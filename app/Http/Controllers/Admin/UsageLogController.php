<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UsageLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class UsageLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = UsageLog::with('user')->latest('id');

        if ($scenario = $request->input('scenario')) {
            $query->where('scenario', $scenario);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom.' 00:00:00');
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        $logs = $query->paginate((int) config('ui.pagination.admin_table', 20))->appends($request->only('scenario', 'user_id', 'date_from', 'date_to'));

        $scenarios = UsageLog::query()
            ->distinct('scenario')
            ->pluck('scenario')
            ->sort()
            ->values();

        // 统计数据缓存5分钟（percentile 查询较重，避免频繁执行）
        $summary = Cache::remember('usage-logs:summary', (int) config('cache_ttl.ttl.standard', 300), function () {
            $agg = UsageLog::query()
                ->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(prompt_tokens) as total_prompt_tokens,
                    SUM(completion_tokens) as total_completion_tokens,
                    SUM(cost_micros) as total_cost_micros,
                    ROUND(AVG(latency_ms)) as avg_latency,
                    MAX(latency_ms) as max_latency
                ')
                ->first();

            return [
                'total_calls' => (int) ($agg->total_calls ?? 0),
                'total_prompt_tokens' => (int) ($agg->total_prompt_tokens ?? 0),
                'total_completion_tokens' => (int) ($agg->total_completion_tokens ?? 0),
                'total_tokens' => (int) ($agg->total_prompt_tokens ?? 0) + (int) ($agg->total_completion_tokens ?? 0),
                'total_cost_micros' => (float) ($agg->total_cost_micros ?? 0),
                'avg_latency' => (int) ($agg->avg_latency ?? 0),
                'max_latency' => (int) ($agg->max_latency ?? 0),
                'p95_latency' => $this->percentile('latency_ms', 95),
            ];
        });

        $byScenario = Cache::remember('usage-logs:by-scenario', (int) config('cache_ttl.ttl.standard', 300), fn () => UsageLog::query()
            ->selectRaw('
                scenario,
                count(*) as calls,
                sum(prompt_tokens) as prompt_tokens,
                sum(completion_tokens) as completion_tokens,
                sum(cost_micros) as cost_micros,
                round(avg(latency_ms)) as avg_latency
            ')
            ->groupBy('scenario')
            ->orderByDesc('calls')
            ->get()
            ->toArray());

        $byProvider = Cache::remember('usage-logs:by-provider', (int) config('cache_ttl.ttl.standard', 300), fn () => UsageLog::query()
            ->selectRaw('
                provider,
                count(*) as calls,
                sum(prompt_tokens) as prompt_tokens,
                sum(completion_tokens) as completion_tokens,
                sum(cost_micros) as cost_micros,
                round(avg(latency_ms)) as avg_latency
            ')
            ->groupBy('provider')
            ->orderByDesc('calls')
            ->get()
            ->toArray());

        $dailyTrend = Cache::remember('usage-logs:daily-trend', (int) config('cache_ttl.ttl.standard', 300), fn () => $this->getDailyTrend(14));

        // 提取图表数据
        $trendCalls = array_column($dailyTrend, 'calls');
        $trendCosts = array_map(fn ($item) => round($item['cost_micros'] / 1000000, 4), $dailyTrend);
        $trendLabels = array_column($dailyTrend, 'label');

        $todayStats = Cache::remember('usage-logs:today', (int) config('cache_ttl.ttl.short', 60), function () {
            $today = now()->startOfDay();
            $agg = UsageLog::query()
                ->selectRaw('
                    COUNT(*) as calls,
                    SUM(prompt_tokens) as prompt_tokens,
                    SUM(completion_tokens) as completion_tokens,
                    SUM(cost_micros) as cost_micros,
                    ROUND(AVG(latency_ms)) as avg_latency
                ')
                ->where('created_at', '>=', $today)
                ->first();

            return [
                'calls' => (int) ($agg->calls ?? 0),
                'prompt_tokens' => (int) ($agg->prompt_tokens ?? 0),
                'completion_tokens' => (int) ($agg->completion_tokens ?? 0),
                'cost_micros' => (float) ($agg->cost_micros ?? 0),
                'avg_latency' => (int) ($agg->avg_latency ?? 0),
            ];
        });

        return view('admin.usage-logs.index', compact(
            'logs', 'scenarios', 'summary', 'byScenario', 'byProvider', 'dailyTrend', 'todayStats',
            'trendCalls', 'trendCosts', 'trendLabels'
        ));
    }

    public function show(UsageLog $usage_log): View
    {
        $usage_log->load('user');

        return view('admin.usage-logs.show', compact('usage_log'));
    }

    /**
     * 计算百分位值
     */
    private function percentile(string $column, int $percentile): int
    {
        $count = UsageLog::count();
        if ($count === 0) {
            return 0;
        }

        $offset = (int) floor($count * $percentile / 100);
        $value = UsageLog::query()
            ->orderBy($column)
            ->skip($offset)
            ->value($column);

        return (int) ($value ?? 0);
    }

    /**
     * 获取最近 N 天的每日趋势（聚合查询替代循环）
     */
    private function getDailyTrend(int $days): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $dailyStats = UsageLog::query()
            ->selectRaw('
                DATE(created_at) as date,
                count(*) as calls,
                sum(prompt_tokens) as prompt_tokens,
                sum(completion_tokens) as completion_tokens,
                sum(cost_micros) as cost_micros,
                round(avg(latency_ms)) as avg_latency
            ')
            ->where('created_at', '>=', $startDate)
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('date');

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $stats = $dailyStats->get($dateKey);

            $trend[] = [
                'date' => $dateKey,
                'label' => $date->format('m-d'),
                'calls' => (int) ($stats?->calls ?? 0),
                'prompt_tokens' => (int) ($stats?->prompt_tokens ?? 0),
                'completion_tokens' => (int) ($stats?->completion_tokens ?? 0),
                'cost_micros' => (int) ($stats?->cost_micros ?? 0),
                'avg_latency' => (int) ($stats?->avg_latency ?? 0),
            ];
        }

        return $trend;
    }
}
