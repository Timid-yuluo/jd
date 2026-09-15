<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Admin\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * 显示重置密码页面
     */
    public function create(Request $request): View
    {
        $siteSettings = app(SystemSettingService::class)->all();
        $siteName = $siteSettings['site_name'] ?? config('app.name');
        $seoDescription = $siteSettings['seo_description'] ?? '';
        $seoKeywords = $siteSettings['seo_keywords'] ?? '';
        $faviconUrl = $siteSettings['favicon_url'] ?? '';

        return view('auth.reset-password', compact('siteName', 'seoDescription', 'seoKeywords', 'faviconUrl') + [
            'token' => $request->route('token'),
            'email' => $request->email,
        ]);
    }

    /**
     * 重置密码
     */
    public function store(Request $request): RedirectResponse
    {
        $passwordMinLength = $this->resolvePasswordMinLength();
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', 'min:'.$passwordMinLength, Rules\Password::defaults()],
        ], [
            'password.min' => '密码长度不能少于 :min 位。',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->setRememberToken(\Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    private function resolvePasswordMinLength(): int
    {
        $raw = app(SystemSettingService::class)->get('password_min_length', '8');
        $value = (int) $raw;

        if ($value < 6) {
            return 8;
        }

        return min($value, 32);
    }
}
