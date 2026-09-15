<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\CreditPackOrder;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class SubscriptionService
{
    /**
     * 创建订阅订单（等待支付）
     */
    public function createOrder(User $user, Plan $plan, string $billingCycle): Order
    {
        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        return Order::create([
            'order_no' => $this->generateOrderNo($user->id, $plan->id),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'status' => Order::STATUS_PENDING,
        ]);
    }

    /**
     * 支付成功后激活订阅
     */
    public function fulfillOrder(
        Order $order,
        ?string $paymentNo = null,
        ?int $paidAmount = null,
        string $paymentMethod = 'wechat'
    ): Subscription {
        return DB::transaction(function () use ($order, $paymentNo, $paidAmount, $paymentMethod) {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()
                ->with(['user', 'plan'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                throw new RuntimeException('订单不存在');
            }

            if ($lockedOrder->isPaid()) {
                if ($paymentNo !== null && $lockedOrder->payment_no !== null && $lockedOrder->payment_no !== $paymentNo) {
                    throw new RuntimeException('订单已由其他支付流水完成');
                }

                $active = $lockedOrder->user->activeSubscription;
                if ($active) {
                    return $active;
                }

                return $this->activate($lockedOrder->user, $lockedOrder->plan, $lockedOrder->billing_cycle);
            }

            if ($paidAmount === null || $paidAmount !== (int) $lockedOrder->amount) {
                throw new InvalidArgumentException('支付金额与订单金额不一致');
            }

            if ($paymentNo !== null && Order::query()
                ->where('payment_no', $paymentNo)
                ->where('id', '!=', $lockedOrder->id)
                ->exists()
            ) {
                throw new RuntimeException('支付流水号已被其他订单使用');
            }

            $lockedOrder->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
                'payment_method' => $paymentMethod,
                'payment_no' => $paymentNo,
            ]);

            return $this->activate($lockedOrder->user, $lockedOrder->plan, $lockedOrder->billing_cycle);
        });
    }

    /**
     * 激活订阅
     */
    public function activate(User $user, Plan $plan, string $billingCycle): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $billingCycle) {
            // 将已有活跃订阅标记过期
            Subscription::where('user_id', $user->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            $duration = $billingCycle === 'yearly' ? 12 : 1;

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'status' => Subscription::STATUS_ACTIVE,
                'started_at' => now(),
                'expires_at' => now()->addMonths($duration),
            ]);

            // 更新用户当前套餐
            $user->setPlan($plan->slug);
            $user->clearPlanCache();

            return $subscription;
        });
    }

    /**
     * 续费（延长现有订阅）
     */
    public function renew(User $user, Plan $plan, string $billingCycle): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $billingCycle) {
            $activeSub = $user->activeSubscription;

            if ($activeSub && $activeSub->plan_id === $plan->id) {
                // 同套餐续费，延长到期时间
                $duration = $billingCycle === 'yearly' ? 12 : 1;
                $activeSub->update([
                    'expires_at' => $activeSub->expires_at->addMonths($duration),
                    'billing_cycle' => $billingCycle,
                ]);

                return $activeSub->fresh();
            }

            // 不同套餐，走激活逻辑
            return $this->activate($user, $plan, $billingCycle);
        });
    }

    /**
     * 升级套餐
     */
    public function upgrade(User $user, Plan $newPlan, string $billingCycle): array
    {
        return DB::transaction(function () use ($user, $newPlan, $billingCycle) {
            $activeSub = $user->activeSubscription;
            $creditAmount = 0;

            if ($activeSub) {
                // 计算剩余天数折算抵扣金额
                $remainingDays = now()->diffInDays($activeSub->expires_at, false);
                if ($remainingDays > 0) {
                    $currentPlan = $activeSub->plan;
                    $dailyPrice = $billingCycle === 'yearly'
                        ? $currentPlan->price_yearly / 365
                        : $currentPlan->price_monthly / 30;
                    $creditAmount = (int) round($dailyPrice * $remainingDays);
                }

                // 将旧订阅标记取消
                $activeSub->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);
            }

            $amount = $billingCycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;
            $finalAmount = max(0, $amount - $creditAmount);

            // 创建新订阅
            $duration = $billingCycle === 'yearly' ? 12 : 1;
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $newPlan->id,
                'billing_cycle' => $billingCycle,
                'status' => Subscription::STATUS_ACTIVE,
                'started_at' => now(),
                'expires_at' => now()->addMonths($duration),
            ]);

            $user->setPlan($newPlan->slug);
            $user->clearPlanCache();

            return [
                'subscription' => $subscription,
                'credit_amount' => $creditAmount,
                'final_amount' => $finalAmount,
            ];
        });
    }

    /**
     * 到期处理（定时任务调用）
     */
    public function expireSubscriptions(): int
    {
        $expired = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $subscription) {
            DB::transaction(function () use ($subscription) {
                $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
                $subscription->user->setPlan('free');
                $subscription->user->clearPlanCache();
            });
            $count++;
        }

        return $count;
    }

    /**
     * 为新注册用户初始化免费套餐
     */
    public function initFreePlan(User $user): Subscription
    {
        $freePlan = Plan::findBySlug('free');

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $freePlan->id,
            'billing_cycle' => 'monthly',
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => self::safeExpiresAt((int) config('membership.free_plan_years', 100)),
        ]);
    }

    /**
     * 取消订阅（到期不续费）
     */
    public function cancel(User $user): bool
    {
        $activeSub = $user->activeSubscription;

        if (! $activeSub) {
            return false;
        }

        $activeSub->update([
            'cancelled_at' => now(),
            // 不立即改变状态，到期后自动过期
        ]);

        return true;
    }

    /**
     * 过期待支付订单（订阅与次卡）
     *
     * @return array{subscription_orders:int,credit_pack_orders:int}
     */
    public function expirePendingOrders(int $hours = 72): array
    {
        $cutoffAt = now()->subHours(max(1, $hours));
        $expiredAt = now();

        $subscriptionOrders = Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '<=', $cutoffAt)
            ->update([
                'status' => Order::STATUS_EXPIRED,
                'expired_at' => $expiredAt,
            ]);

        $creditPackOrders = CreditPackOrder::query()
            ->where('status', CreditPackOrder::STATUS_PENDING)
            ->where('created_at', '<=', $cutoffAt)
            ->update([
                'status' => CreditPackOrder::STATUS_EXPIRED,
                'expired_at' => $expiredAt,
            ]);

        return [
            'subscription_orders' => (int) $subscriptionOrders,
            'credit_pack_orders' => (int) $creditPackOrders,
        ];
    }

    private function generateOrderNo(int $userId, int $planId): string
    {
        return 'SUB'.now()->format('YmdHis').str_pad((string) ($userId % 10000), 4, '0', STR_PAD_LEFT).random_int(10, 99);
    }

    /**
     * MySQL TIMESTAMP 最大值为 2038-01-19，超过会报错
     */
    private static function safeExpiresAt(int $years): string
    {
        $expiresAt = now()->addYears($years);

        // MySQL TIMESTAMP 最大值为 2038-01-19，取安全上界
        return min($expiresAt->toDateTimeString(), '2037-12-31 23:59:59');
    }
}
