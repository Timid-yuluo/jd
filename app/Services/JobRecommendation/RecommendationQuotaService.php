<?php

declare(strict_types=1);

namespace App\Services\JobRecommendation;

use Illuminate\Support\Facades\Cache;

/**
 * 推荐生成每日配额服务
 *
 * 使用 Cache::increment 原子操作避免并发计数丢失
 *
 * 关联文档：docs/features-development-plan.md §5.5
 */
final class RecommendationQuotaService
{
    /**
     * 配额缓存 key 前缀
     */
    private const QUOTA_KEY_PREFIX = 'job_rec_quota:';

    /**
     * 缓存有效期：当日 23:59:59
     */
    private const CACHE_TTL_HOURS = 24;

    /**
     * 获取今日已用次数
     */
    public function usedToday(int $userId): int
    {
        return (int) Cache::get($this->quotaKey($userId), 0);
    }

    /**
     * 获取今日剩余次数
     */
    public function remainingToday(int $userId): int
    {
        $dailyQuota = $this->dailyQuota();

        return max(0, $dailyQuota - $this->usedToday($userId));
    }

    /**
     * 获取每日配额上限
     */
    public function dailyQuota(): int
    {
        return (int) config('job-matching.daily_quota', 3);
    }

    /**
     * 原子扣减一次配额（先 increment 再判断超限，超限则回滚）
     *
     * @return bool true=扣减成功，false=已达上限
     */
    public function tryConsume(int $userId): bool
    {
        $dailyQuota = $this->dailyQuota();
        $key = $this->quotaKey($userId);

        // #3 原子 increment，并发安全
        $used = Cache::increment($key);

        // 首次 increment 需要设置 TTL（increment 不会自动设置）
        if ($used === 1) {
            Cache::put($key, 1, now()->endOfDay());
        }

        // 超限：回滚 increment
        if ($used > $dailyQuota) {
            Cache::decrement($key);

            return false;
        }

        return true;
    }

    /**
     * 释放一次配额（任务失败时回退）
     */
    public function release(int $userId): void
    {
        $used = $this->usedToday($userId);
        if ($used > 0) {
            Cache::decrement($this->quotaKey($userId));
        }
    }

    /**
     * 构建配额缓存 key
     */
    private function quotaKey(int $userId): string
    {
        return self::QUOTA_KEY_PREFIX . $userId . ':' . today()->toDateString();
    }
}
