<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AccessBan;
use App\Models\AdminAccessLog;
use App\Models\User;
use App\Notifications\NewDeviceLoginNotification;
use App\Notifications\SecurityAlertNotification;
use App\Services\Security\DeviceFingerprintService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIsAdmin
{
    private const MAX_DENIED_ATTEMPTS_PER_IP = 10;

    private const MAX_DENIED_ATTEMPTS_PER_DEVICE = 8;

    private const AUTO_BAN_MINUTES = 60;

    private const SESSION_FP_KEY = '_admin_device_fp';

    private const SESSION_IP_KEY = '_admin_ip';

    private const SUSPICIOUS_PATTERNS = [
        '/\.env/i',
        '/wp-admin/i',
        '/wp-login/i',
        '/xmlrpc\.php/i',
        '/phpmyadmin/i',
        '/\.git/i',
        '/actuator/i',
        '/\.aws/i',
    ];

    /** @var array<string, mixed>|null 延迟到 terminate 阶段执行的任务 */
    private ?array $pendingTerminate = null;

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $fingerprintService = app(DeviceFingerprintService::class);
        $fingerprint = $fingerprintService->getFingerprint($request);
        $browserSignature = $fingerprintService->getBrowserSignature($request);
        $isWhitelisted = $this->isIpWhitelisted($ip);

        if ($this->isPathSuspicious($request->path())) {
            $this->logAccess($request, $ip, $fingerprint, $browserSignature, 'suspicious', 'Suspicious path probe: '.$request->path());
            if (! $isWhitelisted) {
                $this->checkAndAutoBan($ip, $fingerprint, $browserSignature);
            }
            abort(404);
        }

        if ($fingerprintService->isIpBanned($ip)) {
            $this->logAccess($request, $ip, $fingerprint, $browserSignature, 'banned', 'IP is banned');
            Log::warning('Banned IP attempted admin access', ['ip' => $ip, 'path' => $request->path()]);
            abort(403, 'Access denied.');
        }

        if ($fingerprintService->isFingerprintBanned($fingerprint)) {
            $this->logAccess($request, $ip, $fingerprint, $browserSignature, 'banned', 'Device is banned');
            Log::warning('Banned device attempted admin access', ['ip' => $ip, 'fp' => $fingerprint]);
            abort(403, 'Access denied.');
        }

        if ($fingerprintService->isBrowserBanned($browserSignature)) {
            $this->logAccess($request, $ip, $fingerprint, $browserSignature, 'banned', 'Browser env is banned');
            Log::warning('Banned browser attempted admin access', ['ip' => $ip, 'bs' => $browserSignature]);
            abort(403, 'Access denied.');
        }

        $user = $request->user();

        if (! $user || ! $user->is_admin || ! $user->can('access admin')) {
            $this->logAccess($request, $ip, $fingerprint, $browserSignature, 'denied', 'Non-admin access attempt');

            if (! $isWhitelisted && config('admin.security.auto_ban_enabled', true)) {
                $this->checkAndAutoBan($ip, $fingerprint, $browserSignature);
            }

            Log::warning('Non-admin attempted admin access', [
                'user_id' => $user?->id,
                'ip' => $ip,
                'fingerprint' => $fingerprint,
                'path' => $request->path(),
            ]);

            abort(403, 'No permission to access admin panel.');
        }

        if (config('admin.security.session_device_binding', true)) {
            $this->enforceSessionDeviceBinding($request, $user, $fingerprint, $ip);
        }

        $this->enforceAdminSessionTimeout($request, $user);

        if (config('admin.security.log_successful_access', true)) {
            $this->deferLogAccess($request, $ip, $fingerprint, $browserSignature, 'access');
        }

        return $next($request);
    }

    /**
     * 响应发送后执行延迟任务（日志写入、设备通知等）
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($this->pendingTerminate === null) {
            return;
        }

        try {
            // 执行延迟的日志写入
            if (isset($this->pendingTerminate['log_access'])) {
                $this->executeLogAccess($this->pendingTerminate['log_access']);
            }

            // 执行延迟的新设备通知
            if (isset($this->pendingTerminate['notify_new_device'])) {
                $data = $this->pendingTerminate['notify_new_device'];
                $this->notifyNewDeviceLogin($data['user'], $data['ip'], $data['request']);
            }
        } catch (\Throwable $e) {
            Log::warning('EnsureIsAdmin terminate failed', ['error' => $e->getMessage()]);
        }
    }

    private function enforceAdminSessionTimeout(Request $request, $user): void
    {
        $timeoutMinutes = (int) config('admin.security.session_timeout_minutes', 120);

        if ($timeoutMinutes <= 0) {
            return;
        }

        $session = $request->session();
        $lastActivity = $session->get('_admin_last_activity');

        if ($lastActivity !== null) {
            $elapsed = now()->diffInMinutes($lastActivity);

            if ($elapsed >= $timeoutMinutes) {
                Log::info('Admin session timed out', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'idle_minutes' => $elapsed,
                ]);

                $session->invalidate();
                $session->regenerateToken();

                abort(403, 'Admin session expired. Please login again.');
            }
        }

        $session->put('_admin_last_activity', now());
    }

    private function isIpWhitelisted(string $ip): bool
    {
        $whitelist = config('admin.security.ip_whitelist', []);

        if (empty($whitelist)) {
            return false;
        }

        return in_array($ip, $whitelist, true);
    }

    private function enforceSessionDeviceBinding(Request $request, $user, string $fingerprint, string $ip): void
    {
        $session = $request->session();
        $boundFp = $session->get(self::SESSION_FP_KEY);
        $boundIp = $session->get(self::SESSION_IP_KEY);

        if ($boundFp === null) {
            $session->put(self::SESSION_FP_KEY, $fingerprint);
            $session->put(self::SESSION_IP_KEY, $ip);

            // 延迟到 terminate 阶段发送新设备通知
            $this->pendingTerminate['notify_new_device'] = [
                'user' => $user,
                'ip' => $ip,
                'request' => $request,
            ];

            return;
        }

        if ($boundFp !== $fingerprint) {
            Log::alert('Session device fingerprint mismatch - possible hijacking', [
                'user_id' => $user->id,
                'bound_fp' => $boundFp,
                'current_fp' => $fingerprint,
                'bound_ip' => $boundIp,
                'current_ip' => $ip,
                'path' => $request->path(),
            ]);

            $this->logAccess($request, $ip, $fingerprint, '', 'hijack', 'Session device fingerprint mismatch');

            $session->invalidate();
            $session->regenerateToken();

            abort(403, 'Session security violation. Please login again.');
        }

        if ($boundIp !== $ip && ! $this->isIpInSameSubnet($boundIp, $ip)) {
            Log::warning('Session IP changed significantly', [
                'user_id' => $user->id,
                'bound_ip' => $boundIp,
                'current_ip' => $ip,
                'fp' => $fingerprint,
            ]);

            $session->put(self::SESSION_IP_KEY, $ip);
        }
    }

    private function notifyNewDeviceLogin($user, string $ip, Request $request): void
    {
        $lastDeviceCacheKey = "admin_last_device:{$user->id}";
        $lastDevice = Cache::get($lastDeviceCacheKey);

        $ua = $request->userAgent() ?? 'Unknown';
        $deviceInfo = $this->parseUserAgent($ua);

        if ($lastDevice && $lastDevice === $ip.':'.$deviceInfo['platform']) {
            return;
        }

        Cache::put($lastDeviceCacheKey, $ip.':'.$deviceInfo['platform'], now()->addDays(30));

        $user->notify(new NewDeviceLoginNotification(
            ip: $ip,
            device: $deviceInfo['platform'],
            browser: $deviceInfo['browser'],
        ));
    }

    private function parseUserAgent(string $ua): array
    {
        $browser = 'Unknown';
        $platform = 'Unknown';

        if (preg_match('/Chrome\/([\d.]+)/i', $ua) && ! preg_match('/Edg\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Edg\/([\d.]+)/i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/Firefox\/([\d.]+)/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([\d.]+)/i', $ua) && ! preg_match('/Chrome/i', $ua)) {
            $browser = 'Safari';
        }

        if (preg_match('/Windows NT/i', $ua)) {
            $platform = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $ua) && ! preg_match('/Android/i', $ua)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $ua)) {
            $platform = 'Android';
        } elseif (preg_match('/iPhone|iPad/i', $ua)) {
            $platform = 'iOS';
        }

        return ['browser' => $browser, 'platform' => $platform];
    }

    private function isIpInSameSubnet(string $ip1, string $ip2): bool
    {
        $long1 = ip2long($ip1);
        $long2 = ip2long($ip2);

        if ($long1 === false || $long2 === false) {
            return false;
        }

        return ($long1 & 0xFFFFFF00) === ($long2 & 0xFFFFFF00);
    }

    private function isPathSuspicious(string $path): bool
    {
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function checkAndAutoBan(string $ip, string $fingerprint, string $browserSignature): void
    {
        if (! config('admin.security.auto_ban_enabled', true)) {
            return;
        }

        $bypassAutoBan = config('admin.security.ip_whitelist_bypass_auto_ban', true)
            && $this->isIpWhitelisted($ip);

        if ($bypassAutoBan) {
            return;
        }

        $ipDeniedCount = AdminAccessLog::query()
            ->byIp($ip)
            ->denied()
            ->recent(self::AUTO_BAN_MINUTES)
            ->count();

        if ($ipDeniedCount >= self::MAX_DENIED_ATTEMPTS_PER_IP) {
            $reason = sprintf('Auto-ban: %d denied attempts in %d min', $ipDeniedCount, self::AUTO_BAN_MINUTES);
            $this->autoBan('ip', $ip, $reason);
            Cache::forget("ban_check:ip:{$ip}");

            return;
        }

        $deviceDeniedCount = AdminAccessLog::query()
            ->byDevice($fingerprint)
            ->denied()
            ->recent(self::AUTO_BAN_MINUTES)
            ->count();

        if ($deviceDeniedCount >= self::MAX_DENIED_ATTEMPTS_PER_DEVICE) {
            $reason = sprintf('Auto-ban: %d denied attempts in %d min', $deviceDeniedCount, self::AUTO_BAN_MINUTES);
            $this->autoBan('device', $fingerprint, $reason);
            Cache::forget("ban_check:device:{$fingerprint}");

            return;
        }

        $browserDeniedCount = AdminAccessLog::query()
            ->where('browser_signature', $browserSignature)
            ->denied()
            ->recent(self::AUTO_BAN_MINUTES)
            ->count();

        if ($browserDeniedCount >= self::MAX_DENIED_ATTEMPTS_PER_DEVICE) {
            $reason = sprintf('Auto-ban: %d denied attempts in %d min', $browserDeniedCount, self::AUTO_BAN_MINUTES);
            $this->autoBan('browser', $browserSignature, $reason);
            Cache::forget("ban_check:browser:{$browserSignature}");
        }
    }

    private function autoBan(string $type, string $value, string $reason): void
    {
        $existing = AccessBan::query()
            ->active()
            ->where('ban_type', $type)
            ->where('ban_value', $value)
            ->exists();

        if ($existing) {
            return;
        }

        AccessBan::query()->create([
            'ban_type' => $type,
            'ban_value' => $value,
            'reason' => $reason,
            'severity' => 'block',
            'expires_at' => now()->addHours(24),
        ]);

        Log::alert('Auto-ban triggered', [
            'type' => $type,
            'value' => $value,
            'reason' => $reason,
        ]);

        Cache::forget("ban_check:{$type}:{$value}");

        $this->notifySuperAdmins($type, $value, $reason);
    }

    private function notifySuperAdmins(string $type, string $value, string $reason): void
    {
        $superAdmins = User::query()
            ->where('is_admin', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->get();

        foreach ($superAdmins as $admin) {
            $admin->notify(new SecurityAlertNotification(
                alertType: 'auto_ban',
                severity: 'high',
                message: $reason,
                details: [
                    'ban_type' => $type,
                    'ban_value' => $value,
                    'triggered_at' => now()->toIso8601String(),
                ],
            ));
        }
    }

    private function logAccess(
        Request $request,
        string $ip,
        string $fingerprint,
        string $browserSignature,
        string $action,
        ?string $details = null
    ): void {
        if ($action === 'access' && ! $this->shouldLogSuccessfulAccess($request, $ip, $fingerprint)) {
            return;
        }

        AdminAccessLog::query()->create([
            'user_id' => $request->user()?->id,
            'ip_address' => $ip,
            'device_fingerprint' => $fingerprint,
            'browser_signature' => $browserSignature,
            'action' => $action,
            'path' => $request->path(),
            'details' => $details,
            'request_headers' => $action !== 'access' ? $this->extractKeyHeaders($request) : null,
            'accessed_at' => now(),
        ]);

        if ($action === 'access') {
            $this->recordLoggedPath($ip, $fingerprint, $request->path());
        }
    }

    /**
     * 延迟日志写入到 terminate 阶段
     */
    private function deferLogAccess(Request $request, string $ip, string $fingerprint, string $browserSignature, string $action, ?string $details = null): void
    {
        if ($action === 'access' && ! $this->shouldLogSuccessfulAccess($request, $ip, $fingerprint)) {
            return;
        }

        $this->pendingTerminate ??= [];
        $this->pendingTerminate['log_access'] = [
            'user_id' => $request->user()?->id,
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'browser_signature' => $browserSignature,
            'action' => $action,
            'path' => $request->path(),
            'details' => $details,
            'headers' => $action !== 'access' ? $this->extractKeyHeaders($request) : null,
        ];
    }

    /**
     * 执行延迟的日志写入
     */
    private function executeLogAccess(array $data): void
    {
        AdminAccessLog::query()->create([
            'user_id' => $data['user_id'],
            'ip_address' => $data['ip'],
            'device_fingerprint' => $data['fingerprint'],
            'browser_signature' => $data['browser_signature'],
            'action' => $data['action'],
            'path' => $data['path'],
            'details' => $data['details'],
            'request_headers' => $data['headers'],
            'accessed_at' => now(),
        ]);

        if ($data['action'] === 'access') {
            $this->recordLoggedPath($data['ip'], $data['fingerprint'], $data['path']);
        }
    }

    private function shouldLogSuccessfulAccess(Request $request, string $ip, string $fingerprint): bool
    {
        $cacheKey = "admin_log_throttle:{$ip}:{$fingerprint}";
        $path = $request->path();

        if (Cache::has($cacheKey)) {
            $logged = Cache::get($cacheKey, []);

            return ! in_array($path, $logged, true);
        }

        return true;
    }

    private function recordLoggedPath(string $ip, string $fingerprint, string $path): void
    {
        $cacheKey = "admin_log_throttle:{$ip}:{$fingerprint}";
        $logged = Cache::get($cacheKey, []);
        $logged[] = $path;
        Cache::put($cacheKey, array_slice($logged, -20), now()->addMinutes(5));
    }

    private function extractKeyHeaders(Request $request): array
    {
        $headers = [];
        $important = ['user-agent', 'referer', 'accept-language', 'x-forwarded-for', 'x-real-ip', 'sec-ch-ua', 'sec-ch-ua-platform'];

        foreach ($important as $header) {
            if ($request->headers->has($header)) {
                $headers[$header] = mb_substr((string) $request->headers->get($header), 0, 500);
            }
        }

        return $headers;
    }
}
