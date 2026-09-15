<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\Resume;
use App\Models\ResumeExportTask;
use App\Services\Notification\UserNotificationService;
use App\Services\Resume\Builders\ResumeDocxBuilder;
use App\Services\Resume\Builders\ResumePdfBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ResumeExportTaskService
{
    private ResumeDocxBuilder $docxBuilder;

    private ResumePdfBuilder $pdfBuilder;

    public function __construct()
    {
        $directory = $this->resolveExportDirectory();
        $this->docxBuilder = new ResumeDocxBuilder($directory);
        $this->pdfBuilder = new ResumePdfBuilder($directory);
    }

    public function isPdfExportAvailable(): bool
    {
        return true;
    }

    public function pdfDriverHint(): string
    {
        return 'browsershot (已安装)';
    }

    public function buildPdfPayloadSignature(Collection $resumes): string
    {
        $parts = $resumes
            ->sortBy(static fn (Resume $resume): int => (int) $resume->id)
            ->map(function (Resume $resume): string {
                return implode('|', [
                    (string) $resume->id,
                    (string) ($resume->updated_at?->getTimestamp() ?? 0),
                    (string) ($resume->template ?? ''),
                    (string) ($resume->theme ?? ''),
                ]);
            })
            ->implode(';');

        return sha1($parts.'|'.$this->pdfBuilder->pdfRendererSignature());
    }

    public function findReusablePdfTask(int $userId, string $signature): ?ResumeExportTask
    {
        $taskId = Cache::get($this->pdfCompletedTaskCacheKey($userId, $signature));
        if (! is_string($taskId) || $taskId === '') {
            return null;
        }

        $task = ResumeExportTask::query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('type', 'pdf')
            ->where('status', 'completed')
            ->first();

        if ($task === null || ! is_string($task->file_path) || $task->file_path === '' || ! File::exists($task->file_path)) {
            Cache::forget($this->pdfCompletedTaskCacheKey($userId, $signature));

            return null;
        }

        return $task;
    }

    public function findProcessingPdfTask(int $userId, string $signature): ?ResumeExportTask
    {
        $taskId = Cache::get($this->pdfProcessingTaskCacheKey($userId, $signature));
        if (! is_string($taskId) || $taskId === '') {
            return null;
        }

        $task = ResumeExportTask::query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('type', 'pdf')
            ->where('status', 'processing')
            ->first();

        if ($task === null) {
            Cache::forget($this->pdfProcessingTaskCacheKey($userId, $signature));

            return null;
        }

        return $task;
    }

    public function rememberProcessingPdfTask(int $userId, string $signature, string $taskId): void
    {
        Cache::put(
            $this->pdfProcessingTaskCacheKey($userId, $signature),
            $taskId,
            now()->addMinutes(max(5, (int) config('export.pdf.processing_ttl_minutes', 15)))
        );
    }

    public function buildSingleDocxFile(Resume $resume): array
    {
        return $this->docxBuilder->buildSingleDocxFile($resume);
    }

    /**
     * @param  Collection<int, Resume>  $resumes
     * @return array{0:string,1:string}
     */
    public function buildBatchDocxZipFile(Collection $resumes, string $taskId): array
    {
        return $this->docxBuilder->buildBatchDocxZipFile($resumes, $taskId);
    }

    public function safeExportBaseName(string $title, int $fallbackId): string
    {
        return $this->docxBuilder->safeExportBaseName($title, $fallbackId);
    }

    public function safeZipFolderName(string $name): string
    {
        return $this->docxBuilder->safeZipFolderName($name);
    }

    public function buildBatchDocxEntryName(Resume $resume, int $sequence): string
    {
        return $this->docxBuilder->buildBatchDocxEntryName($resume, $sequence);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function docxBatchManifestJson(string $taskId, int $count, string $rootFolder, array $entries): string
    {
        return $this->docxBuilder->docxBatchManifestJson($taskId, $count, $rootFolder, $entries);
    }

    public function processTaskById(int $taskId): void
    {
        $task = ResumeExportTask::query()->find($taskId);
        if ($task === null || ! in_array((string) $task->type, ['zip', 'pdf'], true)) {
            return;
        }

        $resumeIds = is_array($task->resume_ids)
            ? array_values(array_unique(array_map('intval', $task->resume_ids)))
            : [];
        $resumes = $this->queryOrderedResumes((int) $task->user_id, $resumeIds);
        if ($resumes->count() !== count($resumeIds)) {
            $task->update([
                'status' => 'failed',
                'error_message' => '部分简历已不存在或无权限访问，导出终止。',
                'last_error_at' => now(),
                'finished_at' => now(),
            ]);

            return;
        }

        if ($task->type === 'zip') {
            $this->processZipExportTaskWithRetries($task, $resumes, (bool) $task->include_optimized);

            return;
        }

        $signature = $this->buildPdfPayloadSignature($resumes);
        $this->processPdfExportTaskWithRetries($task, $resumes, $signature);
    }

    public function cleanupExpiredExportTasks(): void
    {
        $retentionDays = max(1, (int) config('export.retention_days', 7));
        $expiredAt = now()->subDays($retentionDays);

        $expiredTasks = ResumeExportTask::query()
            ->where(function ($query) use ($expiredAt): void {
                $query
                    ->where('created_at', '<', $expiredAt)
                    ->orWhere(function ($subQuery) use ($expiredAt): void {
                        $subQuery
                            ->whereNotNull('finished_at')
                            ->where('finished_at', '<', $expiredAt);
                    });
            })
            ->limit((int) config('ui.limit.export_batch', 50))
            ->get();

        foreach ($expiredTasks as $task) {
            if (is_string($task->file_path) && $task->file_path !== '' && File::exists($task->file_path)) {
                File::delete($task->file_path);
            }
            $task->delete();
        }
    }

    /**
     * @param  Collection<int, Resume>  $resumes
     */
    private function processZipExportTaskWithRetries(
        ResumeExportTask $task,
        Collection $resumes,
        bool $includeOptimized
    ): ResumeExportTask {
        return $this->processTaskWithRetries(
            task: $task,
            builder: fn (): array => $this->buildZipExportFile($task->task_id, $resumes, $includeOptimized)
        );
    }

    /**
     * @param  Collection<int, Resume>  $resumes
     */
    private function processPdfExportTaskWithRetries(ResumeExportTask $task, Collection $resumes, string $signature): ResumeExportTask
    {
        return $this->processTaskWithRetries(
            task: $task,
            builder: fn (): array => $this->pdfBuilder->buildPdfExportFile($task->task_id, $resumes),
            onCompleted: function () use ($task, $signature): void {
                $ttl = max(10, (int) config('export.pdf.reuse_ttl_minutes', 10080));
                Cache::put(
                    $this->pdfCompletedTaskCacheKey((int) $task->user_id, $signature),
                    $task->task_id,
                    now()->addMinutes($ttl)
                );
                Cache::forget($this->pdfProcessingTaskCacheKey((int) $task->user_id, $signature));
            },
            onFailed: function () use ($task, $signature): void {
                Cache::forget($this->pdfProcessingTaskCacheKey((int) $task->user_id, $signature));
            }
        );
    }

    /**
     * @param  callable():array{0:string,1:string}  $builder
     */
    private function processTaskWithRetries(
        ResumeExportTask $task,
        callable $builder,
        ?callable $onCompleted = null,
        ?callable $onFailed = null
    ): ResumeExportTask {
        $maxAttempts = max(1, (int) ($task->max_attempts ?? config('export.max_attempts', 2)));
        $lock = Cache::lock('resume:export:task:'.$task->task_id, 30);
        if (! $lock->get()) {
            return $task->fresh();
        }

        try {
            $attempt = (int) ($task->attempt_count ?? 0) + 1;
            while ($attempt <= $maxAttempts) {
                try {
                    [$filePath, $fileName] = $builder();
                    $task->update([
                        'status' => 'completed',
                        'attempt_count' => $attempt,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'error_message' => null,
                        'last_error_at' => null,
                        'finished_at' => now(),
                    ]);

                    Log::info('resume_export_task_completed', [
                        'task_id' => $task->task_id,
                        'user_id' => $task->user_id,
                        'type' => $task->type,
                        'attempt_count' => $attempt,
                    ]);
                    if ($onCompleted !== null) {
                        $onCompleted();
                    }

                    // 通知用户导出任务已完成
                    $this->notifyExportTaskCompleted($task);

                    return $task->fresh();
                } catch (\Throwable $exception) {
                    $message = Str::limit($exception->getMessage(), 240, '...');
                    $task->update([
                        'status' => 'failed',
                        'attempt_count' => $attempt,
                        'error_message' => $message,
                        'last_error_at' => now(),
                        'finished_at' => now(),
                    ]);

                    Log::warning('resume_export_task_failed', [
                        'task_id' => $task->task_id,
                        'user_id' => $task->user_id,
                        'type' => $task->type,
                        'attempt_count' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'error' => $message,
                    ]);
                    if ($onFailed !== null) {
                        $onFailed();
                    }

                    // 通知用户导出任务失败（仅在所有重试均失败时）
                    if ($attempt >= $maxAttempts) {
                        $this->notifyExportTaskFailed($task, $message);
                    }
                }

                $attempt++;
            }
        } finally {
            $lock->release();
        }

        return $task->fresh();
    }

    /**
     * @return Collection<int, Resume>
     */
    private function queryOrderedResumes(int $userId, array $resumeIds): Collection
    {
        $orderMap = array_flip($resumeIds);

        return Resume::query()
            ->where('user_id', $userId)
            ->whereIn('id', $resumeIds)
            ->with('modules')
            ->get()
            ->sortBy(static fn (Resume $resume): int => $orderMap[$resume->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * 通知用户导出任务已完成
     */
    private function notifyExportTaskCompleted(ResumeExportTask $task): void
    {
        try {
            $downloadUrl = route('user.resumes.export.download', ['task' => $task->task_id]);
            $typeLabel = $task->type === 'pdf' ? 'PDF' : 'ZIP';

            app(UserNotificationService::class)->createSiteNotification(
                (int) $task->user_id,
                '简历导出完成',
                sprintf(
                    '你的 %s 导出任务已完成，共 %d 份简历。<br><a href="%s">点击下载文件</a>',
                    $typeLabel,
                    is_array($task->resume_ids) ? count($task->resume_ids) : 0,
                    e($downloadUrl),
                ),
                'feature',
                'site',
            );
        } catch (\Throwable $e) {
            Log::debug('Export completion notification failed', [
                'task_id' => $task->task_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 通知用户导出任务失败
     */
    private function notifyExportTaskFailed(ResumeExportTask $task, string $errorMessage): void
    {
        try {
            $exportUrl = route('user.resumes.export.index');
            $typeLabel = $task->type === 'pdf' ? 'PDF' : 'ZIP';

            app(UserNotificationService::class)->createSiteNotification(
                (int) $task->user_id,
                '简历导出失败',
                sprintf(
                    '你的 %s 导出任务未能完成，错误原因：%s。<br><a href="%s">前往导出页面重试</a>',
                    $typeLabel,
                    e(Str::limit($errorMessage, 120)),
                    e($exportUrl),
                ),
                'warning',
                'site',
            );
        } catch (\Throwable $e) {
            Log::debug('Export failure notification failed', [
                'task_id' => $task->task_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  Collection<int, Resume>  $resumes
     * @return array{0: string, 1: string}
     */
    private function buildZipExportFile(string $taskId, Collection $resumes, bool $includeOptimized): array
    {
        $directory = $this->resolveExportDirectory();

        $downloadName = 'resumes_export_'.$taskId.'.zip';
        $zipPath = $directory.DIRECTORY_SEPARATOR.$downloadName;

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('导出失败：压缩包创建失败。');
        }

        $manifestLines = [
            '导出说明',
            '------------------------------',
            '导出时间: '.now()->format('Y-m-d H:i:s'),
            '导出数量: '.$resumes->count(),
            '任务编号: '.$taskId,
            '导出范围: '.($includeOptimized ? '原文 + 优化版（若存在）' : '仅原文'),
            '',
            '文件清单:',
        ];

        foreach ($resumes as $index => $resume) {
            $safeTitle = Str::of(Str::ascii($resume->title))
                ->lower()
                ->replaceMatches('/[^a-z0-9\-_]+/', '_')
                ->trim('_')
                ->value();
            $safeTitle = $safeTitle !== '' ? $safeTitle : 'resume_'.$resume->id;
            $prefix = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

            $rawText = implode("\n", [
                '简历标题: '.$resume->title,
                '目标岗位: '.($resume->target_job ?: '未填写'),
                '更新时间: '.$resume->updated_at->format('Y-m-d H:i:s'),
                '',
                (string) $resume->content_raw,
            ]);
            $rawFilename = "{$prefix}_{$safeTitle}_raw.txt";
            $zip->addFromString($rawFilename, $rawText);
            $manifestLines[] = "- {$rawFilename}";

            if ($includeOptimized && filled($resume->optimized_text)) {
                $optimizedText = implode("\n", [
                    '简历标题: '.$resume->title,
                    '目标岗位: '.($resume->target_job ?: '未填写'),
                    '优化版本: AI 优化结果',
                    '更新时间: '.$resume->updated_at->format('Y-m-d H:i:s'),
                    '',
                    (string) $resume->optimized_text,
                ]);
                $optimizedFilename = "{$prefix}_{$safeTitle}_optimized.txt";
                $zip->addFromString($optimizedFilename, $optimizedText);
                $manifestLines[] = "- {$optimizedFilename}";
            }
        }

        $zip->addFromString('README.txt', implode("\n", $manifestLines));
        $zip->close();

        return [$zipPath, $downloadName];
    }

    private function pdfCompletedTaskCacheKey(int $userId, string $signature): string
    {
        return 'resume:export:pdf:completed:user:'.$userId.':'.$signature;
    }

    private function pdfProcessingTaskCacheKey(int $userId, string $signature): string
    {
        return 'resume:export:pdf:processing:user:'.$userId.':'.$signature;
    }

    private function resolveExportDirectory(): string
    {
        $configuredDirectory = trim((string) config('export.working_directory', ''));
        $preferredDirectories = array_filter([
            $configuredDirectory !== '' ? rtrim($configuredDirectory, DIRECTORY_SEPARATOR) : '',
            storage_path('app/resume_exports'),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'resume_exports',
        ], static fn (string $path): bool => $path !== '');

        foreach ($preferredDirectories as $directory) {
            if ($this->ensureWritableDirectory($directory)) {
                return $directory;
            }
        }

        throw new \RuntimeException('导出目录不可写，请检查 RESUME_EXPORT_WORKING_DIRECTORY 或系统临时目录权限。');
    }

    private function ensureWritableDirectory(string $directory): bool
    {
        if (File::exists($directory)) {
            return is_dir($directory) && is_writable($directory);
        }

        try {
            File::makeDirectory($directory, 0755, true);
        } catch (\Throwable) {
            return false;
        }

        return is_dir($directory) && is_writable($directory);
    }
}
