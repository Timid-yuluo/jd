<?php

declare(strict_types=1);

namespace App\Services\Resume\Builders;

use App\Models\Resume;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class ResumeDocxBuilder
{
    private string $exportDirectory;

    private DocxXmlBuilder $xmlBuilder;

    private DocxTemplateService $templateService;

    private DocxMediaService $mediaService;

    public function __construct(string $exportDirectory)
    {
        $this->exportDirectory = $exportDirectory;
        $this->xmlBuilder = new DocxXmlBuilder;
        $this->templateService = new DocxTemplateService;
        $this->mediaService = new DocxMediaService;
    }

    public function buildSingleDocxFile(Resume $resume): array
    {
        $safeTitle = $this->safeExportBaseName((string) $resume->title, (int) $resume->id);
        $downloadName = $safeTitle.'.docx';
        $docxPath = $this->exportDirectory.DIRECTORY_SEPARATOR.$downloadName;

        $this->writeDocxPackage($docxPath, $resume);

        return [$docxPath, $downloadName];
    }

    /**
     * @param  Collection<int, Resume>  $resumes
     * @return array{0:string,1:string}
     */
    public function buildBatchDocxZipFile(Collection $resumes, string $taskId): array
    {
        $batchBaseName = $this->safeZipFolderName('resumes_docx_'.$taskId);
        $downloadName = $batchBaseName.'.zip';
        $zipPath = $this->exportDirectory.DIRECTORY_SEPARATOR.$downloadName;
        $rootFolder = $batchBaseName;
        $docsFolder = $rootFolder.'/docs';
        $resumesFolder = $rootFolder.'/resumes';

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('导出失败：DOCX 压缩包创建失败。');
        }

        $manifestEntries = [];
        $manifestLines = [
            '导出说明',
            '------------------------------',
            '导出时间: '.now()->format('Y-m-d H:i:s'),
            '导出数量: '.$resumes->count(),
            '任务编号: '.$taskId,
            '压缩包根目录: '.$rootFolder,
            '简历文件目录: '.$resumesFolder,
            '命名规则: 序号_目标岗位_简历标题.docx',
            '',
            '目录说明:',
            '- docs/README.txt: 人工可读说明',
            '- docs/manifest.json: 机器可读清单',
            '- resumes/: 所有 DOCX 简历文件',
            '',
            '文件清单:',
        ];

        foreach ($resumes as $index => $resume) {
            $tempDocxPath = $this->exportDirectory.DIRECTORY_SEPARATOR.'tmp_docx_'.$taskId.'_'.$resume->id.'_'.Str::lower(Str::random(6)).'.docx';
            $docxBaseName = $this->buildBatchDocxEntryName($resume, $index + 1);
            $this->writeDocxPackage($tempDocxPath, $resume);
            $zipEntryName = $resumesFolder.'/'.$docxBaseName;
            $zip->addFile($tempDocxPath, $zipEntryName);
            $manifestLines[] = '- '.$zipEntryName;
            $manifestEntries[] = [
                'sequence' => $index + 1,
                'resume_id' => (int) $resume->id,
                'title' => (string) $resume->title,
                'target_job' => (string) ($resume->target_job ?? ''),
                'template' => (string) ($resume->template ?? 'classic'),
                'theme' => (string) ($resume->theme ?? 'blue'),
                'file' => $zipEntryName,
            ];
        }

        $zip->addFromString($docsFolder.'/README.txt', implode("\n", $manifestLines));
        $zip->addFromString($docsFolder.'/manifest.json', $this->docxBatchManifestJson($taskId, $resumes->count(), $rootFolder, $manifestEntries));
        $zip->close();

        foreach ($resumes as $resume) {
            $pattern = $this->exportDirectory.DIRECTORY_SEPARATOR.'tmp_docx_'.$taskId.'_'.$resume->id.'_*';
            foreach (glob($pattern) ?: [] as $tempFile) {
                if (is_string($tempFile) && File::exists($tempFile)) {
                    File::delete($tempFile);
                }
            }
        }

        return [$zipPath, $downloadName];
    }

    public function writeDocxPackage(string $targetPath, Resume $resume): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($targetPath, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('导出失败：DOCX 文件创建失败。');
        }

        $parts = $this->buildDocxDocumentParts($resume);
        $createdAt = now()->toAtomString();
        $footerTimestamp = now()->format('Y-m-d H:i');
        $siteName = (string) config('app.name', '职路通');
        $title = $this->xmlBuilder->xml((string) ($resume->title ?: '简历导出'));

        $zip->addFromString('[Content_Types].xml', $this->xmlBuilder->contentTypesXml($parts['media']));
        $zip->addFromString('_rels/.rels', $this->xmlBuilder->rootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->xmlBuilder->appXml());
        $zip->addFromString('docProps/core.xml', $this->xmlBuilder->coreXml($title, $createdAt));
        $zip->addFromString('word/document.xml', $parts['document_xml']);
        $zip->addFromString('word/styles.xml', $this->xmlBuilder->stylesXml($parts['variant'], $parts['typography']));
        $zip->addFromString('word/fontTable.xml', $this->xmlBuilder->fontTableXml());
        $zip->addFromString('word/footer1.xml', $this->xmlBuilder->footerXml($siteName, $footerTimestamp));
        $zip->addFromString('word/_rels/document.xml.rels', $this->xmlBuilder->documentRelsXml($parts['relationships']));

        foreach ($parts['media'] as $media) {
            $zip->addFromString($media['path'], $media['content']);
        }

        $zip->close();
    }

    /**
     * @return array{
     *   document_xml:string,
     *   relationships:array<int,array{id:string,type:string,target:string}>,
     *   media:array<int,array{path:string,content:string,extension:string}>,
     *   palette:array{accent:string,accent_light:string},
     *   variant:array<string,string|int>,
     *   typography:array<string,int|string>
     * }
     */
    public function buildDocxDocumentParts(Resume $resume): array
    {
        $modules = $resume->modules->isNotEmpty() ? $resume->modules->toArray() : [];
        $fontSettings = $this->resolveFontSettings($resume);
        $typography = $this->buildDocxTypography($fontSettings);

        $palette = $this->templateService->resolveThemePalette((string) ($resume->theme ?? 'blue'));
        $accentHex = $this->normalizeHexColor((string) ($fontSettings['accentColor'] ?? ''));
        if ($accentHex !== null) {
            $palette['accent'] = $accentHex;
            $palette['accent_light'] = $this->lightenHexColor($accentHex, 0.9);
        }

        $variant = $this->templateService->resolveTemplateVariant((string) ($resume->template ?? 'classic'), $palette);
        $headingScale = $this->clampFloat($fontSettings['headingFontSize'] ?? 1.11, 0.8, 1.5, 1.11);
        $variant['section_size'] = max(20, min(40, (int) round(((int) ($variant['section_size'] ?? 26)) * $headingScale)));

        $headingHex = $this->normalizeHexColor((string) ($fontSettings['headingColor'] ?? ''));
        if ($headingHex !== null) {
            $variant['section_color'] = $headingHex;
            $variant['entry_title_color'] = $headingHex;
            $variant['info_name_color'] = $headingHex;
        }

        $bodyHex = $this->normalizeHexColor((string) ($fontSettings['bodyFontColor'] ?? ''));
        if ($bodyHex !== null) {
            $variant['info_contact_color'] = $bodyHex;
            $variant['highlight_color'] = $bodyHex;
        }

        $this->xmlBuilder->applyTypography($typography);

        $blocks = [];
        $relationships = [[
            'id' => 'rIdFooter1',
            'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer',
            'target' => 'footer1.xml',
        ]];
        $media = [];

        $personal = $this->extractDocxPersonalModule($modules);
        $objectiveModules = array_values(array_filter($modules, static fn (array $module): bool => ($module['type'] ?? '') === 'objective'));
        $groupedModules = [
            'education' => array_values(array_filter($modules, static fn (array $module): bool => ($module['type'] ?? '') === 'education')),
            'experience' => array_values(array_filter($modules, static fn (array $module): bool => ($module['type'] ?? '') === 'experience')),
            'project' => array_values(array_filter($modules, static fn (array $module): bool => ($module['type'] ?? '') === 'project')),
            'skill' => array_values(array_filter($modules, static fn (array $module): bool => ($module['type'] ?? '') === 'skill')),
            'certificate' => array_values(array_filter($modules, static fn (array $module): bool => ($module['type'] ?? '') === 'certificate')),
            'summary' => array_values(array_filter($modules, static fn (array $module): bool => ($module['type'] ?? '') === 'summary')),
        ];

        $avatarRelationshipId = null;
        $avatarAsset = $this->mediaService->resolveAvatarAsset($personal['avatar'] ?? '');
        if ($avatarAsset !== null) {
            $relationships[] = [
                'id' => 'rIdAvatar1',
                'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image',
                'target' => 'media/'.$avatarAsset['filename'],
            ];
            $media[] = [
                'path' => 'word/media/'.$avatarAsset['filename'],
                'content' => $avatarAsset['content'],
                'extension' => $avatarAsset['extension'],
            ];
            $avatarRelationshipId = 'rIdAvatar1';
        }

        $contactParts = array_values(array_filter([
            trim((string) ($personal['phone'] ?? '')),
            trim((string) ($personal['email'] ?? '')),
            trim((string) ($personal['location'] ?? '')),
            trim((string) ($resume->target_job ?? '')),
        ], static fn (string $value): bool => $value !== ''));
        $displayName = trim((string) ($personal['name'] ?? '')) !== ''
            ? trim((string) $personal['name'])
            : trim((string) ($resume->title ?? '简历'));
        $targetJobText = trim((string) ($resume->target_job ?? ''));
        $contactText = $contactParts !== [] ? implode('  |  ', $contactParts) : '';
        $blocks[] = $this->xmlBuilder->infoCardTable(
            $displayName,
            $targetJobText,
            $contactText,
            (string) $variant['info_name_color'],
            (string) $variant['info_fill'],
            $avatarRelationshipId,
            (string) $variant['info_target_color'],
            (string) $variant['info_contact_color']
        );
        $blocks[] = $this->xmlBuilder->dividerParagraph((string) $variant['divider_color']);

        if ($objectiveModules !== []) {
            $blocks[] = $this->xmlBuilder->paragraph('求职意向', ['style' => 'ResumeSection']);
            foreach ($objectiveModules as $module) {
                $data = is_array($module['data'] ?? null) ? $module['data'] : [];
                $targetJob = trim((string) ($data['target_job'] ?? ''));
                $content = trim((string) ($data['content'] ?? ''));
                if ($targetJob !== '') {
                    $blocks[] = $this->xmlBuilder->paragraph('目标岗位：'.$targetJob, ['style' => 'ResumeEntryTitle']);
                }
                foreach ($this->docxContentLines($content) as $line) {
                    $blocks[] = $this->xmlBuilder->paragraph($line);
                }
            }
        }

        $highlights = array_values(array_filter(
            array_map(static fn ($item): string => trim((string) $item), is_array($resume->highlights) ? $resume->highlights : []),
            static fn (string $item): bool => $item !== ''
        ));
        if ($highlights !== []) {
            $blocks[] = $this->xmlBuilder->paragraph('核心亮点', ['style' => 'ResumeSection']);
            foreach ($highlights as $highlight) {
                $blocks[] = $this->xmlBuilder->paragraph('• '.$highlight, [
                    'spacing_after' => 60,
                    'shading_fill' => (string) $variant['highlight_fill'],
                    'color' => (string) $variant['highlight_color'],
                    'indent_left' => 120,
                ]);
            }
        }

        foreach (['experience', 'project', 'education', 'skill', 'certificate', 'summary'] as $type) {
            $entries = $groupedModules[$type];
            if ($entries === []) {
                continue;
            }

            $blocks[] = $this->xmlBuilder->paragraph($this->templateService->sectionTitleForType($type, $entries), ['style' => 'ResumeSection']);

            foreach ($entries as $module) {
                $data = is_array($module['data'] ?? null) ? $module['data'] : [];
                $subtitle = trim((string) ($data['subtitle'] ?? ''));
                $date = trim((string) ($data['date'] ?? ''));
                $location = trim((string) ($data['location'] ?? ''));
                $content = trim((string) ($data['content'] ?? ''));
                $items = array_values(array_filter(
                    array_map(static fn ($item): string => trim((string) $item), is_array($data['items'] ?? null) ? $data['items'] : []),
                    static fn (string $item): bool => $item !== ''
                ));

                if ($type === 'skill') {
                    if ($subtitle !== '') {
                        $blocks[] = $this->xmlBuilder->paragraph($subtitle, ['style' => 'ResumeEntryTitle']);
                    }
                    if ($items !== []) {
                        foreach ($items as $item) {
                            $blocks[] = $this->xmlBuilder->paragraph($item, [
                                'spacing_after' => 60,
                                'shading_fill' => (string) $variant['chip_fill'],
                                'color' => (string) $variant['chip_color'],
                                'bold' => true,
                                'indent_left' => 120,
                            ]);
                        }
                    }
                    foreach ($this->docxContentLines($content) as $line) {
                        $blocks[] = $this->xmlBuilder->paragraph($line);
                    }

                    continue;
                }

                if ($type === 'summary') {
                    foreach ($this->docxContentLines($content) as $line) {
                        $blocks[] = $this->xmlBuilder->paragraph($line);
                    }
                    foreach ($items as $item) {
                        $blocks[] = $this->xmlBuilder->paragraph('• '.$item, ['spacing_after' => 60]);
                    }

                    continue;
                }

                $entryTitle = $subtitle !== '' ? $subtitle : trim((string) ($data['title'] ?? ''));
                if ($entryTitle !== '' || $date !== '' || $location !== '') {
                    $blocks[] = $this->xmlBuilder->entryHeaderTable(
                        $entryTitle,
                        $date,
                        $location,
                        (string) $variant['entry_fill']
                    );
                }

                foreach ($this->docxContentLines($content) as $line) {
                    $blocks[] = $this->xmlBuilder->paragraph($line);
                }
                foreach ($items as $item) {
                    $blocks[] = $this->xmlBuilder->paragraph('• '.$item, ['spacing_after' => 60]);
                }
            }
        }

        if ($blocks === []) {
            $blocks[] = $this->xmlBuilder->paragraph((string) $resume->content_raw);
        }

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" '
            .'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" '
            .'xmlns:o="urn:schemas-microsoft-com:office:office" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" '
            .'xmlns:v="urn:schemas-microsoft-com:vml" '
            .'xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" '
            .'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            .'xmlns:w10="urn:schemas-microsoft-com:office:word" '
            .'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
            .'xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" '
            .'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
            .'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
            .'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
            .'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
            .'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            .'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" '
            .'mc:Ignorable="w14 w15 wp14">'
            .'<w:body>'.implode('', $blocks)
            .'<w:sectPr><w:footerReference w:type="default" r:id="rIdFooter1"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="720" w:right="720" w:bottom="900" w:left="720" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>';

        return [
            'document_xml' => $documentXml,
            'relationships' => $relationships,
            'media' => $media,
            'palette' => $palette,
            'variant' => $variant,
            'typography' => $typography,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $modules
     * @return array{name:string,phone:string,email:string,location:string,avatar:string}
     */
    private function extractDocxPersonalModule(array $modules): array
    {
        foreach ($modules as $module) {
            if (($module['type'] ?? '') !== 'personal') {
                continue;
            }

            $data = is_array($module['data'] ?? null) ? $module['data'] : [];

            return [
                'name' => trim((string) ($data['name'] ?? '')),
                'phone' => trim((string) ($data['phone'] ?? '')),
                'email' => trim((string) ($data['email'] ?? '')),
                'location' => trim((string) ($data['location'] ?? '')),
                'avatar' => trim((string) ($data['avatar'] ?? '')),
            ];
        }

        return ['name' => '', 'phone' => '', 'email' => '', 'location' => '', 'avatar' => ''];
    }

    /**
     * @return array<int, string>
     */
    private function docxContentLines(string $content): array
    {
        return array_values(array_filter(
            array_map(static fn (string $line): string => trim($line), preg_split("/\r\n|\n|\r/", $content) ?: []),
            static fn (string $line): bool => $line !== ''
        ));
    }

    /**
     * @param  array{name:string,phone:string,email:string,location:string,avatar:string}  $personal
     * @return array<int, string>
     */
    private function buildDocxContactParts(array $personal, string $targetJob): array
    {
        $parts = [];

        $phone = trim((string) ($personal['phone'] ?? ''));
        if ($phone !== '') {
            $parts[] = $this->withDocxSymbol('☎', $phone);
        }

        $email = trim((string) ($personal['email'] ?? ''));
        if ($email !== '') {
            $parts[] = $this->withDocxSymbol('✉', $email);
        }

        $location = trim((string) ($personal['location'] ?? ''));
        if ($location !== '') {
            $parts[] = $this->withDocxSymbol('⌂', $location);
        }

        $job = trim($targetJob);
        if ($job !== '') {
            $parts[] = $this->withDocxSymbol('◆', $job);
        }

        return $parts;
    }

    private function withDocxSymbol(string $symbol, string $text): string
    {
        return trim($symbol).' '.trim($text);
    }

    public function safeExportBaseName(string $title, int $fallbackId): string
    {
        $value = Str::of(Str::ascii($title))
            ->lower()
            ->replaceMatches('/[^a-z0-9\-_]+/', '_')
            ->trim('_')
            ->value();

        return $value !== '' ? $value : 'resume_'.$fallbackId;
    }

    public function safeZipFolderName(string $name): string
    {
        $value = Str::of(Str::ascii($name))
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '_')
            ->trim('._-')
            ->value();

        return $value !== '' ? $value : 'resume_exports';
    }

    public function buildBatchDocxEntryName(Resume $resume, int $sequence): string
    {
        $parts = [
            str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
        ];

        $targetJob = trim((string) ($resume->target_job ?? ''));
        if ($targetJob !== '') {
            $parts[] = $this->safeExportBaseName($targetJob, (int) $resume->id);
        }

        $parts[] = $this->safeExportBaseName((string) $resume->title, (int) $resume->id);

        return implode('_', array_values(array_unique(array_filter($parts)))).'.docx';
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function docxBatchManifestJson(string $taskId, int $count, string $rootFolder, array $entries): string
    {
        $payload = [
            'task_id' => $taskId,
            'exported_at' => now()->toAtomString(),
            'count' => $count,
            'root_folder' => $rootFolder,
            'entries' => $entries,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '{}';
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFontSettings(Resume $resume): array
    {
        $contentStructured = is_array($resume->content_structured) ? $resume->content_structured : [];
        $settings = is_array($contentStructured['font_settings'] ?? null)
            ? $contentStructured['font_settings']
            : [];

        return [
            'fontFamily' => (string) ($settings['fontFamily'] ?? ''),
            'fontSize' => $settings['fontSize'] ?? 13.5,
            'lineHeight' => $settings['lineHeight'] ?? 1.7,
            'headingFontSize' => $settings['headingFontSize'] ?? 1.11,
            'headingColor' => (string) ($settings['headingColor'] ?? ''),
            'accentColor' => (string) ($settings['accentColor'] ?? ''),
            'bodyFontColor' => (string) ($settings['bodyFontColor'] ?? ''),
            'sectionSpacing' => $settings['sectionSpacing'] ?? 18,
            'titleStyleVariant' => (string) ($settings['titleStyleVariant'] ?? 'template'),
        ];
    }

    /**
     * @param  array<string, mixed>  $fontSettings
     * @return array<string, int|string>
     */
    private function buildDocxTypography(array $fontSettings): array
    {
        $fontName = $this->extractDocxFontName((string) ($fontSettings['fontFamily'] ?? ''));
        $fontSizePx = $this->clampFloat($fontSettings['fontSize'] ?? 13.5, 11.0, 16.0, 13.5);
        $lineHeight = $this->clampFloat($fontSettings['lineHeight'] ?? 1.7, 1.3, 2.2, 1.7);
        $sectionSpacingPx = $this->clampFloat($fontSettings['sectionSpacing'] ?? 18, 8.0, 40.0, 18.0);

        $baseFontSize = max(18, min(32, (int) round($fontSizePx * 1.5)));
        $lineTwips = max(240, min(560, (int) round($baseFontSize * 10 * $lineHeight)));
        $sectionSpacingTwips = max(120, min(760, (int) round($sectionSpacingPx * 15)));
        $paragraphSpacingAfter = max(40, min(260, (int) round($sectionSpacingTwips * 0.45)));

        return [
            'font_name' => $fontName,
            'base_font_size' => $baseFontSize,
            'line_twips' => $lineTwips,
            'paragraph_spacing_after' => $paragraphSpacingAfter,
            'section_spacing_twips' => $sectionSpacingTwips,
            'body_color' => $bodyColor ?? '',
        ];
    }

    private function extractDocxFontName(string $fontFamily): string
    {
        $value = trim(preg_replace('/["\']+/', '', $fontFamily) ?? '');
        if ($value === '') {
            return 'Microsoft YaHei';
        }

        $parts = array_values(array_filter(array_map(static fn (string $item): string => trim($item), explode(',', $value))));

        return $parts[0] ?? 'Microsoft YaHei';
    }

    private function normalizeHexColor(string $value): ?string
    {
        $hex = trim($value);
        if ($hex === '') {
            return null;
        }

        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3 && preg_match('/^[0-9a-fA-F]{3}$/', $hex) === 1) {
            $hex = strtoupper($hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]);
        }

        if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex) !== 1) {
            return null;
        }

        return strtoupper($hex);
    }

    private function lightenHexColor(string $hex, float $ratio = 0.88): string
    {
        $normalized = $this->normalizeHexColor($hex) ?? '2563EB';
        $ratio = max(0.0, min(1.0, $ratio));

        $r = hexdec(substr($normalized, 0, 2));
        $g = hexdec(substr($normalized, 2, 2));
        $b = hexdec(substr($normalized, 4, 2));

        $mix = static fn (int $channel): int => (int) round($channel + (255 - $channel) * $ratio);

        return sprintf('%02X%02X%02X', $mix($r), $mix($g), $mix($b));
    }

    private function clampFloat(mixed $value, float $min, float $max, float $default): float
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (float) $value));
    }
}
