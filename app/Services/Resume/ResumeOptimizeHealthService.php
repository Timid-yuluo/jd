<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\ResumeOptimizeSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Throwable;

final class ResumeOptimizeHealthService
{
    /**
     * @return array<string,mixed>
     */
    public function assessQueueHealth(): array
    {
        $enabled = (bool) config('resume.optimize_session.health_gate.enabled', true);
        $lookbackSeconds = max(60, (int) config('resume.optimize_session.health_gate.lookback_seconds', 300));
        $minSamples = max(1, (int) config('resume.optimize_session.health_gate.min_recent_samples', 5));
        $maxQueueFailures = max(1, (int) config('resume.optimize_session.health_gate.max_queue_failures', 3));
        $maxQueueFailureRate = max(1, (int) config('resume.optimize_session.health_gate.max_queue_failure_rate_percent', 30));
        $maxStalePending = max(1, (int) config('resume.optimize_session.health_gate.max_stale_pending', 3));
        $maxConsecutiveQueueFailures = max(1, (int) config('resume.optimize_session.health_gate.max_consecutive_queue_failures', 3));
        $cutoff = now()->subSeconds($lookbackSeconds);

        $recentSessions = ResumeOptimizeSession::query()
            ->where('created_at', '>=', $cutoff)
            ->orderByDesc('id')
            ->get(['status', 'error_code']);

        $recentTotal = $recentSessions->count();
        $recentQueueFailures = $recentSessions
            ->filter(fn (ResumeOptimizeSession $session): bool => $this->isQueueFailureCode((string) $session->error_code))
            ->count();
        $recentFailureRate = $recentTotal > 0
            ? (int) round(($recentQueueFailures / $recentTotal) * 100)
            : 0;
        $consecutiveQueueFailures = $this->countLeadingQueueFailures($recentSessions);
        $stalePendingCount = $this->stalePendingQuery()->count();
        $queueDepths = $this->collectQueueDepths();
        $workerRuntime = $this->inspectWorkerRuntime();

        $healthy = true;
        if ($enabled) {
            if ($stalePendingCount >= $maxStalePending) {
                $healthy = false;
            } elseif ($consecutiveQueueFailures >= $maxConsecutiveQueueFailures) {
                $healthy = false;
            } elseif ($recentTotal >= $minSamples
                && $recentQueueFailures >= $maxQueueFailures
                && $recentFailureRate >= $maxQueueFailureRate) {
                $healthy = false;
            }
        }

        return [
            'healthy' => $healthy,
            'reason' => $healthy ? '' : 'QUEUE_UNHEALTHY',
            'message' => $healthy ? '' : '后台会话优化分支当前不可用，请稍后重试。',
            'degrade_to_stream' => false,
            'recent_total' => $recentTotal,
            'recent_queue_failures' => $recentQueueFailures,
            'recent_failure_rate_percent' => $recentFailureRate,
            'stale_pending_count' => $stalePendingCount,
            'consecutive_queue_failures' => $consecutiveQueueFailures,
            'queue_depths' => $queueDepths,
            'worker_runtime' => $workerRuntime,
        ];
    }

    /**
     * @return Collection<int,ResumeOptimizeSession>
     */
    public function findStalePendingSessions(int $limit = 100): Collection
    {
        return $this->stalePendingQuery()
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();
    }

    private function stalePendingQuery(): Builder
    {
        $staleAfterSeconds = max(60, (int) config('resume.optimize_session.stale_after_seconds', 300));
        $staleBefore = now()->subSeconds($staleAfterSeconds);

        return ResumeOptimizeSession::query()
            ->whereIn('status', [
                ResumeOptimizeSession::STATUS_QUEUED,
                ResumeOptimizeSession::STATUS_RUNNING,
            ])
            ->where(function (Builder $query) use ($staleBefore): void {
                $query->where('started_at', '<=', $staleBefore)
                    ->orWhere(function (Builder $queuedQuery) use ($staleBefore): void {
                        $queuedQuery->whereNull('started_at')
                            ->where('queued_at', '<=', $staleBefore);
                    });
            });
    }

    /**
     * @return array<string,int|null>
     */
    private function collectQueueDepths(): array
    {
        $connection = (string) config('queue.default', 'database');
        $depths = [];

        foreach ($this->configuredQueueNames() as $queueName) {
            try {
                $depths[$queueName] = Queue::connection($connection)->size($queueName);
            } catch (Throwable) {
                $depths[$queueName] = null;
            }
        }

        return $depths;
    }

    /**
     * @return array<string,mixed>
     */
    private function inspectWorkerRuntime(): array
    {
        $disabledFunctions = array_values(array_filter(array_map(
            static fn (string $function): string => trim($function),
            explode(',', (string) ini_get('disable_functions'))
        )));

        return [
            'php_binary' => PHP_BINARY,
            'queue_connection' => (string) config('queue.default', 'database'),
            'redis_extension_loaded' => extension_loaded('redis'),
            'pcntl_extension_loaded' => extension_loaded('pcntl'),
            'pcntl_signal_available' => function_exists('pcntl_signal'),
            'pcntl_signal_dispatch_available' => function_exists('pcntl_signal_dispatch'),
            'proc_open_available' => function_exists('proc_open'),
            'disabled_functions' => $disabledFunctions,
        ];
    }

    /**
     * @return array<int,string>
     */
    private function configuredQueueNames(): array
    {
        $queues = [
            trim((string) config('resume.optimize_session.default_queue', 'default')),
            trim((string) config('resume.optimize_session.priority_queue', 'default')),
            trim((string) config('resume.optimize_session.heavy_queue', 'optimize-heavy')),
        ];

        return array_values(array_unique(array_filter($queues, static fn (string $queue): bool => $queue !== '')));
    }

    private function isQueueFailureCode(string $errorCode): bool
    {
        return in_array($errorCode, [
            'QUEUE_JOB_FAILED',
            'QUEUE_STALLED',
            'QUEUE_DISPATCH_FAILED',
        ], true);
    }

    /**
     * @param  Collection<int,ResumeOptimizeSession>  $sessions
     */
    private function countLeadingQueueFailures(Collection $sessions): int
    {
        $count = 0;

        foreach ($sessions as $session) {
            if (! $this->isQueueFailureCode((string) $session->error_code)) {
                break;
            }

            $count++;
        }

        return $count;
    }
}
