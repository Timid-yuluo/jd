<?php

declare(strict_types=1);

namespace App\Services\JobRecommendationProgress;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * #3 推荐进度持久化
 *
 * 进度信息持久化到 DB（原 Cache 在跨进程场景会丢失）
 * Cache 仍用于高频读取加速
 */
final class RecommendationProgressRepository
{
    private const CACHE_TTL = 600;

    /**
     * 写入进度（DB + Cache 双写）
     */
    public function set(int $userId, string $status, int $processed, int $total, ?string $error = null): void
    {
        $cacheKey = $this->cacheKey($userId);

        $data = [
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'error' => $error,
            'progress' => $total > 0 ? round($processed / $total * 100, 1) : 0,
            'updated_at' => now()->toDateTimeString(),
        ];

        Cache::put($cacheKey, $data, self::CACHE_TTL);

        // 写入 DB 表（若存在）
        if (DB::getSchemaBuilder()->hasTable('job_recommendation_progress')) {
            DB::table('job_recommendation_progress')->updateOrInsert(
                ['user_id' => $userId],
                array_merge($data, ['updated_at' => now()])
            );
        }
    }

    /**
     * 读取进度（优先 Cache）
     *
     * @return array<string, mixed>|null
     */
    public function get(int $userId): ?array
    {
        $cacheKey = $this->cacheKey($userId);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Cache miss 时回源 DB
        if (DB::getSchemaBuilder()->hasTable('job_recommendation_progress')) {
            $row = DB::table('job_recommendation_progress')->where('user_id', $userId)->first();
            if ($row !== null) {
                $data = (array) $row;
                Cache::put($cacheKey, $data, self::CACHE_TTL);

                return $data;
            }
        }

        return null;
    }

    /**
     * 清除进度（任务完成后调用）
     */
    public function clear(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));

        if (DB::getSchemaBuilder()->hasTable('job_recommendation_progress')) {
            DB::table('job_recommendation_progress')->where('user_id', $userId)->delete();
        }
    }

    private function cacheKey(int $userId): string
    {
        return 'recommendation_progress:' . $userId;
    }
}
