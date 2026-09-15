<?php

declare(strict_types=1);

namespace App\Services\JobRecommendation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 推荐交互埋点仓储（#10 从 Controller 抽出，避免 Controller 直连 DB）
 *
 * 写入 recommendation_interactions 表，用于 #29 推荐效果看板
 *
 * 关联文档：docs/features-development-plan.md §5.5
 */
final class RecommendationInteractionRepository
{
    /**
     * 记录一次用户交互
     *
     * @param  string  $type  交互类型：view/applied/dismissed/click
     */
    public function record(int $userId, int $recommendationId, string $type, ?string $reason = null): void
    {
        try {
            DB::table('recommendation_interactions')->insert([
                'user_id' => $userId,
                'job_recommendation_id' => $recommendationId,
                'interaction_type' => $type,
                'reason' => $reason,
                'metadata' => json_encode(['user_agent' => request()->userAgent()]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('推荐交互埋点失败', [
                'recommendation_id' => $recommendationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
