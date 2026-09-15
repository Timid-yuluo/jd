<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\QuotaUsage;
use App\Models\Resume;
use App\Models\ResumeExportTask;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class ResumeExportHandlerService
{
    /**
     * @return array{
     *     plan_slug: string,
     *     is_free_plan: bool,
     *     can_export_docx: bool,
     *     pdf_used: int,
     *     with_watermark: bool,
     *     should_count_export: bool
     * }
     */
    public function resolveExportPolicy(Request $request): array
    {
        $user = $request->user();
        $plan = $user->currentPlan();
        $planSlug = (string) ($plan?->slug ?? 'free');
        $isFreePlan = $planSlug === 'free';
        $period = QuotaUsage::currentPeriod();
        $pdfUsed = (int) QuotaUsage::query()
            ->where('user_id', (int) $user->id)
            ->where('quota_key', 'export_pdf')
            ->where('period', $period)
            ->value('used');
        return [
            'plan_slug' => $planSlug,
            'is_free_plan' => $isFreePlan,
            'can_export_docx' => ! $isFreePlan,
            'pdf_used' => $pdfUsed,
            'with_watermark' => $isFreePlan && $pdfUsed >= 3,
            'should_count_export' => true,
        ];
    }

    public function normalizeExportTaskId(mixed $taskId): ?string
    {
        if (! is_string($taskId) || trim($taskId) === '') {
            return null;
        }

        $normalized = Str::upper((string) preg_replace('/[^A-Za-z0-9_-]+/', '', $taskId));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  array<int, int>  $resumeIds
     * @return Collection<int, Resume>
     */
    public function queryOrderedResumes(int $userId, array $resumeIds): Collection
    {
        $orderMap = array_flip($resumeIds);

        return Resume::query()
            ->where('user_id', $userId)
            ->whereIn('id', $resumeIds)
            ->get()
            ->sortBy(static fn (Resume $resume): int => $orderMap[$resume->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function mapExportTask(ResumeExportTask $task): array
    {
        $downloadAvailable = $task->status === 'completed'
            && is_string($task->file_path)
            && $task->file_path !== ''
            && File::exists($task->file_path);

        return [
            'task_id' => $task->task_id,
            'type' => $task->type,
            'status' => $task->status,
            'attempt_count' => (int) ($task->attempt_count ?? 0),
            'max_attempts' => (int) ($task->max_attempts ?? config('export.max_attempts', 2)),
            'resume_count' => $task->resume_count,
            'include_optimized' => (bool) $task->include_optimized,
            'position_keywords' => is_array($task->position_keywords) ? $task->position_keywords : [],
            'error_message' => $task->error_message,
            'created_at' => $task->created_at?->toDateTimeString(),
            'finished_at' => $task->finished_at?->toDateTimeString(),
            'retryable' => $task->status === 'failed'
                && (int) ($task->attempt_count ?? 0) < (int) ($task->max_attempts ?? config('export.max_attempts', 2)),
            'download_url' => $downloadAvailable
                ? route('user.resumes.export-tasks.download', ['taskId' => $task->task_id])
                : null,
        ];
    }
}
