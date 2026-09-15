<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * 校验用户 current_plan_slug 与实际活跃订阅是否一致。
 *
 * 场景：订阅过期后 ExpireSubscriptions 命令应将 current_plan_slug 重置为 free，
 * 但如果命令执行失败或手动修改数据库，可能导致不一致。
 * 本命令扫描所有不一致的用户并自动修正。
 */
final class ValidatePlanConsistency extends Command
{
    protected $signature = 'validate:plan-consistency
                            {--fix : 自动修正不一致的记录}
                            {--limit=500 : 单次最多处理条数}';

    protected $description = '校验用户 current_plan_slug 与活跃订阅是否一致，可选自动修正';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $limit = max(1, (int) $this->option('limit'));

        $users = User::query()
            ->select(['id', 'current_plan_slug', 'email'])
            ->limit($limit)
            ->get();

        $inconsistent = 0;
        $fixed = 0;

        foreach ($users as $user) {
            $expectedSlug = $this->resolveExpectedPlanSlug($user);
            $actualSlug = $user->current_plan_slug ?? 'free';

            if ($actualSlug !== $expectedSlug) {
                $inconsistent++;
                $this->warn("  用户 #{$user->id} ({$user->email}): current_plan_slug={$actualSlug}, expected={$expectedSlug}");

                if ($fix) {
                    $user->setPlan($expectedSlug);
                    $fixed++;
                    $this->info("    -> 已修正为 {$expectedSlug}");
                }
            }
        }

        if ($inconsistent === 0) {
            $this->info('所有用户套餐一致性校验通过，无异常。');

            return self::SUCCESS;
        }

        $this->info(sprintf('发现 %d 个不一致用户%s', $inconsistent, $fix ? "，已修正 {$fixed} 个" : '（使用 --fix 自动修正）'));

        return $fix ? self::SUCCESS : self::FAILURE;
    }

    /**
     * 根据活跃订阅推导用户应有的 plan slug
     */
    private function resolveExpectedPlanSlug(User $user): string
    {
        $activeSub = Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($activeSub?->plan) {
            return $activeSub->plan->slug;
        }

        // 无活跃订阅则为 free
        return 'free';
    }
}
