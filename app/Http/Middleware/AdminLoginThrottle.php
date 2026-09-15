<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminAccessLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class AdminLoginThrottle
{
    private const MAX_ADMIN_LOGIN_ATTEMPTS = 5;

    private const ADMIN_LOGIN_DECAY_MINUTES = 30;

    private const ADMIN_LOCKOUT_CACHE_PREFIX = 'admin_login_lockout:';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->is('login')) {
            return $next($request);
        }

        if ($this->isHoneypotTriggered($request)) {
            $this->logHoneypotTrigger($request);

            return back()->withInput($request->only('email', 'remember'));
        }

        if (! $this->isValidFormToken($request)) {
            $this->logInvalidFormToken($request);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => '表单验证失败，请重试。',
                ]);
        }

        $ip = $request->ip();
        $email = mb_strtolower((string) $request->input('email', ''));

        if ($this->isLockedOut($ip, $email)) {
            $this->logThrottledAttempt($request, $ip, $email);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => '管理员登录尝试次数过多，请稍后再试。',
                ]);
        }

        $response = $next($request);

        if ($this->wasAdminLoginFailed($response)) {
            $this->incrementAdminLoginAttempts($ip, $email);
        }

        if ($this->wasAdminLoginSuccessful($request)) {
            $this->clearAdminLoginAttempts($ip, $email);
        }

        return $response;
    }

    private function isHoneypotTriggered(Request $request): bool
    {
        $honeypot = $request->input('website');

        return is_string($honeypot) && $honeypot !== '';
    }

    private function isValidFormToken(Request $request): bool
    {
        $token = $request->input('_form_token');

        if (! is_string($token) || $token === '') {
            return true;
        }

        $ip = $request->ip();
        $now = now();
        $validCurrent = md5($ip . $now->format('YmdH'));
        $validPrevious = md5($ip . $now->subHour()->format('YmdH'));

        return $token === $validCurrent || $token === $validPrevious;
    }

    private function logHoneypotTrigger(Request $request): void
    {
        AdminAccessLog::query()->create([
            'user_id' => null,
            'ip_address' => $request->ip(),
            'device_fingerprint' => '',
            'browser_signature' => '',
            'action' => 'bot_detected',
            'path' => $request->path(),
            'details' => 'Honeypot field filled - likely bot',
            'request_headers' => [
                'user-agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'honeypot-value' => mb_substr((string) $request->input('website'), 0, 100),
            ],
            'accessed_at' => now(),
        ]);

        $this->incrementAdminLoginAttempts($request->ip(), '');
    }

    private function logInvalidFormToken(Request $request): void
    {
        AdminAccessLog::query()->create([
            'user_id' => null,
            'ip_address' => $request->ip(),
            'device_fingerprint' => '',
            'browser_signature' => '',
            'action' => 'invalid_token',
            'path' => $request->path(),
            'details' => 'Invalid form token - possible CSRF or replay attack',
            'request_headers' => [
                'user-agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ],
            'accessed_at' => now(),
        ]);
    }

    private function isLockedOut(string $ip, string $email): bool
    {
        $ipKey = self::ADMIN_LOCKOUT_CACHE_PREFIX.'ip:'.$ip;
        $emailKey = self::ADMIN_LOCKOUT_CACHE_PREFIX.'email:'.$email;

        $ipAttempts = (int) Cache::get($ipKey, 0);
        $emailAttempts = (int) Cache::get($emailKey, 0);

        return $ipAttempts >= self::MAX_ADMIN_LOGIN_ATTEMPTS
            || $emailAttempts >= self::MAX_ADMIN_LOGIN_ATTEMPTS;
    }

    private function incrementAdminLoginAttempts(string $ip, string $email): void
    {
        $decay = now()->addMinutes(self::ADMIN_LOGIN_DECAY_MINUTES);

        $ipKey = self::ADMIN_LOCKOUT_CACHE_PREFIX.'ip:'.$ip;
        Cache::put($ipKey, (int) Cache::get($ipKey, 0) + 1, $decay);

        if (! empty($email)) {
            $emailKey = self::ADMIN_LOCKOUT_CACHE_PREFIX.'email:'.$email;
            Cache::put($emailKey, (int) Cache::get($emailKey, 0) + 1, $decay);
        }
    }

    private function clearAdminLoginAttempts(string $ip, string $email): void
    {
        Cache::forget(self::ADMIN_LOCKOUT_CACHE_PREFIX.'ip:'.$ip);

        if (! empty($email)) {
            Cache::forget(self::ADMIN_LOCKOUT_CACHE_PREFIX.'email:'.$email);
        }
    }

    private function wasAdminLoginFailed(Response $response): bool
    {
        return $response->isRedirection()
            && session()->has('errors');
    }

    private function wasAdminLoginSuccessful(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && $user->is_admin;
    }

    private function logThrottledAttempt(Request $request, string $ip, string $email): void
    {
        AdminAccessLog::query()->create([
            'user_id' => null,
            'ip_address' => $ip,
            'device_fingerprint' => '',
            'browser_signature' => '',
            'action' => 'throttled',
            'path' => $request->path(),
            'details' => 'Admin login throttled',
            'request_headers' => [
                'user-agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'email-attempted' => $email,
            ],
            'accessed_at' => now(),
        ]);
    }
}
