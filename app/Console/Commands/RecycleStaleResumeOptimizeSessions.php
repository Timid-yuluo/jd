<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ResumeOptimizeSession;
use App\Services\Resume\ResumeOptimizeHealthService;
use App\Services\Resume\ResumeOptimizeSessionService;
use Illuminate\Console\Command;

final class RecycleStaleResumeOptimizeSessions extends Command
{
    protected $signature = 'resume:optimize:recycle-stale
        {--limit=100 : 单次最多处理的超时会话数量}
        {--dry-run : 仅查看待回收会话，不执行更新}';

    protected $description = '自动回收超时未完成的简历优化会话';

    public function handle(
        ResumeOptimizeHealthService $healthService,
        ResumeOptimizeSessionService $sessionService
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $sessions = $healthService->findStalePendingSessions($limit);

        if ($sessions->isEmpty()) {
            $this->info('未发现需要回收的超时优化会话。');

            return self::SUCCESS;
        }

        $rows = $sessions->map(function (ResumeOptimizeSession $session): array {
            $referenceAt = $session->started_at ?? $session->queued_at ?? $session->created_at;
            $ageSeconds = $referenceAt !== null ? $referenceAt->diffInSeconds(now()) : 0;

            return [
                'id' => $session->id,
                'uuid' => $session->uuid,
                'resume_id' => $session->resume_id,
                'user_id' => $session->user_id,
                'status' => $session->status,
                'age_seconds' => $ageSeconds,
            ];
        })->all();

        $this->table(['ID', 'UUID', 'Resume', 'User', 'Status', 'Age(s)'], $rows);

        if ((bool) $this->option('dry-run')) {
            $this->warn('dry-run 模式：未执行任何回收操作。');

            return self::SUCCESS;
        }

        $recycledCount = 0;

        foreach ($sessions as $session) {
            $updated = $sessionService->reconcileSessionState($session);

            if ($updated->status === ResumeOptimizeSession::STATUS_FAILED
                && $updated->error_code === 'QUEUE_STALLED') {
                $recycledCount++;
            }
        }

        $snapshot = $healthService->assessQueueHealth();

        $this->info(sprintf(
            '已回收 %d 条超时优化会话，当前 stale_pending=%d，healthy=%s。',
            $recycledCount,
            (int) ($snapshot['stale_pending_count'] ?? 0),
            (($snapshot['healthy'] ?? false) === true) ? 'true' : 'false'
        ));

        return self::SUCCESS;
    }
}
