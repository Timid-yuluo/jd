<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Admin\SystemSettingService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        $siteSettings = app(SystemSettingService::class)->all();
        $siteName = $siteSettings['site_name'] ?? config('app.name');
        $seoDescription = $siteSettings['seo_description'] ?? '';
        $seoKeywords = $siteSettings['seo_keywords'] ?? '';
        $faviconUrl = $siteSettings['favicon_url'] ?? '';

        return view('auth.forgot-password', compact('siteName', 'seoDescription', 'seoKeywords', 'faviconUrl'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $rateLimiter = app(RateLimiter::class);
        $throttleKey = 'password-reset:'.mb_strtolower((string) $request->input('email'));

        if ($rateLimiter->tooManyAttempts($throttleKey, 3)) {
            $seconds = $rateLimiter->availableIn($throttleKey);

            return back()->with('status', "如果该邮箱已注册，重置链接已发送，请检查收件箱。请 {$seconds} 秒后再试。");
        }

        $rateLimiter->hit($throttleKey, 600);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', '如果该邮箱已注册，重置链接已发送，请检查收件箱。');
    }
}
