<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\InterviewQuestion;
use App\Models\UsageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AiConfigDashboardService
{
    /**
     * @return array<string,mixed>
     */
    public function buildMonitor(): array
    {
        $evalQueue = (string) config('interview.evaluation_queue', 'default');
        $recentWindowStart = now()->subHours(24);
        $aiUsage24h = UsageLog::query()->where('created_at', '>=', $recentWindowStart);

        $queueBacklog = 0;
        $failedJobs = 0;
        $queueRate = 0;
        if (Schema::hasTable('jobs')) {
            $queueBacklog = DB::table('jobs')->where('queue', $evalQueue)->count();
            $processedInLastHour = DB::table('jobs')
                ->where('queue', $evalQueue)
                ->where('created_at', '<', now()->subMinutes(5))
                ->count();
            $queueRate = round($processedInLastHour / 60, 1);
        }
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')
                ->where('payload', 'like', '%EvaluateInterviewAnswerJob%')
                ->count();
        }

        $eta = null;
        if ($queueRate > 0 && $queueBacklog > 0) {
            $etaMinutes = ceil($queueBacklog / $queueRate);
            $eta = $etaMinutes < 60 ? $etaMinutes.' 分钟' : round($etaMinutes / 60, 1).' 小时';
        } elseif ($queueBacklog === 0) {
            $eta = '已完成';
        } else {
            $eta = '未知';
        }

        return [
            'queue_name' => $evalQueue,
            'pending_evaluations' => InterviewQuestion::query()
                ->whereNotNull('answer')
                ->whereNull('score')
                ->count(),
            'queue_backlog' => $queueBacklog,
            'failed_jobs' => $failedJobs,
            'calls_24h' => (clone $aiUsage24h)->count(),
            'avg_latency_24h' => round((float) ((clone $aiUsage24h)->avg('latency_ms') ?? 0)),
            'last_call_at' => UsageLog::query()->latest('id')->value('created_at'),
            'queue_rate' => $queueRate,
            'eta' => $eta,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function calculateCostEstimate(): array
    {
        $recentWindowStart = now()->subHours(24);
        $usageStats = UsageLog::query()
            ->where('created_at', '>=', $recentWindowStart)
            ->selectRaw('provider, COUNT(*) as calls, SUM(prompt_tokens) as input_tokens, SUM(completion_tokens) as output_tokens, SUM(cost_micros) as total_cost_micros')
            ->groupBy('provider')
            ->get()
            ->keyBy('provider');

        $byProvider = [];
        $total = 0;

        foreach (['deepseek', 'volcano', 'zhipu'] as $provider) {
            $stats = $usageStats->get($provider);
            $calls = $stats ? $stats->calls : 0;
            $inputTokens = $stats ? (int) ($stats->input_tokens ?? 0) : 0;
            $outputTokens = $stats ? (int) ($stats->output_tokens ?? 0) : 0;
            $cost = $stats ? ((int) ($stats->total_cost_micros ?? 0)) / 1_000_000 : 0.0;

            $byProvider[$provider] = [
                'name' => config("ai.providers.{$provider}.name", $provider),
                'calls' => $calls,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'amount' => $cost,
            ];

            $total += $cost;
        }

        $limit = config('ai.cost_limit_daily', 100);
        $alert = $total > $limit;

        return [
            'by_provider' => $byProvider,
            'total' => $total,
            'limit' => $limit,
            'alert' => $alert,
            'alert_message' => $alert ? "24h 成本 ¥{$total} 已超过日限额 ¥{$limit}" : null,
        ];
    }
}
