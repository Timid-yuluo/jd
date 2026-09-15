<?php

declare(strict_types=1);

namespace App\Services\JobRecommendation;

use App\Models\JobRecommendation;

/**
 * 推荐统计查询（#11 从 Controller 抽出）
 *
 * 合并 4 次 COUNT 为 1 次条件聚合
 *
 * 关联文档：docs/features-development-plan.md §5.5
 */
final class RecommendationStatsAction
{
    /**
     * 获取用户推荐统计
     *
     * @return array{total: int, new: int, applied: int, high_match: int}
     */
    public function forUser(int $userId): array
    {
        $highMatchThreshold = (int) config('job-matching.high_match_threshold', 80);
        $row = JobRecommendation::where('user_id', $userId)
            ->selectRaw(
                'COUNT(*) AS total,
                SUM(status = ?) AS new_count,
                SUM(status = ?) AS applied_count,
                SUM(match_score >= ? AND status != ?) AS high_match_count',
                [
                    JobRecommendation::STATUS_NEW,
                    JobRecommendation::STATUS_APPLIED,
                    $highMatchThreshold,
                    JobRecommendation::STATUS_DISMISSED,
                ]
            )
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'new' => (int) ($row->new_count ?? 0),
            'applied' => (int) ($row->applied_count ?? 0),
            'high_match' => (int) ($row->high_match_count ?? 0),
        ];
    }
}
