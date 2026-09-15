<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Admin\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmailVerificationRequired
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_ROUTES = [
        'user.dashboard',
        'user.profile',
        'user.profile.update',
        'user.profile.logout-all',
        'user.verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // 管理员不受此限制，避免影响后台运维处置。
        if ($user->is_admin && $user->can('access admin')) {
            return $next($request);
        }

        $required = app(SystemSettingService::class)->get('email_verification_required', '0');
        if ((int) $required !== 1) {
            return $next($request);
        }

        if ($user->email_verified_at !== null) {
            return $next($request);
        }

        if ($request->route() && $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('user.profile')
            ->with('warning', '当前系统已开启邮箱验证限制，请先完成邮箱验证后再继续使用该功能。');
    }
}
