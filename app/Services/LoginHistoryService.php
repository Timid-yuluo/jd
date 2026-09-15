<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RecordLoginHistoryJob;
use App\Models\User;
use Illuminate\Http\Request;

final class LoginHistoryService
{
    /**
     * 异步记录登录历史（不阻塞主请求）
     */
    public function record(User $user, Request $request, bool $isSuccess = true): void
    {
        $userAgent = mb_substr((string) $request->header('User-Agent', ''), 0, 500);

        RecordLoginHistoryJob::dispatch([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $this->detectDeviceType($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOS($userAgent),
            'is_success' => $isSuccess,
            'event_type' => $isSuccess ? 'login' : 'failed',
            'cleanup' => true,
        ]);
    }

    /**
     * 异步记录登出历史
     */
    public function recordLogout(User $user, ?string $ip, ?string $userAgent): void
    {
        $agent = mb_substr((string) $userAgent, 0, 500);

        RecordLoginHistoryJob::dispatch([
            'user_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $agent,
            'device_type' => $this->detectDeviceType($agent),
            'browser' => $this->detectBrowser($agent),
            'os' => $this->detectOS($agent),
            'is_success' => true,
            'event_type' => 'logout',
            'cleanup' => true,
        ]);
    }

    private function detectDeviceType(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $agent)) {
            return 'tablet';
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function detectBrowser(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        if (str_contains($agent, 'chrome') && ! str_contains($agent, 'edg')) {
            return 'Chrome';
        }
        if (str_contains($agent, 'firefox')) {
            return 'Firefox';
        }
        if (str_contains($agent, 'safari') && ! str_contains($agent, 'chrome')) {
            return 'Safari';
        }
        if (str_contains($agent, 'edg')) {
            return 'Edge';
        }

        return 'Other';
    }

    private function detectOS(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        if (str_contains($agent, 'windows')) {
            return 'Windows';
        }
        if (str_contains($agent, 'macintosh') || str_contains($agent, 'mac os')) {
            return 'macOS';
        }
        if (str_contains($agent, 'linux')) {
            return 'Linux';
        }
        if (str_contains($agent, 'android')) {
            return 'Android';
        }
        if (str_contains($agent, 'iphone') || str_contains($agent, 'ipad')) {
            return 'iOS';
        }

        return 'Other';
    }
}
