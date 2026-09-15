<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Infrastructure\System\Contracts\SystemResourceMonitor;
use App\Models\CreditPackOrder;
use App\Models\Feedback;
use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\Order;
use App\Models\Resume;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DashboardService
{
    public function __construct(
        private readonly SystemResourceMonitor $systemMonitor,
    ) {}

    /**
     * Build dashboard data payload.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        return Cache::remember('admin.dashboard.data', now()->addSeconds((int) config('cache_ttl.ttl.standard', 300)), function () {
            return $this->buildDashboardData();
        });
    }

    /**
     * Build dashboard data payload.
     *
     * @return array<string, mixed>
     */
    private function buildDashboardData(): array
    {
        $today = Carbon::today();

        // 合并4个独立统计查询为1次（users/resumes/interviews/applications）
        $coreStats = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM users WHERE created_at >= ?) AS today_users,
                (SELECT COUNT(*) FROM resumes) AS total_resumes,
                (SELECT COUNT(*) FROM resumes WHERE created_at >= ?) AS today_resumes,
                (SELECT COUNT(*) FROM interview_sessions) AS total_interviews,
                (SELECT COUNT(*) FROM interview_sessions WHERE created_at >= ?) AS today_interviews,
                (SELECT COUNT(*) FROM job_applications) AS total_applications,
                (SELECT COUNT(*) FROM job_applications WHERE created_at >= ?) AS today_applications
        ", [$today, $today, $today, $today]);

        $totalUsers = (int) ($coreStats->total_users ?? 0);
        $todayUsers = (int) ($coreStats->today_users ?? 0);
        $totalResumes = (int) ($coreStats->total_resumes ?? 0);
        $todayResumes = (int) ($coreStats->today_resumes ?? 0);
        $totalInterviews = (int) ($coreStats->total_interviews ?? 0);
        $todayInterviews = (int) ($coreStats->today_interviews ?? 0);
        $totalApplications = (int) ($coreStats->total_applications ?? 0);
        $todayApplications = (int) ($coreStats->today_applications ?? 0);

        $aiStats = UsageLog::selectRaw('
            count(*) as total,
            sum(case when created_at >= ? then 1 else 0 end) as today,
            coalesce(sum(prompt_tokens), 0) + coalesce(sum(completion_tokens), 0) as total_tokens,
            coalesce(sum(cost_micros), 0) as total_cost
        ', [$today])->first();

        $totalAiCalls = (int) ($aiStats->total ?? 0);
        $todayAiCalls = (int) ($aiStats->today ?? 0);
        $totalTokens = (float) ($aiStats->total_tokens ?? 0);
        $totalCost = (float) ($aiStats->total_cost ?? 0);
        $evaluationQueueName = (string) config('interview.evaluation_queue', 'default');

        // Schema::hasTable 结果缓存24小时（表结构不会变）
        $hasJobsTable = Cache::remember('admin.schema.has_jobs_table', now()->addDay(), fn () => Schema::hasTable('jobs'));
        $hasFailedJobsTable = Cache::remember('admin.schema.has_failed_jobs_table', now()->addDay(), fn () => Schema::hasTable('failed_jobs'));

        $queueBacklog = $hasJobsTable ? DB::table('jobs')->where('queue', $evaluationQueueName)->count() : 0;
        $failedEvaluationJobs = $hasFailedJobsTable
            ? DB::table('failed_jobs')->where('payload', 'like', '%EvaluateInterviewAnswerJob%')->count()
            : 0;

        $pendingEvaluations = InterviewQuestion::query()
            ->whereNotNull('answer')
            ->whereNull('score')
            ->count();

        // 合并5次JD面试clone查询为1次聚合查询
        $jdStats = InterviewSession::query()
            ->whereNotNull('job_description')
            ->where('job_description', '!=', '')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN report->'$.jd_alignment.match_score' IS NOT NULL THEN 1 ELSE 0 END) as evaluated,
                AVG(CASE WHEN report->'$.jd_alignment.match_score' IS NOT NULL THEN JSON_UNQUOTE(JSON_EXTRACT(report, '$.jd_alignment.match_score')) ELSE NULL END) as avg_match,
                SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(report, '$.jd_alignment.match_score')) < 5 THEN 1 ELSE 0 END) as low_match,
                SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(report, '$.jd_alignment.match_score')) >= 8 THEN 1 ELSE 0 END) as high_match
            ")->first();

        $jdInterviewCount = (int) ($jdStats->total ?? 0);
        $jdEvaluatedCount = (int) ($jdStats->evaluated ?? 0);
        $jdAvgMatchScore = (float) ($jdStats->avg_match ?? 0);
        $jdLowMatchCount = (int) ($jdStats->low_match ?? 0);
        $jdHighMatchCount = (int) ($jdStats->high_match ?? 0);
        // 合并2次AI提前终止查询为1次
        $aiTermStats = InterviewSession::query()
            ->where('report->early_termination->enabled_by_ai', true)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as today
            ", [$today])->first();

        $aiEarlyTerminatedCount = (int) ($aiTermStats->total ?? 0);
        $todayAiEarlyTerminatedCount = (int) ($aiTermStats->today ?? 0);

        // Get latest applications for the activity feed
        $latestApplications = JobApplication::with('user')
            ->latest('id')
            ->limit((int) config('ui.limit.dashboard_recent', 5))
            ->get()
            ->map(function ($app) {
                return [
                    'user' => $app->user?->name ?? '未知',
                    'action' => "投递了 {$app->company} - {$app->position}",
                    'time' => $app->created_at->diffForHumans(),
                    'status' => $this->statusLabel($app->status),
                    'status_type' => $this->statusColor($app->status),
                ];
            })
            ->toArray();

        // Application status distribution
        $statusCounts = JobApplication::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // AI usage by scenario
        $usageByScenario = UsageLog::selectRaw('scenario, count(*) as count, sum(prompt_tokens + completion_tokens) as tokens')
            ->groupBy('scenario')
            ->get()
            ->map(fn ($row) => [
                'scenario' => $row->scenario,
                'count' => $row->count,
                'tokens' => (int) $row->tokens,
            ])
            ->toArray();

        // 合并2次营收查询为1次UNION ALL
        $monthStart = Carbon::now()->startOfMonth();
        $revenueRow = DB::selectOne("
            SELECT
                SUM(total_revenue) AS total_revenue,
                SUM(today_revenue) AS today_revenue,
                SUM(month_revenue) AS month_revenue
            FROM (
                SELECT
                    COALESCE(SUM(amount), 0) AS total_revenue,
                    COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END), 0) AS today_revenue,
                    COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END), 0) AS month_revenue
                FROM orders WHERE status = ?
                UNION ALL
                SELECT
                    COALESCE(SUM(amount), 0),
                    COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END), 0),
                    COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END), 0)
                FROM credit_pack_orders WHERE status = ?
            ) AS rev
        ", [$today, $monthStart, Order::STATUS_PAID, $today, $monthStart, CreditPackOrder::STATUS_PAID]);

        $totalRevenue = (float) ($revenueRow->total_revenue ?? 0);
        $todayRevenue = (float) ($revenueRow->today_revenue ?? 0);
        $monthRevenue = (float) ($revenueRow->month_revenue ?? 0);

        // 合并3次待处理提醒查询为1次
        $alertStats = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM feedbacks WHERE status = 'pending') AS pending_feedbacks,
                (SELECT COUNT(*) FROM users WHERE is_suspended = 1) AS suspended_users,
                (SELECT COUNT(*) FROM users WHERE deletion_scheduled_at IS NOT NULL) AS pending_deletion_users
        ");

        $pendingFeedbacks = (int) ($alertStats->pending_feedbacks ?? 0);
        $suspendedUsers = (int) ($alertStats->suspended_users ?? 0);
        $pendingDeletionUsers = (int) ($alertStats->pending_deletion_users ?? 0);

        return [
            'summary' => [
                'users' => number_format($totalUsers),
                'users_today' => "+{$todayUsers}",
                'resumes' => number_format($totalResumes),
                'resumes_today' => "+{$todayResumes}",
                'interviews' => number_format($totalInterviews),
                'interviews_today' => "+{$todayInterviews}",
                'applications' => number_format($totalApplications),
                'applications_today' => "+{$todayApplications}",
                'ai_calls' => number_format($totalAiCalls),
                'ai_calls_today' => "+{$todayAiCalls}",
            ],
            'stats' => [
                [
                    'label' => '总用户数',
                    'value' => number_format($totalUsers),
                    'trend_label' => '今日新增',
                    'trend_value' => "+{$todayUsers}",
                    'trend_type' => $todayUsers > 0 ? 'up' : 'down',
                ],
                [
                    'label' => '总简历数',
                    'value' => number_format($totalResumes),
                    'trend_label' => '今日新增',
                    'trend_value' => "+{$todayResumes}",
                    'trend_type' => $todayResumes > 0 ? 'up' : 'down',
                ],
                [
                    'label' => '面试次数',
                    'value' => number_format($totalInterviews),
                    'trend_label' => '今日新增',
                    'trend_value' => "+{$todayInterviews}",
                    'trend_type' => $todayInterviews > 0 ? 'up' : 'down',
                ],
                [
                    'label' => 'AI 调用次数',
                    'value' => number_format($totalAiCalls),
                    'trend_label' => '今日新增',
                    'trend_value' => "+{$todayAiCalls}",
                    'trend_type' => $todayAiCalls > 0 ? 'up' : 'down',
                ],
            ],
            'status_distribution' => [
                ['label' => JobApplication::statusLabels()[JobApplication::STATUS_WISHLIST], 'value' => $statusCounts[JobApplication::STATUS_WISHLIST] ?? 0],
                ['label' => JobApplication::statusLabels()[JobApplication::STATUS_APPLIED], 'value' => $statusCounts[JobApplication::STATUS_APPLIED] ?? 0],
                ['label' => JobApplication::statusLabels()[JobApplication::STATUS_WRITTEN], 'value' => $statusCounts[JobApplication::STATUS_WRITTEN] ?? 0],
                ['label' => JobApplication::statusLabels()[JobApplication::STATUS_INTERVIEW], 'value' => $statusCounts[JobApplication::STATUS_INTERVIEW] ?? 0],
                ['label' => JobApplication::statusLabels()[JobApplication::STATUS_OFFER], 'value' => $statusCounts[JobApplication::STATUS_OFFER] ?? 0],
                ['label' => JobApplication::statusLabels()[JobApplication::STATUS_REJECTED], 'value' => $statusCounts[JobApplication::STATUS_REJECTED] ?? 0],
            ],
            'ai_usage' => [
                'total_tokens' => number_format($totalTokens),
                'total_cost' => number_format($totalCost),
                'by_scenario' => $usageByScenario,
            ],
            'queue_health' => [
                'queue_name' => $evaluationQueueName,
                'queue_backlog' => $queueBacklog,
                'pending_evaluations' => $pendingEvaluations,
                'failed_jobs' => $failedEvaluationJobs,
            ],
            'jd_quality' => [
                'total_with_jd' => $jdInterviewCount,
                'evaluated_count' => $jdEvaluatedCount,
                'avg_match_score' => round($jdAvgMatchScore, 1),
                'high_match_count' => $jdHighMatchCount,
                'low_match_count' => $jdLowMatchCount,
            ],
            'interview_governance' => [
                'ai_early_terminated_count' => $aiEarlyTerminatedCount,
                'ai_early_terminated_today' => $todayAiEarlyTerminatedCount,
            ],
            'activities' => $latestApplications,
            'system_usage' => $this->getSystemUsage(),
            'revenue' => [
                'total' => number_format($totalRevenue / 100, 2),
                'today' => number_format($todayRevenue / 100, 2),
                'month' => number_format($monthRevenue / 100, 2),
            ],
            'pending_alerts' => [
                'pending_feedbacks' => $pendingFeedbacks,
                'suspended_users' => $suspendedUsers,
                'pending_deletion_users' => $pendingDeletionUsers,
                'queue_backlog' => $queueBacklog,
                'failed_jobs' => $failedEvaluationJobs,
            ],
            'ats_distribution' => $this->getAtsDistribution(),
            'trend_7d' => $this->get7DayTrend(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return JobApplication::statusLabels()[$status] ?? $status;
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            JobApplication::STATUS_WISHLIST => 'gray',
            JobApplication::STATUS_APPLIED => 'blue',
            JobApplication::STATUS_WRITTEN => 'azure',
            JobApplication::STATUS_INTERVIEW => 'orange',
            JobApplication::STATUS_OFFER => 'green',
            JobApplication::STATUS_REJECTED => 'red',
            default => 'blue',
        };
    }

    /**
     * Get real system usage metrics.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSystemUsage(): array
    {
        return Cache::remember('admin.dashboard.system_usage', now()->addSeconds((int) config('cache_ttl.ttl.realtime', 30)), function () {
            return $this->fetchSystemUsage();
        });
    }

    private function fetchSystemUsage(): array
    {
        $cpu = $this->systemMonitor->getCpuUsage();
        $memory = $this->systemMonitor->getMemoryUsage();
        $disk = $this->systemMonitor->getDiskUsage();

        return [
            ['label' => 'CPU 使用率', 'value' => $cpu, 'color' => $cpu > 80 ? 'danger' : 'primary'],
            ['label' => '内存使用率', 'value' => $memory, 'color' => $memory > 80 ? 'danger' : 'warning'],
            ['label' => '磁盘使用率', 'value' => $disk, 'color' => $disk > 80 ? 'danger' : 'success'],
        ];
    }

    /**
     * ATS 评分分布统计
     *
     * @return array<string, mixed>
     */
    private function getAtsDistribution(): array
    {
        // 合并6次独立查询为1次聚合查询，减少DB交互
        $row = Resume::query()->whereNotNull('ats_score')->selectRaw("
            COUNT(*) as total,
            ROUND(AVG(ats_score)) as avg_score,
            SUM(CASE WHEN ats_score < 60 THEN 1 ELSE 0 END) as seg_0_59,
            SUM(CASE WHEN ats_score BETWEEN 60 AND 71 THEN 1 ELSE 0 END) as seg_60_71,
            SUM(CASE WHEN ats_score BETWEEN 72 AND 84 THEN 1 ELSE 0 END) as seg_72_84,
            SUM(CASE WHEN ats_score >= 85 THEN 1 ELSE 0 END) as seg_85_100,
            SUM(CASE WHEN ats_score < 60 THEN 1 ELSE 0 END) as low_score_count
        ")->first();

        $total = (int) ($row->total ?? 0);
        if ($total === 0) {
            return ['total' => 0, 'avg' => 0, 'segments' => [], 'low_score_count' => 0];
        }

        $avgScore = (int) ($row->avg_score ?? 0);
        $lowScoreCount = (int) ($row->low_score_count ?? 0);

        $segments = [
            ['label' => '待提升', 'range' => '0-59', 'color' => 'danger', 'count' => (int) $row->seg_0_59],
            ['label' => '合格', 'range' => '60-71', 'color' => 'warning', 'count' => (int) $row->seg_60_71],
            ['label' => '良好', 'range' => '72-84', 'color' => 'info', 'count' => (int) $row->seg_72_84],
            ['label' => '优秀', 'range' => '85-100', 'color' => 'success', 'count' => (int) $row->seg_85_100],
        ];

        foreach ($segments as &$seg) {
            $seg['percent'] = $total > 0 ? (int) round(($seg['count'] / $total) * 100) : 0;
        }
        unset($seg);

        return [
            'total' => $total,
            'avg' => $avgScore,
            'segments' => $segments,
            'low_score_count' => $lowScoreCount,
        ];
    }

    /**
     * 近7日趋势数据（用户注册、面试场次、ATS评分均值）
     *
     * @return array<string, mixed>
     */
    private function get7DayTrend(): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('m/d');
        }

        $startDate = Carbon::today()->subDays(6);

        // 合并3次独立GROUP BY查询为1次UNION ALL
        $trendRows = DB::select("
            SELECT 'users' AS source, DATE(created_at) AS d, COUNT(*) AS c, NULL AS avg_score
            FROM users WHERE created_at >= ? GROUP BY DATE(created_at)
            UNION ALL
            SELECT 'interviews', DATE(created_at), COUNT(*), NULL
            FROM interview_sessions WHERE created_at >= ? GROUP BY DATE(created_at)
            UNION ALL
            SELECT 'ats', DATE(created_at), NULL, AVG(ats_score)
            FROM resumes WHERE created_at >= ? AND ats_score IS NOT NULL GROUP BY DATE(created_at)
        ", [$startDate, $startDate, $startDate]);

        $userTrend = [];
        $interviewTrend = [];
        $atsTrend = [];
        foreach ($trendRows as $row) {
            $key = $row->d;
            if ($row->source === 'users') {
                $userTrend[$key] = (int) $row->c;
            } elseif ($row->source === 'interviews') {
                $interviewTrend[$key] = (int) $row->c;
            } else {
                $atsTrend[$key] = (float) $row->avg_score;
            }
        }

        $userData = [];
        $interviewData = [];
        $atsData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $userData[] = $userTrend[$date] ?? 0;
            $interviewData[] = $interviewTrend[$date] ?? 0;
            $atsData[] = round($atsTrend[$date] ?? 0, 1);
        }

        return [
            'labels' => $days,
            'users' => $userData,
            'interviews' => $interviewData,
            'ats_avg' => $atsData,
        ];
    }
}
