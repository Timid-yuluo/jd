<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Application\Actions\JobMatching\AnalyzeJobMatchAction;
use App\Models\JobMatchBatch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessBatchJobMatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $batchId,
        public int $userId,
        public array $lines,
        public ?int $resumeId = null,
    ) {
        // 批量岗位匹配依赖 AI，归入 ai 队列统一调度
        $this->onQueue('ai');
    }

    public function handle(AnalyzeJobMatchAction $action): void
    {
        $batch = JobMatchBatch::find($this->batchId);
        $user = User::find($this->userId);

        if (!$batch || !$user) {
            return;
        }

        foreach ($this->lines as $index => $jd) {
            try {
                $analysis = $action->execute($user, $jd, $this->resumeId);
                $history = $analysis['analysis_history'];
                $history->update(['batch_id' => $batch->id]);
                $batch->increment('completed');
            } catch (\Throwable $e) {
                report($e);
                $batch->increment('failed');
            }
        }

        $batch->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $batch = JobMatchBatch::find($this->batchId);
        if ($batch) {
            $batch->update(['status' => 'failed']);
        }
    }
}
