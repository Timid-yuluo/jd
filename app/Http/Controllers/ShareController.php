<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class ShareController extends Controller
{
    /**
     * 查看分享简历（如有密码需验证）
     */
    public function show(Request $request, string $token)
    {
        $resume = Resume::where('share_token', $token)
            ->where('is_shareable', true)
            ->with('modules')
            ->firstOrFail();

        // 延迟递增分享浏览量，避免阻塞
        register_shutdown_function(function () use ($resume): void {
            try {
                Resume::where('id', $resume->id)->increment('share_view_count');
            } catch (\Throwable) {
                // 静默
            }
        });

        // 无密码保护，直接展示
        if (empty($resume->share_password)) {
            return view('share.resume', compact('resume'));
        }

        // 有密码保护，验证 session 中是否已通过
        $sessionKey = 'share_access_' . $resume->id;
        if ($request->session()->has($sessionKey)) {
            return view('share.resume', compact('resume'));
        }

        // 展示密码输入页
        return view('share.password', compact('resume', 'token'));
    }

    /**
     * 验证分享密码
     */
    public function verifyPassword(Request $request, string $token)
    {
        $request->validate(['password' => 'required|string|max:32']);

        // 限流：按 IP + token 组合限流，每个分享链接独立计数
        // 每分钟最多 5 次尝试，防止针对单个分享链接暴力破解
        $throttleKey = 'share_verify:' . $request->ip() . ':' . md5($token);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput()->withErrors(['password' => "尝试次数过多，请 {$seconds} 秒后再试"]);
        }
        RateLimiter::hit($throttleKey, 60);

        $resume = Resume::where('share_token', $token)
            ->where('is_shareable', true)
            ->firstOrFail();

        if (empty($resume->share_password)) {
            return redirect()->route('share.resume', $token);
        }

        if (! $resume->verifySharePassword((string) $request->input('password'))) {
            return back()->withInput()->withErrors(['password' => '访问密码错误']);
        }

        // 验证成功后清除该链接的限流计数，避免合法用户因历史失败被锁定
        RateLimiter::clear($throttleKey);

        $request->session()->put('share_access_' . $resume->id, true);

        return redirect()->route('share.resume', $token);
    }
}
