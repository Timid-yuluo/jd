<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ThrottlePdfDownload
{
    public function __construct(
        private readonly RateLimiter $rateLimiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $userId = $request->user()?->id;
        $deviceId = $this->deviceFingerprint($request);

        // 检查该设备是否已被封禁
        $banKey = 'print-pdf-ban:dev:'.$deviceId;
        if ($this->rateLimiter->tooManyAttempts($banKey, 1)) {
            $banSeconds = $this->rateLimiter->availableIn($banKey);

            return $this->banResponse($banSeconds);
        }

        // 正常限流：1次/分钟/设备
        $normalKey = 'print-pdf:'.$deviceId;
        if ($this->rateLimiter->tooManyAttempts($normalKey, 1)) {
            $retryAfter = $this->rateLimiter->availableIn($normalKey);

            return $this->tooManyResponse($retryAfter);
        }

        // 滥用量：10次/分钟/IP → 封禁该设备 24小时
        $abuseKey = 'print-pdf-abuse:ip:'.$ip;
        if ($this->rateLimiter->tooManyAttempts($abuseKey, 10)) {
            $this->rateLimiter->hit($banKey, 86400);
            $this->rateLimiter->clear($abuseKey);

            return $this->banResponse(86400);
        }

        $this->rateLimiter->hit($normalKey, 60);
        $this->rateLimiter->hit($abuseKey, 60);

        return $next($request);
    }

    /**
     * 设备指纹：User-Agent 哈希 + 语言偏好 + IP 网段
     */
    private function deviceFingerprint(Request $request): string
    {
        $ua = $request->header('User-Agent', '');
        $lang = $request->header('Accept-Language', '');
        $ipPrefix = substr((string) $request->ip(), 0, (int) strrpos((string) $request->ip(), '.') ?: 0);

        $fingerprint = $ipPrefix.'|'.$lang.'|'.$ua;

        return hash('sha256', $fingerprint);
    }

    private function tooManyResponse(int $retryAfter): Response
    {
        return response()->make('请求过于频繁，请在 '.$retryAfter.' 秒后重试。', 429, [
            'Retry-After' => (string) $retryAfter,
        ]);
    }

    private function banResponse(int $banSeconds): Response
    {
        $hours = (int) ceil($banSeconds / 3600);

        return response()->make("操作过于频繁，该设备已被禁止下载操作 {$hours} 小时。", 403, [
            'Retry-After' => (string) $banSeconds,
        ]);
    }
}
