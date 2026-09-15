<?php

declare(strict_types=1);

namespace App\Services\JobRecommendation;

use App\Models\ExternalRecruitment;
use Illuminate\Support\Facades\DB;

/**
 * 招聘岗位获取器（#9 从 JobRecommendationService 拆分）
 *
 * #7 使用 whereNotExists 子查询避免 pluck 全量 ID
 *
 * 关联文档：docs/features-development-plan.md §5.2.1
 */
final class RecruitmentFetcher
{
    /**
     * 获取用户未推荐过的有效岗位
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ExternalRecruitment>
     */
    public function getUnrecommendedForUser(int $userId)
    {
        $batchSize = (int) config('job-matching.batch_size', 50);

        return ExternalRecruitment::query()
            ->approved()
            ->whereNotExists(function ($q) use ($userId): void {
                $q->select(DB::raw(1))
                    ->from('job_recommendations')
                    ->whereColumn('job_recommendations.external_recruitment_id', 'external_recruitments.id')
                    ->where('job_recommendations.user_id', $userId)
                    ->whereNotNull('job_recommendations.external_recruitment_id');
            })
            ->where(function ($q): void {
                $q->whereNull('deadline')->orWhere('deadline', '>', now());
            })
            ->orderByDesc('is_hot')
            ->orderByDesc('source_created_at')
            ->limit($batchSize)
            ->get();
    }
}
