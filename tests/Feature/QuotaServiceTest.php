<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\QuotaUsage;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\Membership\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 配额服务核心业务测试
 *
 * 覆盖场景：
 * - 月配额充足时放行（source=quota）
 * - 月配额耗尽且无次卡时拒绝（QUOTA_EXCEEDED）
 * - 月配额耗尽但有次卡时需确认（QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE）
 * - 用户指定次卡时验证有效性
 * - 配额扣减（月配额/次卡）
 * - 不限量套餐（-1）直接放行
 */
class QuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuotaService $service;
    private User $user;
    private Plan $freePlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QuotaService::class);

        // 创建或更新免费套餐（月配额 3 次），使用 firstOrCreate 避免重复键冲突
        $this->freePlan = Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => '免费版',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'quotas' => ['optimize_full' => ['monthly' => 3]],
                'features' => [],
                'sort_order' => 0,
                'is_active' => true,
            ]
        );
        // 确保配额配置正确（可能已存在但配置不同）
        $this->freePlan->forceFill(['quotas' => ['optimize_full' => ['monthly' => 3]]])->save();
        Cache::flush();

        $this->user = User::factory()->create(['current_plan_slug' => 'free']);
    }

    public function test_check_allows_when_monthly_quota_available(): void
    {
        $result = $this->service->check($this->user, 'optimize_full');

        $this->assertTrue($result['allowed']);
        $this->assertSame('quota', $result['source']);
        $this->assertSame(3, $result['monthly_limit']);
        $this->assertSame(0, $result['monthly_used']);
    }

    public function test_check_denies_when_monthly_quota_exhausted_and_no_credits(): void
    {
        // 预置本月已用满
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 3,
        ]);
        Cache::flush();

        $result = $this->service->check($this->user, 'optimize_full');

        $this->assertFalse($result['allowed']);
        $this->assertSame('QUOTA_EXCEEDED', $result['reason']);
        $this->assertNull($result['credits']);
    }

    public function test_check_returns_credit_confirmation_when_quota_exhausted_but_credits_exist(): void
    {
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 3,
        ]);
        UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 2,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $result = $this->service->check($this->user, 'optimize_full');

        $this->assertFalse($result['allowed']);
        $this->assertSame('QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE', $result['reason']);
        $this->assertNotNull($result['credits']);
        $this->assertCount(1, $result['credits']);
        $this->assertSame(2, $result['credits'][0]['remaining']);
    }

    public function test_check_with_unlimited_quota_returns_allowed(): void
    {
        // 创建不限量套餐
        Plan::where('slug', 'free')->update([
            'quotas' => ['optimize_full' => ['monthly' => -1]],
        ]);
        Cache::flush();
        $this->user->clearPlanCache();

        $result = $this->service->check($this->user, 'optimize_full');

        $this->assertTrue($result['allowed']);
        $this->assertSame(-1, $result['monthly_limit']);
    }

    public function test_resolve_allows_via_monthly_quota(): void
    {
        $resolution = $this->service->resolve($this->user, 'optimize_full', [], 'user.resumes.optimize');

        $this->assertTrue($resolution->canProceed);
        $this->assertSame('quota', $resolution->source);
        $this->assertNull($resolution->creditId);
    }

    public function test_resolve_denies_with_quota_exceeded_when_no_credits(): void
    {
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 3,
        ]);
        Cache::flush();

        $resolution = $this->service->resolve($this->user, 'optimize_full', [], 'user.resumes.optimize');

        $this->assertFalse($resolution->canProceed);
        $this->assertTrue($resolution->isQuotaExceeded());
    }

    public function test_resolve_returns_credit_confirmation_when_credits_available(): void
    {
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 3,
        ]);
        UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 1,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $resolution = $this->service->resolve($this->user, 'optimize_full', [], 'user.resumes.optimize');

        $this->assertFalse($resolution->canProceed);
        $this->assertTrue($resolution->needsCreditConfirmation());
        $this->assertNotNull($resolution->credits);
    }

    public function test_resolve_with_user_specified_credit_allows(): void
    {
        QuotaUsage::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'period' => QuotaUsage::currentPeriod(),
            'used' => 3,
        ]);
        $credit = UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 2,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $resolution = $this->service->resolve(
            $this->user,
            'optimize_full',
            ['use_credit' => 1, 'credit_id' => $credit->id],
            'user.resumes.optimize'
        );

        $this->assertTrue($resolution->canProceed);
        $this->assertSame('credit', $resolution->source);
        $this->assertSame($credit->id, $resolution->creditId);
    }

    public function test_resolve_with_invalid_credit_id_denies(): void
    {
        $resolution = $this->service->resolve(
            $this->user,
            'optimize_full',
            ['use_credit' => 1, 'credit_id' => 99999],
            'user.resumes.optimize'
        );

        $this->assertFalse($resolution->canProceed);
        $this->assertSame('INVALID_CREDIT', $resolution->denyReason);
    }

    public function test_resolve_with_expired_credit_denies(): void
    {
        $credit = UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 1,
            'source_type' => 'manual',
            'expires_at' => now()->subDay(),
        ]);
        Cache::flush();

        $resolution = $this->service->resolve(
            $this->user,
            'optimize_full',
            ['use_credit' => 1, 'credit_id' => $credit->id],
            'user.resumes.optimize'
        );

        $this->assertFalse($resolution->canProceed);
        $this->assertSame('INVALID_CREDIT', $resolution->denyReason);
    }

    public function test_resolve_with_key_mismatch_credit_denies(): void
    {
        $credit = UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'keywords_extract', // 不匹配 optimize_full
            'remaining' => 1,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $resolution = $this->service->resolve(
            $this->user,
            'optimize_full',
            ['use_credit' => 1, 'credit_id' => $credit->id],
            'user.resumes.optimize'
        );

        $this->assertFalse($resolution->canProceed);
        $this->assertSame('CREDIT_KEY_MISMATCH', $resolution->denyReason);
    }

    public function test_increment_quota_usage_increases_used_count(): void
    {
        $this->service->incrementQuotaUsage($this->user->id, 'optimize_full');

        $usage = QuotaUsage::where('user_id', $this->user->id)
            ->where('quota_key', 'optimize_full')
            ->where('period', QuotaUsage::currentPeriod())
            ->first();

        $this->assertNotNull($usage);
        $this->assertSame(1, $usage->used);
    }

    public function test_decrement_credit_reduces_remaining(): void
    {
        $credit = UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 3,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $result = $this->service->decrementCredit($credit->id, $this->user->id, 'optimize_full');

        $this->assertTrue($result);
        $this->assertSame(2, $credit->fresh()->remaining);
    }

    public function test_decrement_credit_fails_when_zero_remaining(): void
    {
        $credit = UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 0,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $result = $this->service->decrementCredit($credit->id, $this->user->id, 'optimize_full');

        $this->assertFalse($result);
    }

    public function test_deduct_skips_when_status_code_is_error(): void
    {
        $resolution = new \App\DTO\QuotaResolution(
            canProceed: true,
            quotaKey: 'optimize_full',
            source: 'quota',
        );

        $this->service->deduct($resolution, $this->user->id, 500);

        $usage = QuotaUsage::where('user_id', $this->user->id)->first();
        $this->assertNull($usage);
    }

    public function test_deduct_increments_quota_on_success_status(): void
    {
        $resolution = new \App\DTO\QuotaResolution(
            canProceed: true,
            quotaKey: 'optimize_full',
            source: 'quota',
        );

        $this->service->deduct($resolution, $this->user->id, 200);

        $usage = QuotaUsage::where('user_id', $this->user->id)
            ->where('quota_key', 'optimize_full')
            ->first();

        $this->assertNotNull($usage);
        $this->assertSame(1, $usage->used);
    }

    public function test_deduct_decrements_credit_on_success_status(): void
    {
        $credit = UserCredit::create([
            'user_id' => $this->user->id,
            'quota_key' => 'optimize_full',
            'remaining' => 2,
            'source_type' => 'manual',
        ]);
        Cache::flush();

        $resolution = new \App\DTO\QuotaResolution(
            canProceed: true,
            quotaKey: 'optimize_full',
            source: 'credit',
            creditId: $credit->id,
        );

        $this->service->deduct($resolution, $this->user->id, 200);

        $this->assertSame(1, $credit->fresh()->remaining);
    }

    public function test_scenario_to_quota_key_mapping(): void
    {
        $this->assertSame('optimize_full', QuotaService::scenarioToQuotaKey('resume.optimize'));
        $this->assertSame('optimize_section', QuotaService::scenarioToQuotaKey('resume.optimize-section'));
        $this->assertSame('keywords_extract', QuotaService::scenarioToQuotaKey('resume.keywords'));
        $this->assertSame('interview_sessions', QuotaService::scenarioToQuotaKey('interview.generate-question'));
        $this->assertSame('job_match', QuotaService::scenarioToQuotaKey('job.match'));
        $this->assertSame('unknown_scenario', QuotaService::scenarioToQuotaKey('unknown_scenario'));
    }

    public function test_route_to_quota_key_mapping(): void
    {
        $this->assertSame('optimize_full', QuotaService::routeToQuotaKey('user.resumes.optimize'));
        $this->assertSame('optimize_full', QuotaService::routeToQuotaKey('user.resumes.optimize-stream'));
        $this->assertSame('optimize_section', QuotaService::routeToQuotaKey('user.resumes.optimize-section'));
        $this->assertSame('keywords_extract', QuotaService::routeToQuotaKey('user.resumes.keywords.extract'));
        $this->assertSame('interview_sessions', QuotaService::routeToQuotaKey('user.interviews.store'));
        $this->assertNull(QuotaService::routeToQuotaKey('user.dashboard'));
    }
}
