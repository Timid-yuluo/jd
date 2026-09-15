<?php

declare(strict_types=1);

namespace App\Services\Resume\Builders;

use App\Models\Resume;
use App\Support\ResumeRenderStyle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

final class ResumePdfBuilder
{
    private string $exportDirectory;

    public function __construct(string $exportDirectory)
    {
        $this->exportDirectory = $exportDirectory;
    }

    /**
     * @param  Collection<int, Resume>  $resumes
     * @return array{0: string, 1: string}
     */
    public function buildPdfExportFile(string $taskId, Collection $resumes): array
    {
        $downloadName = 'resumes_export_'.$taskId.'.pdf';
        $pdfPath = $this->exportDirectory.DIRECTORY_SEPARATOR.$downloadName;

        $pagesHtml = [];
        foreach ($resumes as $resume) {
            $template = in_array((string) $resume->template, ['classic', 'modern', 'minimal', 'timeline', 'creative', 'elegant'], true)
                ? (string) $resume->template
                : 'classic';
            $theme = in_array((string) ($resume->theme ?? ''), ['blue', 'coral', 'green', 'purple', 'orange'], true)
                ? (string) $resume->theme
                : 'blue';

            $viewName = 'user.resumes.templates.'.$template;
            if (! View::exists($viewName)) {
                $viewName = 'user.resumes.templates.classic';
            }

            $modules = $resume->modules->isNotEmpty() ? $resume->modules->toArray() : [];
            $renderStyle = ResumeRenderStyle::fromResume($resume);
            $renderedHtml = View::make($viewName, [
                'resume' => $resume,
                'theme' => $theme,
                'forceModules' => $modules,
                'exportMode' => 'pdf',
            ])->render();
            $pagesHtml[] = '<div class="resume-render-context" style="'.$renderStyle['wrapper_style'].'">'
                .$this->normalizeTemplateHtmlForPdf($renderedHtml)
                .'</div>';
        }

        $rawHtml = implode("\n", $pagesHtml);
        $cjkFontPath = $this->resolveCjkFontPath();

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>'
            .$this->buildFontFaceStyles($cjkFontPath)
            .'@page{size:A4;margin:10mm;}'
            .'html,body{margin:0;padding:0;background:#fff;font-family:"'.$this->resolveFontFamily().'","PingFang SC","Microsoft YaHei","WenQuanYi Micro Hei",sans-serif;}'
            .'.resume-export-page,.resume-export-page *{font-family:"'.$this->resolveFontFamily().'","PingFang SC","Microsoft YaHei","WenQuanYi Micro Hei",sans-serif !important;}'
            .'.resume-export-page{page-break-after:always;}'
            .'.resume-export-page:last-child{page-break-after:auto;}'
            .ResumeRenderStyle::sharedCss()
            .'</style></head><body>'
            .collect($pagesHtml)->map(static fn (string $item): string => '<div class="resume-export-page">'.$item.'</div>')->implode('')
            .'</body></html>';

        return $this->buildPdfWithBrowsershot($html, $pdfPath, $downloadName);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function buildPdfWithBrowsershot(string $html, string $pdfPath, string $downloadName): array
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10, 'mm')
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->noSandbox();

        $nodeBinary = $this->resolveNodeBinary();
        if ($nodeBinary !== null) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        $npmBinary = $this->resolveNpmBinary();
        if ($npmBinary !== null) {
            $browsershot->setNpmBinary($npmBinary);
        }

        $chromiumPath = $this->resolveChromiumPath();
        if ($chromiumPath !== null) {
            $browsershot->setChromePath($chromiumPath);
        }

        $browsershot->savePdf($pdfPath);

        if (! File::exists($pdfPath) || File::size($pdfPath) < 100) {
            throw new \RuntimeException('Browsershot PDF 生成失败');
        }

        return [$pdfPath, $downloadName];
    }

    /**
     * 构建 @font-face 样式，Browsershot 通过 Chrome 渲染可直接使用本地字体文件。
     *
     * @param  string|null  $cjkFontPath
     */
    public function buildFontFaceStyles(?string $cjkFontPath): string
    {
        if ($cjkFontPath === null) {
            return '';
        }

        $fontUri = 'file://'.str_replace('\\', '/', $cjkFontPath);
        $family = $this->resolveFontFamily();

        return '@font-face{font-family:"'.$family.'";src:url("'.$fontUri.'");font-weight:normal;font-style:normal;}'
            .'@font-face{font-family:"'.$family.'";src:url("'.$fontUri.'");font-weight:700;font-style:normal;}';
    }

    public function resolveFontFamily(): string
    {
        return 'ResumeCjkFont';
    }

    public function resolveCjkFontPath(): ?string
    {
        $configuredPath = trim((string) config('export.pdf.cjk_font_path', ''));
        $candidates = array_filter([
            $configuredPath,
            storage_path('fonts/NotoSansSC-Regular.ttf'),
            storage_path('fonts/NotoSansSC.ttf'),
            storage_path('fonts/NotoSansSC-Regular.ttc'),
            '/usr/share/fonts/wenquanyi/wqy-microhei/wqy-microhei.ttc',
            '/usr/share/fonts/wqy-microhei/wqy-microhei.ttc',
            '/usr/share/fonts/google-noto-cjk/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/noto-cjk/NotoSansCJK-Regular.ttc',
        ], static fn (string $path): bool => $path !== '');

        foreach ($candidates as $path) {
            if (! File::exists($path) || ! is_readable($path)) {
                continue;
            }
            if (! $this->isSupportedPdfFontExtension($path)) {
                continue;
            }

            return $path;
        }

        return null;
    }

    public function isSupportedPdfFontExtension(string $fontPath): bool
    {
        $extension = strtolower((string) pathinfo($fontPath, PATHINFO_EXTENSION));

        return in_array($extension, ['ttf', 'ttc'], true);
    }

    public function containsCjkCharacters(string $text): bool
    {
        return preg_match('/\p{Han}/u', $text) === 1;
    }

    public function pdfRendererSignature(): string
    {
        $fontPath = $this->resolveCjkFontPath();
        if ($fontPath === null) {
            return 'browsershot:no-cjk-font:v1';
        }

        return implode(':', [
            'browsershot',
            'font',
            md5($fontPath),
            (string) (@filemtime($fontPath) ?: 0),
            'v1',
        ]);
    }

    public function normalizeTemplateHtmlForPdf(string $html): string
    {
        return str_replace(
            ['Instrument Sans', "font-family: 'Segoe UI', system-ui, sans-serif;"],
            [$this->resolveFontFamily(), 'font-family: "'.$this->resolveFontFamily().'","PingFang SC","Microsoft YaHei",sans-serif;'],
            $html
        );
    }

    /**
     * 解析 Node.js 可执行文件路径，优先使用项目内或系统路径。
     */
    private function resolveNodeBinary(): ?string
    {
        $configured = trim((string) config('export.pdf.node_binary', ''));
        if ($configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $paths = [
            '/usr/bin/node',
            '/usr/local/bin/node',
            (string) (shell_exec('which node 2>/dev/null') ?: ''),
        ];

        foreach ($paths as $path) {
            $path = trim($path);
            if ($path !== '' && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * 解析 npm 可执行文件路径。
     */
    private function resolveNpmBinary(): ?string
    {
        $configured = trim((string) config('export.pdf.npm_binary', ''));
        if ($configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $paths = [
            '/usr/bin/npm',
            '/usr/local/bin/npm',
            (string) (shell_exec('which npm 2>/dev/null') ?: ''),
        ];

        foreach ($paths as $path) {
            $path = trim($path);
            if ($path !== '' && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * 解析 Chromium/Chrome 可执行文件路径。Browsershot 会自动下载，但服务器环境建议手动指定。
     */
    private function resolveChromiumPath(): ?string
    {
        $configured = trim((string) config('export.pdf.chromium_path', ''));
        if ($configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $paths = [
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/local/bin/chromium',
            (string) (shell_exec('which chromium-browser 2>/dev/null || which chromium 2>/dev/null || which google-chrome 2>/dev/null') ?: ''),
        ];

        foreach ($paths as $path) {
            $path = trim($path);
            if ($path !== '' && is_executable($path)) {
                return $path;
            }
        }

        return null;
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
