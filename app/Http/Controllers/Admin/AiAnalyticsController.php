<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UsageLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AiAnalyticsController extends Controller
{
    public function index(): View
    {
        $days = request()->input('days', 7);
        $days = in_array((int) $days, [7, 30, 90]) ? (int) $days : 7;

        $cacheKey = "ai_analytics:dashboard:{$days}";

        $data = Cache::remember($cacheKey, 300, fn () => $this->computeAnalytics($days));

        return view('admin.ai-analytics.index', array_merge(['days' => $days], $data));
    }

    /**
     * 计算 AI 分析数据（被缓存 5 分钟）
     *
     * @return array<string, mixed>
     */
    private function computeAnalytics(int $days): array
    {
        $startDate = Carbon::today()->subDays($days - 1);

        // Daily usage stats
        $dailyStats = UsageLog::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(prompt_tokens + completion_tokens) as tokens'),
                DB::raw('SUM(cost_micros) as cost'),
                DB::raw('AVG(latency_ms) as avg_latency')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $dates = [];
        $callsData = [];
        $tokensData = [];
        $costData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $dates[] = Carbon::parse($date)->format('m-d');

            $stat = $dailyStats->firstWhere('date', $date);
            $callsData[] = $stat ? (int) $stat->calls : 0;
            $tokensData[] = $stat ? (int) $stat->tokens : 0;
            $costData[] = $stat ? (int) $stat->cost : 0;
        }

        // Scenario distribution
        $scenarioStats = UsageLog::query()
            ->select('scenario', DB::raw('COUNT(*) as count'), DB::raw('SUM(prompt_tokens + completion_tokens) as tokens'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('scenario')
            ->orderByDesc('count')
            ->get();

        // Provider distribution
        $providerStats = UsageLog::query()
            ->select('provider', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('provider')
            ->orderByDesc('count')
            ->get();

        // Summary
        $summary = [
            'total_calls' => UsageLog::where('created_at', '>=', $startDate)->count(),
            'total_tokens' => (int) UsageLog::where('created_at', '>=', $startDate)->sum(DB::raw('prompt_tokens + completion_tokens')),
            'total_cost' => (int) UsageLog::where('created_at', '>=', $startDate)->sum('cost_micros'),
            'avg_latency' => round((float) (UsageLog::where('created_at', '>=', $startDate)->avg('latency_ms') ?: 0)),
        ];

        return compact('dates', 'callsData', 'tokensData', 'costData', 'scenarioStats', 'providerStats', 'summary');
    }
}
