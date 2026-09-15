<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Resume\ResumeOptimizeSessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class OptimizeResumeSessionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public int $timeout = 120;

    public function __construct(
        public readonly int $sessionId
    ) {
        // AI 简历优化为耗时任务，独立 ai 队列便于资源隔离与并发控制
        $this->onQueue('ai');
    }

    /**
     * @return array<int,int>
     */
    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function handle(ResumeOptimizeSessionService $service): void
    {
        // 幂等检查：仅处理 pending 状态的 session，防止重试导致重复处理
        $session = \App\Models\ResumeOptimizeSession::find($this->sessionId);
        if (! $session || $session->status !== 'pending') {
            return;
        }

        $service->processSessionById($this->sessionId);
    }

    public function failed(?Throwable $exception): void
    {
        app(ResumeOptimizeSessionService::class)->markSessionFailedAfterQueueFailure($this->sessionId, $exception);
    }
}
