<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Admin\SystemSettingService;
use App\Services\LoginHistoryService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $ipKey = 'login:ip:'.$request->ip();
        $emailKey = 'login:email:'.mb_strtolower((string) $request->input('email'));
        $rateLimiter = app(RateLimiter::class);

        $ipAttempts = $rateLimiter->attempts($ipKey);
        $emailAttempts = $rateLimiter->attempts($emailKey);
        $maxAttempts = $this->resolveMaxLoginAttempts();

        if ($ipAttempts >= $maxAttempts || $emailAttempts >= $maxAttempts) {
            $lockoutSeconds = $this->resolveLockoutSeconds(max($ipAttempts, $emailAttempts));

            throw ValidationException::withMessages([
                'email' => "登录尝试次数过多，请在 {$lockoutSeconds} 秒后重试。",
            ]);
        }

        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $decaySeconds = $this->resolveDecaySeconds(max($ipAttempts, $emailAttempts) + 1);
            $rateLimiter->hit($ipKey, $decaySeconds);
            $rateLimiter->hit($emailKey, $decaySeconds);

            $remainingAttempts = $maxAttempts - $ipAttempts - 1;

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => $remainingAttempts > 0
                        ? "账号或密码错误，还剩 {$remainingAttempts} 次尝试机会。"
                        : '账号或密码错误，账户已被临时锁定。',
                ]);
        }

        $rateLimiter->clear($ipKey);
        $rateLimiter->clear($emailKey);

        $user = auth()->user();
        if ($user !== null) {
            if ($user->isSuspended()) {
                Auth::logout();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => '账号已被封禁，请联系管理员处理。']);
            }

            if ($user->isPendingDeletion()) {
                Auth::logout();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors([
                        'email' => '您的账号已申请注销，正在冷静期内。如需恢复，请使用邮件中的恢复链接。',
                    ]);
            }

        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        app(LoginHistoryService::class)->record($user, $request, true);

        $request->session()->regenerate();

        return redirect()->intended(route('user.dashboard'));
    }

    public function confirmLogout(): View
    {
        return view('auth.logout');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        if ($user !== null) {
            app(LoginHistoryService::class)->recordLogout($user, $ip, $userAgent);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', '您已成功退出登录。');
    }

    private function resolveMaxLoginAttempts(): int
    {
        $raw = app(SystemSettingService::class)->get('max_login_attempts', '5');
        $value = (int) $raw;

        if ($value < 1) {
            return 5;
        }

        return min($value, 10);
    }

    private function resolveDecaySeconds(int $attempts): int
    {
        return match (true) {
            $attempts <= 3 => 60,
            $attempts <= 5 => 300,
            $attempts <= 10 => 900,
            $attempts <= 20 => 3600,
            default => 7200,
        };
    }

    private function resolveLockoutSeconds(int $attempts): int
    {
        return $this->resolveDecaySeconds($attempts);
    }
}
