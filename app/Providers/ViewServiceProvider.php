<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Admin\SystemSettingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;

/**
 * 视图合成器、Str 宏与 HTML 代码消毒 — 从 AppServiceProvider 抽离
 */
final class ViewServiceProvider extends ServiceProvider
{
    /** 视图共享变量缓存 Key，与 SystemSettingService 缓存失效联动 */
    private const SHARED_VARS_CACHE_KEY = 'view:shared_vars';

    /** 视图共享变量缓存 TTL（秒），略大于系统设置缓存以保证最终一致 */
    private const SHARED_VARS_CACHE_TTL = 360;
    public function boot(): void
    {
        $this->registerStrMacros();

        View::composer('*', function ($view): void {
            $view->with($this->resolveSharedViewVars());
        });
    }

    private function registerStrMacros(): void
    {
        Str::macro('toColorHex', static function (string $value): string {
            $hash = md5($value);
            $r = hexdec(substr($hash, 0, 2));
            $g = hexdec(substr($hash, 2, 2));
            $b = hexdec(substr($hash, 4, 2));
            $r = (int) ($r * 0.6 + 80);
            $g = (int) ($g * 0.6 + 80);
            $b = (int) ($b * 0.6 + 80);
            return sprintf('#%02x%02x%02x', min($r, 255), min($g, 255), min($b, 255));
        });

        // 生成基于用户标识的确定性几何图案头像 SVG
        Str::macro('avatarSvg', static function (string $identifier, int $size = 80): string {
            $hash = md5($identifier);
            $hue = hexdec(substr($hash, 0, 2)) % 360;
            $bgColor = "hsl({$hue}, 65%, 55%)";
            $fgColor = "hsl({$hue}, 65%, 80%)";

            $grid = 5;
            $cellSize = $size / $grid;
            $rects = '';
            for ($y = 0; $y < $grid; $y++) {
                for ($x = 0; $x < ceil($grid / 2) + 1; $x++) {
                    $bitIndex = $y * 3 + $x;
                    $byteIndex = (int) floor($bitIndex / 8);
                    $bitOffset = $bitIndex % 8;
                    $byte = hexdec(substr($hash, $byteIndex * 2, 2));
                    if (($byte >> $bitOffset) & 1) {
                        $rx = $x * $cellSize;
                        $ry = $y * $cellSize;
                        $rects .= "<rect x=\"{$rx}\" y=\"{$ry}\" width=\"{$cellSize}\" height=\"{$cellSize}\"/>";
                        $mirrorX = ($grid - 1 - $x) * $cellSize;
                        if ($mirrorX !== $rx) {
                            $rects .= "<rect x=\"{$mirrorX}\" y=\"{$ry}\" width=\"{$cellSize}\" height=\"{$cellSize}\"/>";
                        }
                    }
                }
            }

            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'">'
                .'<rect width="'.$size.'" height="'.$size.'" fill="'.$bgColor.'"/>'
                .'<g fill="'.$fgColor.'">'.$rects.'</g>'
                .'</svg>';

            return 'data:image/svg+xml,'.rawurlencode($svg);
        });
    }

    /**
     * 解析全局共享视图变量
     *
     * 使用 Cache 缓存组装结果（而非进程级 static 变量），
     * 以便管理员更新系统设置后能及时刷新（通过 clearSharedVarsCache）。
     *
     * @return array<string, mixed>
     */
    private function resolveSharedViewVars(): array
    {
        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get(self::SHARED_VARS_CACHE_KEY);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $settings = app(SystemSettingService::class)->all();

        $siteName = (string) ($settings['site_name'] ?? config('app.name'));
        $customHeadCode = self::sanitizeHeadSnippet((string) ($settings['custom_head_code'] ?? ''));
        $customBodyCode = self::sanitizeHeadSnippet((string) ($settings['custom_body_code'] ?? ''));
        $analyticsGoogle = self::sanitizeHeadSnippet((string) ($settings['analytics_google'] ?? ''));
        $analyticsBaidu = self::sanitizeHeadSnippet((string) ($settings['analytics_baidu'] ?? ''));
        $analyticsClarity = self::sanitizeHeadSnippet((string) ($settings['analytics_clarity'] ?? ''));

        $vars = [
            'siteSettings' => $settings,
            'siteName' => $siteName,
            'siteSubtitle' => (string) ($settings['site_subtitle'] ?? 'AI驱动的求职助手平台'),
            'seoKeywords' => (string) ($settings['seo_keywords'] ?? 'AI求职, 简历优化, 模拟面试'),
            'seoDescription' => (string) ($settings['seo_description'] ?? 'AI驱动的求职助手平台 - 智能简历优化、AI模拟面试、求职进度管理'),
            'maintenanceNotice' => (string) ($settings['maintenance_notice'] ?? ''),
            'copyrightText' => (string) ($settings['copyright_text'] ?? ''),
            'siteLogoUrl' => (string) ($settings['site_logo_url'] ?? ''),
            'faviconUrl' => (string) ($settings['favicon_url'] ?? ''),
            'icpNumber' => (string) ($settings['icp_number'] ?? ''),
            'policeRecordNumber' => (string) ($settings['police_record_number'] ?? ''),
            'policeRecordUrl' => (string) ($settings['police_record_url'] ?? ''),
            'wechatUrl' => (string) ($settings['wechat_url'] ?? ''),
            'githubUrl' => (string) ($settings['github_url'] ?? ''),
            'linkedinUrl' => (string) ($settings['linkedin_url'] ?? ''),
            'adminSiteName' => $siteName,
            'themeColor' => (string) ($settings['theme_primary_color'] ?? '#206bc4'),
            'themeMode' => (string) ($settings['theme_layout_mode'] ?? 'light'),
            'sidebarCollapsed' => (bool) ($settings['theme_sidebar_collapsed'] ?? false),
            'customHeadCode' => $customHeadCode,
            'customBodyCode' => $customBodyCode,
            'analyticsGoogle' => $analyticsGoogle,
            'analyticsBaidu' => $analyticsBaidu,
            'analyticsClarity' => $analyticsClarity,
            'maintenanceMode' => (bool) ($settings['maintenance_mode'] ?? false),
        ];

        try {
            Cache::put(self::SHARED_VARS_CACHE_KEY, $vars, self::SHARED_VARS_CACHE_TTL);
        } catch (\Throwable) {
            // 缓存不可用时静默降级，不影响视图渲染
        }

        return $vars;
    }

    /**
     * 清除视图共享变量缓存（供 SystemSettingService 变更后调用）。
     */
    public static function clearSharedVarsCache(): void
    {
        try {
            Cache::forget(self::SHARED_VARS_CACHE_KEY);
        } catch (\Throwable) {
            // 静默
        }
    }

    private static function sanitizeHeadSnippet(string $snippet): string
    {
        $snippet = trim($snippet);
        if ($snippet === '') {
            return '';
        }

        if (preg_match('/<\s*(script|meta|link|style|noscript)\b/i', $snippet) !== 1) {
            if (preg_match('/\.{2,}/', $snippet)) {
                return '';
            }
            return '';
        }

        if (preg_match('/<\s*(iframe|object|embed|form|base|applet)\b/i', $snippet)) {
            return '';
        }

        if (preg_match('/<\s*script\b/i', $snippet) === 1) {
            if (preg_match('/<\/\s*script\s*>/i', $snippet) !== 1) {
                return '';
            }
            if (preg_match('/on\w+\s*=/i', $snippet)) {
                return '';
            }
            if (preg_match('/javascript\s*:/i', $snippet)) {
                return '';
            }
            if (preg_match('/vbscript\s*:/i', $snippet)) {
                return '';
            }
            if (preg_match('/<\s*script[^>]*\bsrc\s*=\s*["\']?\s*data:/i', $snippet)) {
                return '';
            }
        }

        if (preg_match('/href\s*=\s*["\']?\s*javascript\s*:/i', $snippet)) {
            return '';
        }

        $nonce = request()->attributes->get('csp_nonce', '');
        if (is_string($nonce) && $nonce !== '') {
            $snippet = preg_replace(
                '/<\s*script\b(?![^>]*\bnonce=)/i',
                '<script nonce="'.e($nonce).'"',
                $snippet
            ) ?? $snippet;
        }

        return $snippet;
    }

    /**
     * 公共静态入口 — 供 Service 层调用 sanitizeHeadSnippet 逻辑
     */
    public static function sanitizeHeadSnippetPublic(string $snippet): string
    {
        return self::sanitizeHeadSnippet($snippet);
    }
}
