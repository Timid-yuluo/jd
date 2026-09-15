<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Resume\ResumeOptimizeHealthService;
use Illuminate\Console\Command;

final class CheckResumeOptimizeHealth extends Command
{
    protected $signature = 'resume:optimize:check-health
        {--json : 以 JSON 输出健康快照}';

    protected $description = '检查简历异步优化队列健康状态';

    public function handle(ResumeOptimizeHealthService $healthService): int
    {
        $snapshot = $healthService->assessQueueHealth();
        $healthy = ($snapshot['healthy'] ?? false) === true;

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['healthy', $healthy ? 'true' : 'false'],
                ['reason', (string) ($snapshot['reason'] ?? '')],
                ['message', (string) ($snapshot['message'] ?? '')],
                ['recent_total', (string) ($snapshot['recent_total'] ?? 0)],
                ['recent_queue_failures', (string) ($snapshot['recent_queue_failures'] ?? 0)],
                ['recent_failure_rate_percent', (string) ($snapshot['recent_failure_rate_percent'] ?? 0)],
                ['stale_pending_count', (string) ($snapshot['stale_pending_count'] ?? 0)],
                ['consecutive_queue_failures', (string) ($snapshot['consecutive_queue_failures'] ?? 0)],
                ['queue_connection', (string) (($snapshot['worker_runtime']['queue_connection'] ?? ''))],
                ['php_binary', (string) (($snapshot['worker_runtime']['php_binary'] ?? ''))],
                ['redis_extension_loaded', (($snapshot['worker_runtime']['redis_extension_loaded'] ?? false) === true) ? 'true' : 'false'],
                ['pcntl_signal_available', (($snapshot['worker_runtime']['pcntl_signal_available'] ?? false) === true) ? 'true' : 'false'],
                ['proc_open_available', (($snapshot['worker_runtime']['proc_open_available'] ?? false) === true) ? 'true' : 'false'],
            ]
        );

        $queueDepths = $snapshot['queue_depths'] ?? [];
        if (is_array($queueDepths) && $queueDepths !== []) {
            $this->table(
                ['Queue', 'Depth'],
                array_map(
                    static fn (string $queue, mixed $depth): array => [$queue, $depth === null ? 'n/a' : (string) $depth],
                    array_keys($queueDepths),
                    array_values($queueDepths)
                )
            );
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
