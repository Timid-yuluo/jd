<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\DTO\QuotaResolution;
use App\Models\QuotaUsage;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class QuotaService
{
    /**
     * 检查配额，返回结果数组
     *
     * 适用于控制器中的预检查（UI 展示、按钮禁用判断等）。
     * 中间件配额决策请使用 resolve()，它返回 QuotaResolution DTO 并支持次卡自动选择。
     *
     * @return array{allowed: bool, reason: string|null, quota_key: string, monthly_limit: int, monthly_used: int, credits: array|null}
     */
    public function check(User $user, string $quotaKey): array
    {
        $plan = $user->currentPlan();
        if (! $plan) {
            return [
                'allowed' => false,
                'reason' => 'no_plan',
                'quota_key' => $quotaKey,
                'message' => '未找到有效的订阅套餐，请确认账户已激活。',
            ];
        }

        $period = QuotaUsage::currentPeriod();

        // 获取月配额限制
        $monthlyLimit = $plan->getQuotaLimit($quotaKey);

        // -1 表示不限量
        if ($monthlyLimit === -1) {
            return [
                'allowed' => true,
                'reason' => null,
                'quota_key' => $quotaKey,
                'monthly_limit' => -1,
                'monthly_used' => 0,
                'source' => 'quota',
                'credits' => null,
            ];
        }

        // 获取本月已用
        $monthlyUsed = $this->getMonthlyUsed($user->id, $quotaKey, $period);

        // 月配额未满
        if ($monthlyUsed < $monthlyLimit) {
            return [
                'allowed' => true,
                'reason' => null,
                'quota_key' => $quotaKey,
                'monthly_limit' => $monthlyLimit,
                'monthly_used' => $monthlyUsed,
                'source' => 'quota',
                'credits' => null,
            ];
        }

        // 月配额已满，检查次卡余额
        $availableCredits = UserCredit::getAvailableCredits($user->id, $quotaKey);

        if ($availableCredits->isEmpty()) {
            return [
                'allowed' => false,
                'reason' => 'QUOTA_EXCEEDED',
                'quota_key' => $quotaKey,
                'monthly_limit' => $monthlyLimit,
                'monthly_used' => $monthlyUsed,
                'source' => null,
                'credits' => null,
            ];
        }

        // 有次卡余额，但需要用户确认
        return [
            'allowed' => false,
            'reason' => 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE',
            'quota_key' => $quotaKey,
            'monthly_limit' => $monthlyLimit,
            'monthly_used' => $monthlyUsed,
            'source' => null,
            'credits' => $availableCredits->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->packName() ?? ($c->isUniversal() ? '通用次卡' : '专用次卡'),
                'quota_key' => $c->quota_key,
                'quota_label' => $c->quotaLabelText(),
                'usage_text' => $c->usageDescriptionText(),
                'remaining' => $c->remaining,
                'expires_at' => $c->expires_at?->toDateString(),
                'is_universal' => $c->isUniversal(),
            ])->values()->toArray(),
        ];
    }

    /**
     * 自动选择最佳次卡并返回扣减上下文（无需用户手动确认）
     *
     * 优先级：专用次卡（quota_key 匹配）→ 通用次卡（quota_key=null），同类型按过期时间升序
     *
     * @return array{allowed: bool, reason: string|null, source: string|null, credit_id: int|null, credit_name: string|null}
     */
    public function checkWithAutoCredit(User $user, string $quotaKey): array
    {
        $availableCredits = UserCredit::getAvailableCredits($user->id, $quotaKey);

        if ($availableCredits->isEmpty()) {
            return [
                'allowed' => false,
                'reason' => 'QUOTA_EXCEEDED',
                'source' => null,
                'credit_id' => null,
                'credit_name' => null,
            ];
        }

        /** @var UserCredit $bestCredit */
        $bestCredit = $availableCredits->first();
        $creditName = $bestCredit->packName() ?? ($bestCredit->isUniversal() ? '通用次卡' : '专用次卡');

        return [
            'allowed' => true,
            'reason' => null,
            'source' => 'credit',
            'credit_id' => $bestCredit->id,
            'credit_name' => $creditName,
        ];
    }

    /**
     * 检查用户确认使用次卡后的配额
     *
     * @return array{allowed: bool, reason: string|null, source: string|null, credit_id: int|null}
     */
    public function checkWithCredit(User $user, string $quotaKey, int $creditId): array
    {
        $credit = UserCredit::where('id', $creditId)
            ->where('user_id', $user->id)
            ->where('remaining', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $credit) {
            return [
                'allowed' => false,
                'reason' => 'INVALID_CREDIT',
                'source' => null,
                'credit_id' => null,
            ];
        }

        // 验证次卡类型匹配：专用次卡的 quota_key 必须匹配，通用次卡（quota_key=null）可以
        if ($credit->quota_key !== null && $credit->quota_key !== $quotaKey) {
            return [
                'allowed' => false,
                'reason' => 'CREDIT_KEY_MISMATCH',
                'source' => null,
                'credit_id' => null,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'source' => 'credit',
            'credit_id' => $credit->id,
        ];
    }

    /**
     * 扣减月配额用量（AI 调用成功后调用）
     */
    public function incrementQuotaUsage(int $userId, string $quotaKey): void
    {
        $period = QuotaUsage::currentPeriod();

        DB::table('quota_usages')->upsert(
            [
                'user_id' => $userId,
                'quota_key' => $quotaKey,
                'period' => $period,
                'used' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ['user_id', 'quota_key', 'period'],
            ['used' => DB::raw('used + 1'), 'updated_at' => now()]
        );

        // 同步 Redis 缓存
        $cacheKey = "quota:{$userId}:{$quotaKey}:{$period}";
        $newValue = Cache::increment($cacheKey);
        Cache::put($cacheKey, $newValue, now()->addDays(35));
    }

    /**
     * 扣减次卡余额（用户确认使用次卡时调用）
     *
     * 扣减成功后失效次卡缓存，确保下次读取获取最新余额
     */
    public function decrementCredit(int $creditId, int $userId, string $scenario = ''): bool
    {
        $affected = DB::table('user_credits')
            ->where('id', $creditId)
            ->where('user_id', $userId)
            ->where('remaining', '>', 0)
            ->decrement('remaining');

        if ($affected > 0) {
            \App\Models\CreditUsageLog::create([
                'user_id' => $userId,
                'credit_id' => $creditId,
                'scenario' => $scenario,
            ]);

            // 失效次卡缓存，确保下次读取获取最新余额
            UserCredit::forgetAvailableCreditsCache($userId, $scenario);
        }

        return $affected > 0;
    }

    /**
     * @param  array{source:string,quota_key:string,credit_id?:int}|null  $context
     */
    public function consumeFromContext(?array $context, int $userId): void
    {
        if (! is_array($context)) {
            return;
        }

        $source = (string) ($context['source'] ?? '');
        $quotaKey = (string) ($context['quota_key'] ?? '');

        if ($source === 'credit') {
            $creditId = (int) ($context['credit_id'] ?? 0);
            if ($creditId > 0) {
                $this->decrementCredit($creditId, $userId, $quotaKey);
            }

            return;
        }

        if ($source === 'quota' && $quotaKey !== '') {
            $this->incrementQuotaUsage($userId, $quotaKey);
        }
    }

    /**
     * 获取本月已用量
     */
    public function getMonthlyUsed(int $userId, string $quotaKey, string $period): int
    {
        $cacheKey = "quota:{$userId}:{$quotaKey}:{$period}";

        return (int) Cache::remember($cacheKey, 300, function () use ($userId, $quotaKey, $period) {
            $usage = QuotaUsage::where('user_id', $userId)
                ->where('quota_key', $quotaKey)
                ->where('period', $period)
                ->value('used');

            return $usage ?? 0;
        });
    }

    public function getBulkMonthlyUsage(int $userId, array $quotaKeys, string $period): array
    {
        $result = [];
        $uncached = [];

        foreach ($quotaKeys as $key) {
            $cacheKey = "quota:{$userId}:{$key}:{$period}";
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $result[$key] = (int) $cached;
            } else {
                $uncached[] = $key;
            }
        }

        if (!empty($uncached)) {
            $dbResults = QuotaUsage::where('user_id', $userId)
                ->whereIn('quota_key', $uncached)
                ->where('period', $period)
                ->pluck('used', 'quota_key')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            foreach ($uncached as $key) {
                $value = $dbResults[$key] ?? 0;
                $result[$key] = $value;
                Cache::put("quota:{$userId}:{$key}:{$period}", $value, 300);
            }
        }

        return $result;
    }

    /**
     * scenario → quota_key 映射
     */
    public static function scenarioToQuotaKey(string $scenario): string
    {
        return match ($scenario) {
            'resume.optimize' => 'optimize_full',
            'resume.optimize-section' => 'optimize_section',
            'resume.keywords' => 'keywords_extract',
            'resume.import-draft' => 'import_document',
            'interview.generate-question' => 'interview_sessions',
            'job.match' => 'job_match',
            'job.match-analysis' => 'match_analysis',
            // 新增功能模块配额键
            'salary.negotiate' => 'salary_negotiation',
            'assessment.submit' => 'career_assessment',
            'skill.learning-path' => 'skill_learning_path',
            'job.recommend.generate' => 'job_recommendation',
            'company.review' => 'company_review',
            default => $scenario,
        };
    }

    /**
     * 路由名 → quota_key 映射
     */
    public static function routeToQuotaKey(string $routeName): ?string
    {
        return match ($routeName) {
            'user.interviews.store' => 'interview_sessions',
            'user.resumes.optimize' => 'optimize_full',
            'user.resumes.optimize-stream' => 'optimize_full',
            'user.resumes.optimize-section' => 'optimize_section',
            'user.resumes.generate-section' => 'optimize_section',
            'user.resumes.keywords.extract' => 'keywords_extract',
            'user.resumes.import-document-draft' => 'import_document',
            'user.resumes.optimize-sessions.create' => 'optimize_full',
            'user.jobs.analyze.submit' => 'job_match',
            // 新增功能模块路由映射
            'user.salary.negotiate' => 'salary_negotiation',
            'user.assessments.submit' => 'career_assessment',
            'user.skills.learn.analyze' => 'skill_learning_path',
            'user.job-recommendations.generate' => 'job_recommendation',
            default => null,
        };
    }

    /**
     * 统一配额决策入口 — 中间件唯一调用点
     *
     * 决策链：
     * 1. 用户指定次卡（use_credit + credit_id）→ 验证次卡有效性
     * 2. 月配额检查 → 充足则放行
     * 3. 配额耗尽 + 有次卡 → 返回 QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE（需用户确认选择次卡）
     *    例外：配置为自动选择的路由直接使用最佳次卡
     * 4. 无次卡 → 返回 QUOTA_EXCEEDED
     */
    public function resolve(User $user, string $quotaKey, array $input, string $routeName): QuotaResolution
    {
        // Step 1: 用户指定次卡
        if (! empty($input['use_credit']) && ! empty($input['credit_id'])) {
            return $this->resolveWithCredit($user, $quotaKey, (int) $input['credit_id']);
        }

        // Step 2: 月配额检查
        $result = $this->check($user, $quotaKey);
        if ($result['allowed']) {
            return new QuotaResolution(
                canProceed: true,
                quotaKey: $quotaKey,
                source: 'quota',
            );
        }

        // Step 3: 配额耗尽 + 有次卡
        if ($result['reason'] === 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE') {
            // 例外：配置为自动选择的路由直接使用最佳次卡
            if ($this->routeAllowsAutoCredit($routeName)) {
                $autoResult = $this->checkWithAutoCredit($user, $quotaKey);
                if ($autoResult['allowed'] && $autoResult['credit_id']) {
                    return new QuotaResolution(
                        canProceed: true,
                        quotaKey: $quotaKey,
                        source: 'credit',
                        creditId: $autoResult['credit_id'],
                        creditName: $autoResult['credit_name'],
                        autoSelected: true,
                    );
                }
            }

            // 默认：需要用户确认选择次卡
            return new QuotaResolution(
                canProceed: false,
                quotaKey: $quotaKey,
                source: '',
                denyReason: 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE',
                monthlyLimit: $result['monthly_limit'],
                monthlyUsed: $result['monthly_used'],
                credits: $result['credits'],
            );
        }

        // Step 4: 无次卡
        return new QuotaResolution(
            canProceed: false,
            quotaKey: $quotaKey,
            source: '',
            denyReason: 'QUOTA_EXCEEDED',
            monthlyLimit: $result['monthly_limit'],
            monthlyUsed: $result['monthly_used'],
        );
    }

    /**
     * 响应成功后扣减配额 — terminate 委托调用
     */
    public function deduct(QuotaResolution $resolution, int $userId, int $statusCode): void
    {
        if (! $resolution->canProceed || $statusCode >= 400) {
            return;
        }

        if ($resolution->source === 'credit' && $resolution->creditId !== null) {
            $this->decrementCredit($resolution->creditId, $userId, $resolution->quotaKey);
        } elseif ($resolution->source === 'quota') {
            $this->incrementQuotaUsage($userId, $resolution->quotaKey);
        }
    }

    /**
     * 用户指定次卡时的配额决策
     */
    private function resolveWithCredit(User $user, string $quotaKey, int $creditId): QuotaResolution
    {
        $result = $this->checkWithCredit($user, $quotaKey, $creditId);

        if ($result['allowed']) {
            return new QuotaResolution(
                canProceed: true,
                quotaKey: $quotaKey,
                source: 'credit',
                creditId: $result['credit_id'],
            );
        }

        return new QuotaResolution(
            canProceed: false,
            quotaKey: $quotaKey,
            source: '',
            denyReason: $result['reason'],
        );
    }

    /**
     * 判断路由是否允许自动选择次卡（无需用户确认）
     * 默认所有路由都需要用户确认选择次卡，仅配置的路由允许自动选择
     */
    private function routeAllowsAutoCredit(string $routeName): bool
    {
        return in_array($routeName, config('quota.credit_auto_select_routes', []), true);
    }
}
