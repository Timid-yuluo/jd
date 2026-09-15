<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Admin\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ApplyDynamicSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $default = (int) config('session.lifetime', 120);

        try {
            $raw = app(SystemSettingService::class)->get('session_lifetime', (string) $default);
            $minutes = (int) $raw;

            if ($minutes >= 10 && $minutes <= 480) {
                config(['session.lifetime' => $minutes]);
            }
        } catch (Throwable) {
            config(['session.lifetime' => $default]);
        }

        if (Auth::check()) {
            $idleTimeout = $this->resolveIdleTimeout();
            $lastActivity = session('last_activity_time');

            if ($lastActivity && $idleTimeout > 0) {
                $idleSeconds = now()->diffInSeconds($lastActivity);

                if ($idleSeconds > $idleTimeout * 60) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('warning', '由于长时间未操作，已自动退出登录，请重新登录。');
                }
            }

            session(['last_activity_time' => now()]);
        }

        return $next($request);
    }

    private function resolveIdleTimeout(): int
    {
        try {
            $raw = app(SystemSettingService::class)->get('session_idle_timeout', '30');
            $minutes = (int) $raw;

            if ($minutes >= 5 && $minutes <= 240) {
                return $minutes;
            }
        } catch (Throwable) {
        }

        return 30;
    }
}
