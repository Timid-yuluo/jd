<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRecommendation;
use App\Models\JobRecommendationRun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

/**
 * #29 推荐效果看板
 *
 * 统计全局推荐生成量、查看率、投递率、忽略率、平均匹配分
 * 关联文档：docs/features-development-plan.md §5.5
 */
final class RecommendationInsightsController extends Controller
{
    /**
     * 看板首页：最近 30 天数据
     */
    public function index(): View
    {
        $since = now()->subDays(30);

        // 总体统计
        $overall = JobRecommendation::where('created_at', '>=', $since)
            ->selectRaw(
                'COUNT(*) AS total,
                SUM(viewed_at IS NOT NULL) AS viewed,
                SUM(status = ?) AS applied,
                SUM(status = ?) AS dismissed,
                AVG(match_score) AS avg_score',
                [JobRecommendation::STATUS_APPLIED, JobRecommendation::STATUS_DISMISSED]
            )
            ->first();

        $total = (int) ($overall->total ?? 0);
        $viewed = (int) ($overall->viewed ?? 0);
        $applied = (int) ($overall->applied ?? 0);
        $dismissed = (int) ($overall->dismissed ?? 0);

        $metrics = [
            'total' => $total,
            'viewed' => $viewed,
            'applied' => $applied,
            'dismissed' => $dismissed,
            'view_rate' => $total > 0 ? round($viewed / $total * 100, 2) : 0,
            'apply_rate' => $total > 0 ? round($applied / $total * 100, 2) : 0,
            'dismiss_rate' => $total > 0 ? round($dismissed / $total * 100, 2) : 0,
            'avg_score' => (float) ($overall->avg_score ?? 0),
        ];

        // 按日趋势
        $dailyTrend = JobRecommendation::where('created_at', '>=', $since)
            ->selectRaw(
                'DATE(created_at) AS date,
                COUNT(*) AS cnt,
                SUM(viewed_at IS NOT NULL) AS viewed,
                SUM(status = ?) AS applied',
                [JobRecommendation::STATUS_APPLIED]
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Top 公司（按推荐量）
        $topCompanies = JobRecommendation::where('created_at', '>=', $since)
            ->select('company', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('company')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        // 忽略原因分布
        $dismissReasons = DB::table('recommendation_interactions')
            ->where('interaction_type', 'dismiss')
            ->where('created_at', '>=', $since)
            ->select('reason', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('reason')
            ->orderByDesc('cnt')
            ->get();

        // #30 最近 20 次运行历史
        $recentRuns = JobRecommendationRun::with('user:id,name,email')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.recommendation-insights.index', [
            'metrics' => $metrics,
            'dailyTrend' => $dailyTrend,
            'topCompanies' => $topCompanies,
            'dismissReasons' => $dismissReasons,
            'recentRuns' => $recentRuns,
            'since' => $since,
        ]);
    }
}
