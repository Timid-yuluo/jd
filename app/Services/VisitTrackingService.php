<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessPageVisitsJob;
use App\Models\PageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final class VisitTrackingService
{
    private const EXCLUDED_PATHS = [
        '_debugbar',
        'horizon',
        'telescope',
        'up',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
        'storage',
    ];

    private const EXCLUDED_EXTENSIONS = [
        '.css', '.js', '.map', '.png', '.jpg', '.jpeg', '.gif', '.svg',
        '.webp', '.ico', '.woff', '.woff2', '.ttf', '.eot', '.mp4',
        '.webm', '.mp3', '.pdf',
    ];

    private const SESSION_COOKIE_NAME = '_vsid';

    private const ONLINE_TTL = 300;

    private array $buffer = [];

    public function __construct(
        private readonly UserAgentParser $uaParser,
        private readonly IpLocationService $ipLocation,
    ) {}

    public function shouldTrack(Request $request): bool
    {
        if ($request->isMethod('HEAD')) {
            return false;
        }

        $path = $request->path();

        foreach (self::EXCLUDED_PATHS as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return false;
            }
        }

        $lowerPath = strtolower($path);
        foreach (self::EXCLUDED_EXTENSIONS as $ext) {
            if (str_ends_with($lowerPath, $ext)) {
                return false;
            }
        }

        $sampleRate = (float) config('tracking.sample_rate', 1.0);
        if ($sampleRate < 1.0 && mt_rand() / mt_getrandmax() > $sampleRate) {
            return false;
        }

        return true;
    }

    public function recordPageview(Request $request): void
    {
        $sessionId = $this->resolveSessionId($request);
        $userAgent = (string) ($request->header('User-Agent', ''));
        $realIp = $this->resolveRealIp($request);

        $parsed = $this->uaParser->parse($userAgent);
        $geo = $this->resolveGeo($realIp);

        $data = [
            'user_id' => $request->user()?->id,
            'session_id' => $sessionId,
            'path' => mb_substr($request->path(), 0, 500),
            'route_name' => $request->route()?->getName(),
            'method' => $request->method(),
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'real_ip' => $realIp ?: ($request->ip() ?? '0.0.0.0'),
            'user_agent' => mb_substr($userAgent, 0, 500),
            'device_type' => $parsed['device_type'],
            'device_brand' => $parsed['device_brand'],
            'browser' => $parsed['browser'],
            'browser_version' => $parsed['browser_version'],
            'os' => $parsed['os'],
            'os_version' => $parsed['os_version'],
            'is_bot' => $parsed['is_bot'] ? 1 : 0,
            'bot_name' => $parsed['bot_name'],
            'referer' => mb_substr((string) $request->header('Referer', ''), 0, 500),
            'country' => $geo['country'] ?? null,
            'city' => $geo['city'] ?? null,
            'isp' => $geo['isp'] ?? null,
            'event_type' => 'pageview',
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];

        $this->buffer[] = $data;

        if (! $parsed['is_bot']) {
            $this->touchOnlineUser($request, $sessionId);
        }

        if (count($this->buffer) >= 50) {
            $this->flushBuffer();
        }
    }

    public function recordEvent(Request $request, string $eventType, ?string $eventLabel = null, ?array $meta = null): void
    {
        $sessionId = $this->resolveSessionId($request);
        $userAgent = (string) ($request->header('User-Agent', ''));
        $realIp = $this->resolveRealIp($request);

        $parsed = $this->uaParser->parse($userAgent);
        $geo = $this->resolveGeo($realIp);

        // 使用缓冲批量写入，与 recordPageview 一致
        $this->buffer[] = [
            'user_id' => $request->user()?->id,
            'session_id' => $sessionId,
            'path' => mb_substr($request->input('path', $request->path()), 0, 500),
            'route_name' => $request->route()?->getName(),
            'method' => 'EVENT',
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'real_ip' => $realIp ?: ($request->ip() ?? '0.0.0.0'),
            'user_agent' => mb_substr($userAgent, 0, 500),
            'device_type' => $parsed['device_type'],
            'device_brand' => $parsed['device_brand'],
            'browser' => $parsed['browser'],
            'browser_version' => $parsed['browser_version'],
            'os' => $parsed['os'],
            'os_version' => $parsed['os_version'],
            'is_bot' => $parsed['is_bot'] ? 1 : 0,
            'bot_name' => $parsed['bot_name'],
            'country' => $geo['country'] ?? null,
            'city' => $geo['city'] ?? null,
            'isp' => $geo['isp'] ?? null,
            'event_type' => $eventType,
            'event_label' => $eventLabel ? mb_substr($eventLabel, 0, 255) : null,
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];

        if (count($this->buffer) >= 50) {
            $this->flushBuffer();
        }
    }

    public function updateDuration(string $sessionId, string $path, int $durationMs): void
    {
        PageVisit::query()
            ->where('session_id', $sessionId)
            ->where('path', $path)
            ->where('event_type', 'pageview')
            ->whereNull('duration_ms')
            ->latest('created_at')
            ->limit(1)
            ->update(['duration_ms' => min($durationMs, 3600000)]);
    }

    public function flush(): void
    {
        $this->flushBuffer();
    }

    private function flushBuffer(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $rows = $this->buffer;
        $this->buffer = [];

        try {
            // 异步分发到 tracking 队列，避免阻塞主请求
            ProcessPageVisitsJob::dispatch($rows);
        } catch (\Throwable $e) {
            // 队列不可用时降级为同步写入，保证数据不丢失
            Log::debug('PageVisit queue dispatch failed, fallback to sync insert', [
                'count' => count($rows),
                'error' => $e->getMessage(),
            ]);

            try {
                foreach (array_chunk($rows, 200) as $chunk) {
                    PageVisit::query()->insert($chunk);
                }
            } catch (\Throwable $fallbackException) {
                Log::debug('PageVisit fallback sync insert failed', [
                    'count' => count($rows),
                    'error' => $fallbackException->getMessage(),
                ]);
            }
        }
    }

    public function getOnlineCount(): int
    {
        return (int) Cache::get('visitor:online_count', 0);
    }

    public function resolveRealIp(Request $request): string
    {
        $cfIp = $request->header('CF-Connecting-IP');
        if ($cfIp && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }

        $realIp = $request->header('X-Real-IP');
        if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) && ! in_array($ip, ['127.0.0.1', '::1'], true)) {
                    return $ip;
                }
            }
        }

        return $request->ip() ?? '0.0.0.0';
    }

    private function resolveGeo(string $ip): ?array
    {
        return $this->ipLocation->lookup($ip);
    }

    private function resolveSessionId(Request $request): string
    {
        $cookieId = $request->cookie(self::SESSION_COOKIE_NAME);
        if (is_string($cookieId) && strlen($cookieId) === 64 && ctype_alnum($cookieId)) {
            return $cookieId;
        }

        return Str::random(64);
    }

    private function touchOnlineUser(Request $request, string $sessionId): void
    {
        // 使用 Redis Sorted Set 替代 Cache 数组，原子操作、无并发冲突
        $onlineKey = 'visitor:online_sessions';

        try {
            $now = time();
            $redis = Redis::connection();

            // 添加/更新 session 分数为当前时间戳
            $redis->zadd($onlineKey, $now, $sessionId);

            // 清除过期 session（分数 < cutoff）
            $cutoff = $now - self::ONLINE_TTL;
            $redis->zremrangebyscore($onlineKey, '-inf', (string) $cutoff);

            // 设置 key 过期时间，防止无限增长
            $redis->expire($onlineKey, self::ONLINE_TTL + 60);

            // 统计在线人数
            $count = $redis->zcard($onlineKey);
            Cache::put('visitor:online_count', $count, 60);
        } catch (\Throwable) {
            // 静默
        }
    }
}
