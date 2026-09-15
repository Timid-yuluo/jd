<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\VisitTrackingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrackPageVisit
{
    private const EXCLUDED_PREFIXES = [
        'admin',
        'api/',
        '_debugbar',
        'horizon',
        'telescope',
    ];

    public function __construct(
        private readonly VisitTrackingService $trackingService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldTrack($request)) {
            return $next($request);
        }

        // 仅标记需要追踪，延迟到 terminate 阶段处理，避免阻塞响应
        $request->attributes->set('_should_track_visit', true);

        $response = $next($request);

        $this->setSessionCookie($request, $response);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->attributes->get('_should_track_visit')) {
            return;
        }

        $this->trackingService->recordPageview($request);
        $this->trackingService->flush();
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $path = $request->path();

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return $this->trackingService->shouldTrack($request);
    }

    private function setSessionCookie(Request $request, Response $response): void
    {
        $sessionId = $request->cookie('_vsid');

        if (is_string($sessionId) && strlen($sessionId) === 64 && ctype_alnum($sessionId)) {
            $value = $sessionId;
        } else {
            $value = \Illuminate\Support\Str::random(64);
        }

        $response->headers->setCookie(
            cookie('_vsid', $value, 30 * 24 * 60, '/', null, $request->isSecure(), true, false, 'Lax')
        );
    }
}
