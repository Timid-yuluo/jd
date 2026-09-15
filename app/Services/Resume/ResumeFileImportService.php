<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Infrastructure\AI\AiManager;
use App\Models\ResumeModule;
use App\Services\Resume\Parsing\ResumeModuleParserService;
use App\Support\Resume\ResumeModuleSerializer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class ResumeFileImportService
{
    public function __construct(
        private readonly ResumeModuleParserService $moduleParserService,
        private readonly AiManager $aiManager,
    ) {}

    public function shouldSkipImportedLine(string $line): bool
    {
        if ($line === '') {
            return false;
        }

        return preg_match('/^(第\s*\d+\s*页(\s*\/\s*共\s*\d+\s*页)?|page\s*\d+(\s*of\s*\d+)?)$/iu', $line) === 1;
    }

    public function shouldMergeImportedLine(string $previousLine, string $currentLine): bool
    {
        if ($previousLine === '' || $currentLine === '') {
            return false;
        }

        if ($this->matchSectionHeader($currentLine) !== null || $this->isListLikeLine($currentLine)) {
            return false;
        }

        if (preg_match('/[。！？!?；;:：]$/u', $previousLine)) {
            return false;
        }

        if ($this->isDateLikePart($currentLine) || preg_match('/^(电话|手机|邮箱|邮件|地址|所在地|现居地|Location)[:：]/ui', $currentLine)) {
            return false;
        }

        return mb_strlen($previousLine) >= 12;
    }

    public function isPdfOcrAvailable(): bool
    {
        return $this->findExecutablePath('tesseract') !== null
            && ($this->findExecutablePath('pdftoppm') !== null || $this->findExecutablePath('pdftocairo') !== null);
    }

    public function findExecutablePath(string $name): ?string
    {
        $finder = new ExecutableFinder;
        $path = $finder->find($name);

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function extractPdfTextWithOcr(string $pdfPath, int $maxPages = 5): string
    {
        $tesseractPath = $this->findExecutablePath('tesseract');
        $pdfToImagePath = $this->findExecutablePath('pdftoppm') ?? $this->findExecutablePath('pdftocairo');

        if ($tesseractPath === null || $pdfToImagePath === null) {
            return '';
        }

        $tempDir = storage_path('app/tmp/resume-import-ocr-'.Str::uuid());
        File::ensureDirectoryExists($tempDir);
        $imagePrefix = $tempDir.DIRECTORY_SEPARATOR.'page';

        try {
            $renderCommand = str_contains(basename($pdfToImagePath), 'pdftocairo')
                ? [$pdfToImagePath, '-png', '-r', '200', '-l', (string) $maxPages, $pdfPath, $imagePrefix]
                : [$pdfToImagePath, '-png', '-r', '200', '-l', (string) $maxPages, $pdfPath, $imagePrefix];

            $renderProcess = new Process($renderCommand);
            $renderProcess->setTimeout(60);
            $renderProcess->mustRun();

            $imagePaths = glob($tempDir.DIRECTORY_SEPARATOR.'page*.png') ?: [];
            sort($imagePaths);
            if ($imagePaths === []) {
                return '';
            }

            // 限制 OCR 处理页数，防止多页 PDF 长时间阻塞
            $imagePaths = array_slice($imagePaths, 0, $maxPages);

            $texts = [];
            $totalStartTime = microtime(true);
            foreach ($imagePaths as $imagePath) {
                // 总处理时间超过 90 秒则停止
                if ((microtime(true) - $totalStartTime) > 90) {
                    break;
                }

                $ocrProcess = new Process([$tesseractPath, $imagePath, 'stdout', '--psm', '6']);
                $ocrProcess->setTimeout(60);
                $ocrProcess->run();

                if (! $ocrProcess->isSuccessful()) {
                    continue;
                }

                $output = trim($ocrProcess->getOutput());
                if ($output !== '') {
                    $texts[] = $output;
                }
            }

            return implode("\n\n", $texts);
        } catch (ProcessFailedException) {
            return '';
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * @param  Collection<int, ResumeModule>  $modules
     */
    public function modulesToRawText($modules): string
    {
        return ResumeModuleSerializer::toRawText(
            $modules->map(fn ($m) => ['type' => (string) $m->type, 'data' => is_array($m->data) ? $m->data : []])->values()->all()
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $modules
     */
    public function extractTargetJobFromModules(array $modules): ?string
    {
        foreach ($modules as $module) {
            $type = isset($module['type']) && is_string($module['type']) ? $module['type'] : '';
            if ($type !== 'objective') {
                continue;
            }

            $data = isset($module['data']) && is_array($module['data']) ? $module['data'] : [];
            $targetJob = isset($data['target_job']) && is_string($data['target_job'])
                ? trim($data['target_job'])
                : '';

            return $targetJob !== '' ? $targetJob : null;
        }

        return null;
    }

    /**
     * @return array{text:string,meta:array<string,mixed>}
     */
    public function extractImportedPayload(UploadedFile $file, string $extension): array
    {
        if ($extension === 'pdf') {
            $parser = new Parser;
            $pdf = $parser->parseFile($file->getPathname());
            $pdfText = trim($pdf->getText());
            $ocrAvailable = $this->isPdfOcrAvailable();

            if ($this->normalizeImportedResumeText($pdfText) !== '') {
                return [
                    'text' => $pdfText,
                    'meta' => [
                        'source' => 'pdf',
                        'extraction_mode' => 'pdf_text',
                        'ocr_available' => $ocrAvailable,
                        'ocr_used' => false,
                        'possible_scanned_pdf' => false,
                    ],
                ];
            }

            if ($ocrAvailable) {
                $ocrText = $this->extractPdfTextWithOcr($file->getPathname());
                if ($this->normalizeImportedResumeText($ocrText) !== '') {
                    return [
                        'text' => $ocrText,
                        'meta' => [
                            'source' => 'pdf',
                            'extraction_mode' => 'pdf_ocr',
                            'ocr_available' => true,
                            'ocr_used' => true,
                            'possible_scanned_pdf' => true,
                        ],
                    ];
                }
            }

            return [
                'text' => $pdfText,
                'meta' => [
                    'source' => 'pdf',
                    'extraction_mode' => 'pdf_text_empty',
                    'ocr_available' => $ocrAvailable,
                    'ocr_used' => false,
                    'possible_scanned_pdf' => true,
                ],
            ];
        }

        if ($extension === 'docx') {
            $docxText = $this->extractDocxText($file->getPathname());

            return [
                'text' => $docxText,
                'meta' => [
                    'source' => 'docx',
                    'extraction_mode' => 'docx_text',
                    'ocr_available' => false,
                    'ocr_used' => false,
                    'possible_scanned_pdf' => false,
                ],
            ];
        }

        if ($extension === 'doc') {
            $docText = $this->extractDocText($file->getPathname());

            return [
                'text' => $docText,
                'meta' => [
                    'source' => 'doc',
                    'extraction_mode' => 'doc_text',
                    'ocr_available' => false,
                    'ocr_used' => false,
                    'possible_scanned_pdf' => false,
                ],
            ];
        }

        return [
            'text' => trim($file->getContent()),
            'meta' => [
                'source' => $extension,
                'extraction_mode' => 'plain_text',
                'ocr_available' => false,
                'ocr_used' => false,
                'possible_scanned_pdf' => false,
            ],
        ];
    }

    public function extractDocxText(string $path): string
    {
        $text = '';
        $zip = new \ZipArchive;
        if ($zip->open($path) === true) {
            // Zip Bomb 防护：限制解压后内容大小为 50MB
            $maxUncompressedSize = 50 * 1024 * 1024;
            $statIndex = $zip->statName('word/document.xml');
            if ($statIndex !== false && isset($statIndex['size']) && $statIndex['size'] > $maxUncompressedSize) {
                $zip->close();
                throw new \RuntimeException('文档文件过大，解压后内容超过 50MB 限制');
            }
            $xml = (string) ($zip->getFromName('word/document.xml') ?: '');
            $zip->close();

            if ($xml !== '') {
                // 限制处理内容长度，防止超大 XML 消耗内存
                $xml = mb_substr($xml, 0, $maxUncompressedSize);
                $xml = str_replace(['</w:p>', '</w:tr>', '</w:tbl>'], ["\n", "\n", "\n"], $xml);
                $plain = strip_tags($xml);
                $text = html_entity_decode($plain, ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        if (trim($text) !== '') {
            return $text;
        }

        return $this->extractOfficeTextByLibreOffice($path, 'docx');
    }

    public function extractDocText(string $path): string
    {
        $antiwordPath = $this->findExecutablePath('antiword');
        if ($antiwordPath !== null) {
            try {
                $process = new Process([$antiwordPath, '-w', '0', $path]);
                $process->setTimeout(60);
                $process->run();
                $output = trim($process->getOutput());
                if ($process->isSuccessful() && $output !== '') {
                    return $output;
                }
            } catch (\Throwable $e) {
                Log::debug('antiword extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }

        $catdocPath = $this->findExecutablePath('catdoc');
        if ($catdocPath !== null) {
            try {
                $process = new Process([$catdocPath, $path]);
                $process->setTimeout(60);
                $process->run();
                $output = trim($process->getOutput());
                if ($process->isSuccessful() && $output !== '') {
                    return $output;
                }
            } catch (\Throwable $e) {
                Log::debug('catdoc extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }

        return $this->extractOfficeTextByLibreOffice($path, 'doc');
    }

    public function extractOfficeTextByLibreOffice(string $path, string $extension): string
    {
        $sofficePath = $this->findExecutablePath('soffice') ?? $this->findExecutablePath('libreoffice');
        if ($sofficePath === null) {
            return '';
        }

        // LibreOffice --headless 不支持并发，使用文件锁串行化
        $lockPath = storage_path('app/tmp/libreoffice-conversion.lock');
        File::ensureDirectoryExists(dirname($lockPath));
        $lockFile = fopen($lockPath, 'w');
        if ($lockFile === false) {
            return '';
        }

        if (! flock($lockFile, LOCK_EX | LOCK_NB)) {
            // 已有转换在进行中，阻塞等待
            flock($lockFile, LOCK_EX);
        }

        $tempDir = storage_path('app/tmp/resume-import-office-'.Str::uuid());
        File::ensureDirectoryExists($tempDir);
        $baseName = pathinfo($path, PATHINFO_FILENAME);
        $outputPath = $tempDir.DIRECTORY_SEPARATOR.$baseName.'.txt';

        try {
            $process = new Process([
                $sofficePath,
                '--headless',
                '--convert-to',
                'txt:Text',
                '--outdir',
                $tempDir,
                $path,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful() || ! File::exists($outputPath)) {
                return '';
            }

            return trim((string) File::get($outputPath));
        } catch (\Throwable) {
            return '';
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            flock($lockFile, LOCK_UN);
            fclose($lockFile);
        }
    }

    public function normalizeImportedResumeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[•●▪◦‣∙]/u', '- ', $text) ?? $text;
        $text = preg_replace('/[－–—]/u', '-', $text) ?? $text;
        $text = preg_replace('/[：﹕]/u', ':', $text) ?? $text;

        $normalizedLines = [];
        foreach (explode("\n", $text) as $line) {
            $line = trim((string) preg_replace('/\s+/u', ' ', trim($line)));
            if ($this->shouldSkipImportedLine($line)) {
                continue;
            }

            if ($line === '') {
                if (! empty($normalizedLines) && end($normalizedLines) !== '') {
                    $normalizedLines[] = '';
                }

                continue;
            }

            $lastIndex = count($normalizedLines) - 1;
            if ($lastIndex >= 0 && $normalizedLines[$lastIndex] !== '' && $this->shouldMergeImportedLine($normalizedLines[$lastIndex], $line)) {
                $normalizedLines[$lastIndex] .= ' '.$line;
            } else {
                $normalizedLines[] = $line;
            }
        }

        $normalized = implode("\n", $normalizedLines);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $normalized));
    }

    /**
     * @param  array<string, mixed>  $importMeta
     * @return array{
     *   modules:array<int, array<string, mixed>>,
     *   target_job:string,
     *   import_meta:array<string,mixed>
     * }
     */
    public function resolveImportedModules(string $text, array $importMeta, callable $logException): array
    {
        $targetJob = '';
        $modules = [];
        $llmEnabled = (bool) config('resume.import_parser.llm_enabled', true);
        $llmMinConfidence = (float) config('resume.import_parser.llm_min_confidence', 0.45);
        $llmMinModuleCount = max(1, (int) config('resume.import_parser.llm_min_module_count', 2));

        if ($llmEnabled) {
            try {
                $aiResult = $this->aiManager->provider()->extractResumeStructured($text);
                $aiModules = $this->normalizeAiModules($aiResult['modules'] ?? null);
                $aiConfidence = is_numeric($aiResult['confidence'] ?? null) ? (float) $aiResult['confidence'] : 0.0;
                $aiTargetJob = is_string($aiResult['target_job'] ?? null) ? trim((string) $aiResult['target_job']) : '';

                if (count($aiModules) >= $llmMinModuleCount && $aiConfidence >= $llmMinConfidence) {
                    $modules = $aiModules;
                    $targetJob = $aiTargetJob;
                    $importMeta['llm_used'] = true;
                    $importMeta['llm_confidence'] = round($aiConfidence, 4);
                    $importMeta['extraction_mode'] = 'llm_structured';
                } else {
                    $importMeta['llm_used'] = false;
                    $importMeta['llm_confidence'] = round($aiConfidence, 4);
                    $importMeta['llm_fallback_reason'] = 'low_confidence_or_insufficient_modules';
                }
            } catch (\Throwable $exception) {
                $importMeta['llm_used'] = false;
                $importMeta['llm_fallback_reason'] = 'llm_error';
                $logException('resume_import_llm_extract_failed', $exception, [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($modules === []) {
            $modules = $this->moduleParserService->parseRawToModules($text);
            $targetJob = $this->extractTargetJobFromModules($modules) ?? '';
            if (! isset($importMeta['extraction_mode']) || ! is_string($importMeta['extraction_mode'])) {
                $importMeta['extraction_mode'] = 'rule_based';
            }
        }

        return [
            'modules' => $modules,
            'target_job' => $targetJob,
            'import_meta' => $importMeta,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizeAiModules(mixed $rawModules): array
    {
        if (! is_array($rawModules)) {
            return [];
        }

        $allowedTypes = array_fill_keys($this->allowedModuleTypes(), true);
        $normalized = [];
        foreach ($rawModules as $idx => $module) {
            if (! is_array($module)) {
                continue;
            }
            $type = is_string($module['type'] ?? null) ? trim((string) $module['type']) : '';
            $data = is_array($module['data'] ?? null) ? $module['data'] : [];
            if ($type === '' || $data === [] || ! isset($allowedTypes[$type])) {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'data' => $data,
                'sort_order' => is_int($module['sort_order'] ?? null) ? (int) $module['sort_order'] : $idx,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    public function allowedModuleTypes(): array
    {
        return ['personal', 'objective', 'education', 'experience', 'project', 'skill', 'certificate', 'summary'];
    }

    private function matchSectionHeader(string $line): ?string
    {
        $patterns = [
            '/^(教育背景|教育经历|学历|学习经历|Education)/ui' => 'education',
            '/^(工作经历|实习经历|工作经验|工作履历|Experience|Work)/ui' => 'experience',
            '/^(项目经验|项目经历|项目|Projects?)/ui' => 'project',
            '/^(技能|证书|技能证书|专业技能|Skills?|Certifications?)/ui' => 'skill',
            '/^(获奖|荣誉|奖励|Awards?|Honors?)/ui' => 'certificate',
            '/^(自我评价|个人评价|自我介绍|Summary|Profile)/ui' => 'summary',
            '/^(求职意向|应聘岗位|目标岗位|Objective)/ui' => 'objective',
        ];

        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $line)) {
                return $type;
            }
        }

        return null;
    }

    private function isListLikeLine(string $line): bool
    {
        return (bool) preg_match('/^[\-\•\*\d+\.\、]/u', trim($line));
    }

    private function isDateLikePart(string $line): bool
    {
        return (bool) preg_match('/^\d{4}[.\-\/年]\d{1,2}/u', trim($line));
    }
}
