<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Admin\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 检查网站维护模式中间件
 */
class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 管理员始终可以访问
        if ($request->user()?->is_admin && $request->user()?->can('access admin')) {
            return $next($request);
        }

        $settings = app(SystemSettingService::class)->all();
        $maintenanceMode = (bool) ($settings['maintenance_mode'] ?? false);

        // 如果维护模式开启且不是管理员
        if ($maintenanceMode) {
            // 允许访问登录页面（以便管理员可以登录）
            if ($request->is('login') || $request->routeIs('login')) {
                return $next($request);
            }

            // 返回维护页面
            $maintenanceNotice = $settings['maintenance_notice'] ?? '系统正在维护中，请稍后再试。';

            return response()->view('errors.maintenance', [
                'message' => $maintenanceNotice,
                'siteName' => $settings['site_name'] ?? config('app.name'),
            ], 503);
        }

        return $next($request);
    }
}
