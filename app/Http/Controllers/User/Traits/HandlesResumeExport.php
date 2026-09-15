<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use App\Jobs\ProcessResumeExportTaskJob;
use App\Models\Resume;
use App\Models\ResumeExportTask;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait HandlesResumeExport
{
    public function printPdf(Request $request, Resume $resume): View
    {
        $this->authorize('view', $resume);

        $resume->load('modules');
        $policy = $this->resumeExportHandlerService->resolveExportPolicy($request);
        $modules = $this->normalizeModulesForPrint($this->buildShowModules($resume));

        return view('user.resumes.print-pdf', [
            'items' => [[
                'resume' => $resume,
                'modules' => $modules,
                'theme' => $resume->theme ?? 'blue',
                'template' => in_array((string) $resume->template, ['classic', 'modern', 'minimal', 'timeline', 'creative', 'elegant'], true)
                    ? (string) $resume->template
                    : 'classic',
            ]],
            'autoPrint' => $request->boolean('auto_print', true),
            'documentTitle' => $resume->title !== '' ? $resume->title : '简历导出',
            'withWatermark' => $policy['with_watermark'],
            'exportPolicy' => $policy,
        ]);
    }

    public function registerPdfDownload(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('view', $resume);

        $this->quotaService->incrementQuotaUsage((int) $request->user()->id, 'export_pdf');
        $policy = $this->resumeExportHandlerService->resolveExportPolicy($request);

        return $this->respondSuccessPayload([
            'with_watermark' => (bool) ($policy['with_watermark'] ?? false),
            'pdf_used' => (int) ($policy['pdf_used'] ?? 0),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $modules
     * @return array<int, array<string, mixed>>
     */
    private function normalizeModulesForPrint(array $modules): array
    {
        foreach ($modules as $index => $module) {
            $data = is_array($module['data'] ?? null) ? $module['data'] : [];
            $avatar = trim((string) ($data['avatar'] ?? ''));

            if ($avatar !== '' && ! preg_match('/^(https?:)?\/\//i', $avatar)) {
                $data['avatar'] = url('/'.ltrim($avatar, '/'));
            }

            $modules[$index]['data'] = $data;
        }

        return $modules;
    }

    public function exportDocx(Request $request, Resume $resume): BinaryFileResponse
    {
        $this->authorize('view', $resume);
        $policy = $this->resumeExportHandlerService->resolveExportPolicy($request);
        if ($policy['can_export_docx'] === false) {
            abort(403, '免费版暂不支持 DOCX 导出，请升级基础版或专业版。');
        }

        $resume->load('modules');
        [$filePath, $downloadName] = $this->resumeExportTaskService->buildSingleDocxFile($resume);

        return response()->download(
            $filePath,
            $downloadName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

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
    private function resolveExportPolicy(Request $request): array
    {
        return $this->resumeExportHandlerService->resolveExportPolicy($request);
    }

    public function createExportTask(Request $request): JsonResponse
    {
        $this->resumeExportTaskService->cleanupExpiredExportTasks();
        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key'));
        if ($idempotencyKey !== '' && ! preg_match('/^[A-Za-z0-9_-]{8,80}$/', $idempotencyKey)) {
            return $this->fail('幂等键格式错误。', 422);
        }

        if ($idempotencyKey !== '') {
            $cachedTaskId = Cache::get('resume:export:idempotency:user:'.$request->user()->id.':'.$idempotencyKey);
            if (is_string($cachedTaskId) && $cachedTaskId !== '') {
                $existing = ResumeExportTask::query()
                    ->where('user_id', $request->user()->id)
                    ->where('task_id', $cachedTaskId)
                    ->first();
                if ($existing !== null) {
                    return $this->respondSuccessPayload([
                        'task' => $this->resumeExportHandlerService->mapExportTask($existing),
                    ]);
                }
            }
        }

        $validated = $request->validate([
            'resume_ids' => ['required', 'array', 'min:1', 'max:100'],
            'resume_ids.*' => ['required', 'integer', 'distinct'],
            'export_task_id' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $resumeIds = array_values(array_unique(array_map('intval', $validated['resume_ids'])));
        $taskId = $this->resumeExportHandlerService->normalizeExportTaskId($validated['export_task_id'] ?? null)
            ?? ('PDF-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)));

        $existingTask = ResumeExportTask::query()
            ->where('user_id', $request->user()->id)
            ->where('task_id', $taskId)
            ->first();
        if ($existingTask !== null) {
            return $this->respondSuccessPayload([
                'task' => $this->resumeExportHandlerService->mapExportTask($existingTask),
            ]);
        }

        $resumes = $this->resumeExportHandlerService->queryOrderedResumes($request->user()->id, $resumeIds);
        if ($resumes->count() !== count($resumeIds)) {
            return $this->fail('所选简历包含不存在或无权限访问的记录，请刷新后重试。', 422);
        }

        $pdfSignature = $this->resumeExportTaskService->buildPdfPayloadSignature($resumes);
        $processingTask = $this->resumeExportTaskService->findProcessingPdfTask((int) $request->user()->id, $pdfSignature);
        if ($processingTask !== null) {
            return $this->respondSuccessPayload([
                'task' => $this->resumeExportHandlerService->mapExportTask($processingTask),
            ]);
        }

        $reusableTask = $this->resumeExportTaskService->findReusablePdfTask((int) $request->user()->id, $pdfSignature);
        if ($reusableTask !== null) {
            return $this->respondSuccessPayload([
                'task' => $this->resumeExportHandlerService->mapExportTask($reusableTask),
            ]);
        }

        $maxAttempts = max(1, (int) config('export.max_attempts', 2));

        $positionKeywords = $resumes
            ->pluck('target_job')
            ->filter(static fn (?string $job): bool => is_string($job) && trim($job) !== '')
            ->map(static fn (string $job): string => trim($job))
            ->unique()
            ->values()
            ->all();

        $task = ResumeExportTask::query()->create([
            'user_id' => $request->user()->id,
            'task_id' => $taskId,
            'type' => 'pdf',
            'status' => 'processing',
            'attempt_count' => 0,
            'max_attempts' => $maxAttempts,
            'resume_ids' => $resumeIds,
            'resume_count' => count($resumeIds),
            'position_keywords' => $positionKeywords,
            'include_optimized' => false,
            'started_at' => now(),
        ]);

        Log::info('resume_export_task_created', [
            'task_id' => $task->task_id,
            'user_id' => $request->user()->id,
            'type' => 'pdf',
            'resume_count' => count($resumeIds),
            'max_attempts' => $maxAttempts,
        ]);

        if (is_string($pdfSignature) && $pdfSignature !== '') {
            $this->resumeExportTaskService->rememberProcessingPdfTask((int) $request->user()->id, $pdfSignature, (string) $task->task_id);
        }

        ProcessResumeExportTaskJob::dispatch((int) $task->id)
            ->onQueue((string) config('export.queue', 'resume-export'));

        if ($idempotencyKey !== '') {
            Cache::put(
                'resume:export:idempotency:user:'.$request->user()->id.':'.$idempotencyKey,
                $task->task_id,
                now()->addMinutes(30)
            );
        }

        return $this->respondSuccessPayload([
            'task' => $this->resumeExportHandlerService->mapExportTask($task),
        ]);
    }

    public function exportTaskStatus(Request $request, string $taskId): JsonResponse
    {
        $safeTaskId = $this->resumeExportHandlerService->normalizeExportTaskId($taskId);
        if ($safeTaskId === null) {
            return $this->fail('任务编号格式错误。', 422);
        }

        $task = ResumeExportTask::query()
            ->where('user_id', $request->user()->id)
            ->where('task_id', $safeTaskId)
            ->first();

        if ($task === null) {
            return $this->fail('导出任务不存在。', 404);
        }

        return $this->respondSuccessPayload([
            'task' => $this->resumeExportHandlerService->mapExportTask($task),
        ]);
    }

    public function downloadExportTask(Request $request, string $taskId): BinaryFileResponse|RedirectResponse
    {
        $safeTaskId = $this->resumeExportHandlerService->normalizeExportTaskId($taskId);
        if ($safeTaskId === null) {
            return redirect()->route('user.resumes.index')->with('error', '下载失败：任务编号格式错误。');
        }

        $task = ResumeExportTask::query()
            ->where('user_id', $request->user()->id)
            ->where('task_id', $safeTaskId)
            ->first();
        if ($task === null || $task->status !== 'completed' || ! is_string($task->file_path) || $task->file_path === '') {
            return redirect()->route('user.resumes.index')->with('error', '下载失败：导出任务未完成或文件不存在。');
        }
        if (! File::exists($task->file_path)) {
            return redirect()->route('user.resumes.index')->with('error', '下载失败：导出文件已失效，请重新导出。');
        }

        return response()->download(
            $task->file_path,
            $task->file_name ?: ('resumes_export_'.$safeTaskId.'.'.($task->type === 'pdf' ? 'pdf' : 'zip')),
            ['Content-Type' => $task->type === 'pdf' ? 'application/pdf' : 'application/zip']
        );
    }
}
