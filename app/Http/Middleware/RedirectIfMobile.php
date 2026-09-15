<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfMobile
{
    /**
     * 检测移动端 User-Agent，跳转到提示页面。
     * 用户可通过 session 标记跳过此拦截。
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 排除 API 路径，避免移动端 API 请求被误重定向
        if ($request->is('api/*')) {
            return $next($request);
        }

        // 已选择继续访问，不再拦截
        if ($request->session()->has('mobile_continue')) {
            return $next($request);
        }

        // 当前已在提示页，避免循环
        if ($request->path() === 'mobile-tip') {
            return $next($request);
        }

        if ($this->isMobile($request->header('User-Agent', ''))) {
            // 仅传递相对路径给提示页，防止开放重定向
            $redirectPath = '/' . ltrim($request->path(), '/');
            $queryString = $request->getQueryString();
            $redirect = $redirectPath . ($queryString ? '?' . $queryString : '');
            return redirect()->route('mobile-tip', ['redirect' => $redirect]);
        }

        return $next($request);
    }

    private function isMobile(string $userAgent): bool
    {
        $ua = strtolower($userAgent);

        // 排除平板（iPad 等大屏设备可正常使用）
        if (preg_match('/(ipad|tablet|playbook)|(android(?!.*(mobi|opera mini)))/i', $ua)) {
            return false;
        }

        return (bool) preg_match(
            '/(android|iphone|ipod|mobile|phone|webos|bb10|blackberry|iemobile|opera mini|opera mobi|nokia|symbian|windows phone)/i',
            $ua
        );
    }
}
