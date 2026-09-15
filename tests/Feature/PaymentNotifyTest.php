<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\AlipayPayController;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Membership\AlipayF2FPaymentService;
use App\Services\Membership\CreditService;
use App\Services\Membership\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 支付回调测试
 *
 * 覆盖场景：
 * - 签名验证失败返回 400
 * - 非成功交易状态返回 success（支付宝会重复通知）
 * - 订单号缺失返回 400
 * - 订单不存在返回 404
 * - 订阅订单支付成功后激活订阅
 * - 金额不一致拒绝支付
 * - 重复支付流水号拒绝
 */
class PaymentNotifyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Plan $plan;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock 支付宝签名验证
        $this->mock(AlipayF2FPaymentService::class, function ($mock) {
            $mock->shouldReceive('verifySign')->andReturn(true);
            $mock->shouldReceive('isEnabled')->andReturn(true);
        });

        $this->plan = Plan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name' => '基础版',
                'price_monthly' => 2900,
                'price_yearly' => 26800,
                'quotas' => ['optimize_full' => ['monthly' => 20]],
                'features' => [],
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
        Cache::flush();

        $this->user = User::factory()->create(['current_plan_slug' => 'free']);
        $this->order = Order::create([
            'order_no' => 'TEST' . time(),
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 2900, // 29.00 元 = 2900 分
            'status' => Order::STATUS_PENDING,
            'expired_at' => now()->addHours(24),
        ]);
    }

    public function test_notify_returns_400_when_sign_verification_fails(): void
    {
        $this->mock(AlipayF2FPaymentService::class, function ($mock) {
            $mock->shouldReceive('verifySign')->andReturn(false);
            $mock->shouldReceive('isEnabled')->andReturn(true);
        });

        $this->post('/alipay-pay/notify', [
            'out_trade_no' => $this->order->order_no,
            'trade_status' => 'TRADE_SUCCESS',
        ])->assertStatus(400);
    }

    public function test_notify_returns_success_for_non_success_trade_status(): void
    {
        $this->post('/alipay-pay/notify', [
            'out_trade_no' => $this->order->order_no,
            'trade_status' => 'WAIT_BUYER_PAY',
            'sign' => 'fake_sign',
        ])->assertOk()
          ->assertSee('success');
    }

    public function test_notify_returns_400_when_out_trade_no_missing(): void
    {
        $this->post('/alipay-pay/notify', [
            'trade_status' => 'TRADE_SUCCESS',
            'sign' => 'fake_sign',
        ])->assertStatus(400);
    }

    public function test_notify_returns_404_when_order_not_found(): void
    {
        $this->post('/alipay-pay/notify', [
            'out_trade_no' => 'NON_EXISTENT_ORDER',
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '29.00',
            'trade_no' => 'ALIPAY123',
            'sign' => 'fake_sign',
        ])->assertStatus(404);
    }

    public function test_notify_fulfills_subscription_order(): void
    {
        $this->post('/alipay-pay/notify', [
            'out_trade_no' => $this->order->order_no,
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '29.00',
            'trade_no' => 'ALIPAY123456',
            'sign' => 'fake_sign',
        ])->assertOk()
          ->assertSee('success');

        // 验证订单已支付
        $this->order->refresh();
        $this->assertSame(Order::STATUS_PAID, $this->order->status);
        $this->assertSame('alipay', $this->order->payment_method);
        $this->assertSame('ALIPAY123456', $this->order->payment_no);
        $this->assertNotNull($this->order->paid_at);
    }

    public function test_notify_rejects_mismatched_amount(): void
    {
        $this->post('/alipay-pay/notify', [
            'out_trade_no' => $this->order->order_no,
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '1.00', // 金额不一致
            'trade_no' => 'ALIPAY123456',
            'sign' => 'fake_sign',
        ])->assertStatus(500); // 异常返回 500

        // 验证订单未被支付
        $this->order->refresh();
        $this->assertSame(Order::STATUS_PENDING, $this->order->status);
    }

    public function test_notify_rejects_duplicate_payment_no(): void
    {
        // 创建另一个已支付订单，使用相同的 payment_no
        $anotherOrder = Order::create([
            'order_no' => 'TEST_DUP' . time(),
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 2900,
            'status' => Order::STATUS_PAID,
            'payment_method' => 'alipay',
            'payment_no' => 'DUP_PAYMENT_NO',
            'paid_at' => now(),
        ]);

        $this->post('/alipay-pay/notify', [
            'out_trade_no' => $this->order->order_no,
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '29.00',
            'trade_no' => 'DUP_PAYMENT_NO', // 重复的支付流水号
            'sign' => 'fake_sign',
        ])->assertStatus(500);

        // 验证订单未被支付
        $this->order->refresh();
        $this->assertSame(Order::STATUS_PENDING, $this->order->status);
    }

    public function test_notify_idempotent_for_already_paid_order(): void
    {
        // 先将订单标记为已支付
        $this->order->update([
            'status' => Order::STATUS_PAID,
            'payment_no' => 'ALIPAY_EXISTING',
            'paid_at' => now(),
            'payment_method' => 'alipay',
        ]);

        // 相同 payment_no 的重复通知应返回 success（幂等）
        $this->post('/alipay-pay/notify', [
            'out_trade_no' => $this->order->order_no,
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '29.00',
            'trade_no' => 'ALIPAY_EXISTING',
            'sign' => 'fake_sign',
        ])->assertOk()
          ->assertSee('success');
    }

    public function test_yuan_to_fen_conversion(): void
    {
        // 通过反射测试私有方法
        $controller = app(AlipayPayController::class);
        $reflection = new \ReflectionMethod($controller, 'yuanToFen');
        $reflection->setAccessible(true);

        $this->assertSame(2900, $reflection->invoke($controller, '29.00'));
        $this->assertSame(100, $reflection->invoke($controller, '1.00'));
        $this->assertSame(1, $reflection->invoke($controller, '0.01'));
        $this->assertSame(26800, $reflection->invoke($controller, '268.00'));
        $this->assertNull($reflection->invoke($controller, ''));
    }
}
