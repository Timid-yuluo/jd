<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ResumeExportTask;
use App\Services\Resume\ResumeExportTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessResumeExportTaskJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        public readonly int $taskId
    ) {
        // 导出任务为 CPU/IO 密集型，独立队列避免阻塞其他业务
        $this->onQueue('export');
    }

    public function handle(ResumeExportTaskService $resumeExportTaskService): void
    {
        $resumeExportTaskService->processTaskById($this->taskId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Resume export task job failed permanently', [
            'task_id' => $this->taskId,
            'error' => $exception->getMessage(),
        ]);

        $task = ResumeExportTask::find($this->taskId);
        if ($task && $task->status !== 'failed') {
            $task->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
