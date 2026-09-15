<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\QuotaUsage;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * CheckQuota 中间件测试
 *
 * 覆盖场景：
 * - 配额充足时放行并扣减
 * - 配额耗尽时返回 429（JSON）/ 302 重定向（非 JSON）
 * - 配额耗尽但有次卡时返回确认提示（200 + QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE）
 * - 幂等性保护：重复请求不重复扣减
 * - 错误状态码不扣减
 */
class CheckQuotaMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建免费套餐（月配额 2 次）
        Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => '免费版',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'quotas' => ['optimize_full' => ['monthly' => 2]],
                'features' => [],
                'sort_order' => 0,
                'is_active' => true,
            ]
        )->forceFill(['quotas' => ['optimize_full' => ['monthly' => 2]]])->save();
        Cache::flush();

        $this->user = User::factory()->create(['current_plan_slug' => 'free']);

        // 注册受配额保护的路由（排除 CSRF 验证以便测试）
        \Illuminate\Support\Facades\Route::post('/_test/quota/optimize', function (\Illuminate\Http\Request $request) {
            $request->attributes->set('quota_consume', true);
            return response()->json(['success' => true]);
        })->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
          ->middleware(['web', 'quota'])
          ->name('user.resumes.optimize');

        \Illuminate\Support\Facades\Route::post('/_test/quota/error', function (\Illuminate\Http\Request $request) {
            $request->attributes->set('quota_consume', true);
            return response()->json(['error' => 'server error'], 500);
        })->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
          ->middleware(['web', 'quota'])
          ->name('user.resumes.optimize-section');
    }

    public function test_middleware_allows_when_quota_available(): void
    {
        $this->actingAs($this->user)
            ->postJson('/_test/quota/optimize', ['content' => 'test'])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 验证配额已扣减
        $usage = QuotaUsage::where('user_id', $this->user->id)
            ->where('quota_key', 'optimize_full')
            ->first();
        $this->assertNotNull($usage);
        $this->assertSame(1, $usage->used);
    }

    public function test_middleware_denies_with_429_when_quota_exhausted_json(): void
    {
        // 预置已用满
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 2,
        ]);
        Cache::flush();

        $this->actingAs($this->user)
            ->postJson('/_test/quota/optimize', ['content' => 'test'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'QUOTA_EXCEEDED')
            ->assertHeader('X-Quota-Status', 'exceeded');
    }

    public function test_middleware_redirects_when_quota_exhausted_non_json(): void
    {
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 2,
        ]);
        Cache::flush();

        $this->actingAs($this->user)
            ->withHeaders(['Accept' => 'text/html'])
            ->post('/_test/quota/optimize', ['content' => 'test'])
            ->assertRedirect();
    }

    public function test_middleware_returns_credit_confirmation_when_credits_available(): void
    {
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 2,
        ]);
        UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 1,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $this->actingAs($this->user)
            ->postJson('/_test/quota/optimize', ['content' => 'test'])
            ->assertOk()
            ->assertJsonPath('code', 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE')
            ->assertJsonPath('data.credits.0.remaining', 1);
    }

    public function test_middleware_does_not_consume_on_error_status(): void
    {
        // 使用 optimize_full 路由（配额已配置），返回 500
        \Illuminate\Support\Facades\Route::post('/_test/quota/error', function (\Illuminate\Http\Request $request) {
            $request->attributes->set('quota_consume', true);
            return response()->json(['error' => 'server error'], 500);
        })->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
          ->middleware(['web', 'quota'])
          ->name('user.resumes.optimize');

        $this->actingAs($this->user)
            ->postJson('/_test/quota/error', ['content' => 'test'])
            ->assertStatus(500);

        // 验证配额未扣减
        $usage = QuotaUsage::where('user_id', $this->user->id)->first();
        $this->assertNull($usage);
    }

    public function test_middleware_idempotency_prevents_double_consume(): void
    {
        // 第一次请求 — 正常扣减
        $this->actingAs($this->user)
            ->postJson('/_test/quota/optimize', ['content' => 'test'])
            ->assertOk();

        $usageAfterFirst = QuotaUsage::where('user_id', $this->user->id)
            ->where('quota_key', 'optimize_full')
            ->first();
        $this->assertSame(1, $usageAfterFirst->used);

        // 第二次相同请求 — 幂等保护，不重复扣减
        $this->actingAs($this->user)
            ->postJson('/_test/quota/optimize', ['content' => 'test'])
            ->assertOk();

        $usageAfterSecond = QuotaUsage::where('user_id', $this->user->id)
            ->where('quota_key', 'optimize_full')
            ->first();
        $this->assertSame(1, $usageAfterSecond->used, '幂等保护应防止重复扣减');
    }

    public function test_middleware_allows_with_user_specified_credit(): void
    {
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 2,
        ]);
        $credit = UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 3,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $this->actingAs($this->user)
            ->postJson('/_test/quota/optimize', [
                'content' => 'test',
                'use_credit' => 1,
                'credit_id' => $credit->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 验证次卡已扣减
        $this->assertSame(2, $credit->fresh()->remaining);
    }

    public function test_middleware_skips_when_no_user(): void
    {
        // 未登录用户：CheckQuota 中间件直接放行（$user 为 null 时 return $next($request)）
        // 认证由其他中间件处理，quota 中间件不负责拦截未登录请求
        $this->postJson('/_test/quota/optimize', ['content' => 'test'])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 验证未扣减配额
        $usage = QuotaUsage::where('user_id', 0)->first();
        $this->assertNull($usage);
    }
}
