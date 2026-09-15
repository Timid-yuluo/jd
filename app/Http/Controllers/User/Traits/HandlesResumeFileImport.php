<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use App\Models\Resume;
use App\Models\ResumeModule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

trait HandlesResumeFileImport
{
    private function shouldSkipImportedLine(string $line): bool
    {
        return $this->resumeFileImportService->shouldSkipImportedLine($line);
    }

    private function shouldMergeImportedLine(string $previousLine, string $currentLine): bool
    {
        return $this->resumeFileImportService->shouldMergeImportedLine($previousLine, $currentLine);
    }

    private function isPdfOcrAvailable(): bool
    {
        return $this->resumeFileImportService->isPdfOcrAvailable();
    }

    private function findExecutablePath(string $name): ?string
    {
        return $this->resumeFileImportService->findExecutablePath($name);
    }

    private function extractPdfTextWithOcr(string $pdfPath): string
    {
        return $this->resumeFileImportService->extractPdfTextWithOcr($pdfPath);
    }

    /**
     * @param  Collection<int, ResumeModule>  $modules
     */
    private function modulesToRawText($modules): string
    {
        return $this->resumeFileImportService->modulesToRawText($modules);
    }

    /**
     * @param  array<int, array<string, mixed>>  $modules
     */
    private function extractTargetJobFromModules(array $modules): ?string
    {
        return $this->resumeFileImportService->extractTargetJobFromModules($modules);
    }

    /**
     * @return array{text:string,meta:array<string,mixed>}
     */
    private function extractImportedPayload(UploadedFile $file, string $extension): array
    {
        return $this->resumeFileImportService->extractImportedPayload($file, $extension);
    }

    private function extractDocxText(string $path): string
    {
        return $this->resumeFileImportService->extractDocxText($path);
    }

    private function extractDocText(string $path): string
    {
        return $this->resumeFileImportService->extractDocText($path);
    }

    private function extractOfficeTextByLibreOffice(string $path, string $extension): string
    {
        return $this->resumeFileImportService->extractOfficeTextByLibreOffice($path, $extension);
    }

    private function normalizeImportedResumeText(string $text): string
    {
        return $this->resumeFileImportService->normalizeImportedResumeText($text);
    }

    /**
     * @param  array<string, mixed>  $importMeta
     * @return array{
     *   modules:array<int, array<string, mixed>>,
     *   target_job:string,
     *   import_meta:array<string,mixed>
     * }
     */
    private function resolveImportedModules(string $text, array $importMeta): array
    {
        return $this->resumeFileImportService->resolveImportedModules($text, $importMeta, function (string $event, \Throwable $exception, array $context = []): void {
            $this->logUserFacingException($event, $exception, $context);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAiModules(mixed $rawModules): array
    {
        return $this->resumeFileImportService->normalizeAiModules($rawModules);
    }

    public function importDocument(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:'.config('ui.upload.document_max_kb', 10240)],
            'preview_only' => ['nullable', 'boolean'],
        ]);

        $file = $validated['document'];
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'txt');
        $previewOnly = (bool) ($validated['preview_only'] ?? false);

        try {
            $importPayload = $this->extractImportedPayload($file, $extension);
            $text = $this->normalizeImportedResumeText((string) ($importPayload['text'] ?? ''));
            $importMeta = is_array($importPayload['meta'] ?? null) ? $importPayload['meta'] : [];

            if ($text === '') {
                $message = ($extension === 'pdf' && ($importMeta['possible_scanned_pdf'] ?? false) === true)
                    ? (($importMeta['ocr_available'] ?? false) === true
                        ? '当前 PDF 疑似为扫描件，已尝试 OCR 但仍未识别出可用文本。'
                        : '当前 PDF 疑似为扫描件，但服务器暂未启用 OCR 识别能力，请先转为可复制文本的 PDF 或 DOCX 后重试。')
                    : '文件内容为空，未提取到文本。';

                return $this->fail($message);
            }

            $resolved = $this->resolveImportedModules($text, $importMeta);
            $parsed = $resolved['modules'];
            $importMeta = $resolved['import_meta'];
            if (empty($parsed)) {
                return $this->fail('已提取到文本，但暂未识别出可视化模块，请检查 PDF 内容结构后重试。', 422);
            }
            $targetJob = is_string($resolved['target_job'] ?? null)
                ? (string) $resolved['target_job']
                : $this->extractTargetJobFromModules($parsed);
            $moduleStats = collect($parsed)
                ->countBy(static fn (array $module): string => (string) ($module['type'] ?? 'unknown'))
                ->all();

            if ($previewOnly) {
                return $this->respondSuccessPayload([
                    'preview_only' => true,
                    'message' => '已识别导入内容，请确认后再覆盖当前简历。',
                    'modules' => $parsed,
                    'content_raw' => $text,
                    'target_job' => $targetJob,
                    'module_stats' => $moduleStats,
                    'import_meta' => $importMeta,
                ]);
            }

            DB::transaction(function () use ($resume, $text, $parsed, $targetJob): void {
                $resume->update([
                    'content_raw' => $text,
                    'target_job' => $targetJob,
                    'ats_score' => null,
                    'optimized_text' => null,
                ]);

                $resume->modules()->delete();
                foreach ($parsed as $idx => $mod) {
                    $resume->modules()->create([
                        'type' => $mod['type'],
                        'data' => $mod['data'],
                        'sort_order' => $idx,
                    ]);
                }
            });

            $resume->load('modules');

            return $this->respondSuccessPayload([
                'message' => '导入成功，已解析为可视化模块。',
                'modules' => $resume->modules->toArray(),
                'content_raw' => $text,
                'target_job' => $targetJob,
                'ats_score' => $resume->ats_score,
                'import_meta' => $importMeta,
            ]);
        } catch (\Throwable $e) {
            $this->logUserFacingException('resume_import_failed', $e, [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
                'extension' => $extension,
            ]);

            return $this->fail('导入失败，请检查文件格式或稍后重试。', 500);
        }
    }

    public function importDocumentDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:'.config('ui.upload.document_max_kb', 10240)],
        ]);

        $file = $validated['document'];
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'txt');

        try {
            $importPayload = $this->extractImportedPayload($file, $extension);
            $text = $this->normalizeImportedResumeText((string) ($importPayload['text'] ?? ''));
            $importMeta = is_array($importPayload['meta'] ?? null) ? $importPayload['meta'] : [];

            if ($text === '') {
                return $this->fail('文件内容为空或未识别到可解析文本，请尝试另存为可编辑文本后重试。', 422);
            }

            $resolved = $this->resolveImportedModules($text, $importMeta);
            $parsed = $resolved['modules'];
            $importMeta = $resolved['import_meta'];
            $targetJob = is_string($resolved['target_job'] ?? null)
                ? (string) $resolved['target_job']
                : $this->extractTargetJobFromModules($parsed);
            $moduleStats = collect($parsed)
                ->countBy(static fn (array $module): string => (string) ($module['type'] ?? 'unknown'))
                ->all();
            $this->markQuotaConsumptionSuccess($request);

            return $this->respondSuccessPayload([
                'message' => '识别成功，已解析可填充内容。',
                'content_raw' => $text,
                'modules' => $parsed,
                'target_job' => $targetJob,
                'module_stats' => $moduleStats,
                'import_meta' => $importMeta,
            ]);
        } catch (\Throwable $e) {
            $this->logUserFacingException('resume_import_draft_failed', $e, [
                'user_id' => $request->user()?->id,
                'extension' => $extension,
            ]);

            return $this->fail('导入失败，请检查文件格式或稍后重试。', 500);
        }
    }
}
