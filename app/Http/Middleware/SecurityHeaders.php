<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Admin\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // 生成 nonce 并存入 request attributes，确保视图渲染时可访问
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);
        $isPotentiallyTrustworthy = $this->isPotentiallyTrustworthyOrigin($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=(), screen-wake-lock=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        if ($isPotentiallyTrustworthy) {
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
            $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        } else {
            $response->headers->remove('Cross-Origin-Opener-Policy');
            $response->headers->remove('Cross-Origin-Resource-Policy');
        }

        $response->headers->set('Content-Security-Policy', $this->buildCsp($nonce));

        $this->applyCacheHeaders($request, $response);

        if (app()->isProduction() && $request->isSecure()) {
            $hsts = 'max-age=31536000; includeSubDomains';
            if (config('security.hsts_preload', false)) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        } else {
            $response->headers->remove('Strict-Transport-Security');
        }

        return $response;
    }

    private function applyCacheHeaders(Request $request, Response $response): void
    {
        $path = $request->path();

        if ($this->isStaticAsset($path)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->remove('Pragma');

            return;
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');

            return;
        }

        $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
    }

    private function isStaticAsset(string $path): bool
    {
        $normalizedPath = ltrim($path, '/');
        $staticPatterns = [
            '*.css',
            '*.js',
            '*.jpg',
            '*.jpeg',
            '*.png',
            '*.gif',
            '*.svg',
            '*.ico',
            '*.woff',
            '*.woff2',
            '*.ttf',
            '*.eot',
            '*.otf',
            '*.webp',
            '*.avif',
            'build/*',
            'assets/*',
            'fonts/*',
            'images/*',
            'css/*',
            'js/*',
        ];

        foreach ($staticPatterns as $pattern) {
            if (fnmatch($pattern, $normalizedPath)) {
                return true;
            }
        }

        return false;
    }

    private function isPotentiallyTrustworthyOrigin(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        return in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }

    private function buildCsp(string $nonce): string
    {
        // CSP 模板缓存：设置变更极少，缓存基础模板避免每次请求重建
        $cacheKey = 'security:csp_template';
        $template = Cache::remember($cacheKey, 300, function () {
            $settings = app(SystemSettingService::class)->all();
            $scriptDomains = [];
            $connectDomains = [];
            $imgDomains = [];

            // Google Analytics
            if (!empty($settings['analytics_google'])) {
                $scriptDomains[] = 'https://www.googletagmanager.com';
                $scriptDomains[] = 'https://www.google-analytics.com';
                $connectDomains[] = 'https://www.google-analytics.com';
                $connectDomains[] = 'https://analytics.google.com';
                $imgDomains[] = 'https://www.google-analytics.com';
                $imgDomains[] = 'https://www.googletagmanager.com';
            }

            // Baidu Analytics
            if (!empty($settings['analytics_baidu'])) {
                $scriptDomains[] = 'https://hm.baidu.com';
                $connectDomains[] = 'https://hm.baidu.com';
                $imgDomains[] = 'https://hm.baidu.com';
            }

            // Microsoft Clarity
            if (!empty($settings['analytics_clarity'])) {
                $scriptDomains[] = 'https://www.clarity.ms';
                $scriptDomains[] = 'https://scripts.clarity.ms';
                $connectDomains[] = 'https://www.clarity.ms';
                $connectDomains[] = 'https://v.clarity.ms';
                $imgDomains[] = 'https://www.clarity.ms';
                $imgDomains[] = 'c.clarity.ms';
            }

            // Google Fonts
            $styleDomains[] = 'https://fonts.googleapis.com';
            $fontDomains[] = 'https://fonts.gstatic.com';

            $scriptSrc = "script-src 'self' 'unsafe-eval' 'nonce-NONCE'";
            if ($scriptDomains !== []) {
                $scriptSrc .= ' ' . implode(' ', array_unique($scriptDomains));
            }

            $styleSrc = "style-src 'self' 'unsafe-inline'";
            if (!empty($styleDomains)) {
                $styleSrc .= ' ' . implode(' ', array_unique($styleDomains));
            }

            $fontSrc = "font-src 'self' data:";
            if (!empty($fontDomains)) {
                $fontSrc .= ' ' . implode(' ', array_unique($fontDomains));
            }

            $connectSrc = "connect-src 'self'";
            if ($connectDomains !== []) {
                $connectSrc .= ' ' . implode(' ', array_unique($connectDomains));
            }

            $imgSrc = "img-src 'self' data: blob: https://api.qrserver.com";
            if ($imgDomains !== []) {
                $imgSrc .= ' ' . implode(' ', array_unique($imgDomains));
            }

            return implode('; ', [
                "default-src 'self'",
                $scriptSrc,
                $styleSrc,
                $fontSrc,
                $imgSrc,
                $connectSrc,
                "frame-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);
        });

        // 替换 NONCE 占位符为实际值
        return str_replace('NONCE', $nonce, $template);
    }
}
