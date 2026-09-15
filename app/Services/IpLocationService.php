<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class IpLocationService
{
    private const CACHE_TTL = 86400;

    private const RATE_LIMIT_KEY = 'geoip:amap_rate';

    private const RATE_LIMIT_PER_MINUTE = 40;

    private ?\ip2region\XdbSearcher $searcher = null;

    public function lookup(string $ip): ?array
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        if ($this->isPrivateIp($ip)) {
            return null;
        }

        $cacheKey = "geoip:v2:{$ip}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $result = $this->lookupIp2Region($ip);

        if ($result === null) {
            $result = $this->lookupAmap($ip);
        }

        Cache::put($cacheKey, $result ?: false, self::CACHE_TTL);

        return $result;
    }

    public function batchLookup(array $ips, int $delayMs = 50): array
    {
        $results = [];
        foreach ($ips as $ip) {
            $results[$ip] = $this->lookup($ip);
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return $results;
    }

    private function lookupIp2Region(string $ip): ?array
    {
        try {
            $searcher = $this->getSearcher();
            if ($searcher === null) {
                return null;
            }

            $region = $searcher->search($ip);

            if (empty($region)) {
                return null;
            }

            $parts = explode('|', $region);
            if (count($parts) < 5) {
                return null;
            }

            [$country, $area, $province, $city, $isp] = $parts;

            if ($province === '0' || $province === '') {
                $province = null;
            }
            if ($city === '0' || $city === '') {
                $city = null;
            }
            if ($isp === '0' || $isp === '') {
                $isp = null;
            }

            if ($country !== '中国') {
                return [
                    'country' => $country,
                    'city' => $city ?: $province,
                    'province' => $province,
                    'isp' => $isp,
                    'source' => 'ip2region',
                ];
            }

            return [
                'country' => '中国',
                'province' => $province,
                'city' => $city,
                'isp' => $isp,
                'source' => 'ip2region',
            ];
        } catch (\Throwable $e) {
            Log::debug('IP2Region lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function getSearcher(): ?\ip2region\XdbSearcher
    {
        if ($this->searcher !== null) {
            return $this->searcher;
        }

        $xdbPath = base_path('vendor/chinayin/ip2region/assets/ip2region.xdb');
        if (! file_exists($xdbPath)) {
            Log::warning('ip2region.xdb not found', ['path' => $xdbPath]);

            return null;
        }

        try {
            $this->searcher = \ip2region\XdbSearcher::newWithFileOnly($xdbPath);

            return $this->searcher;
        } catch (\Throwable $e) {
            Log::warning('Failed to init ip2region searcher', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function lookupAmap(string $ip): ?array
    {
        $key = (string) config('services.amap.key', '');
        if ($key === '') {
            return null;
        }

        if (! $this->checkAmapRateLimit()) {
            return null;
        }

        try {
            $url = sprintf('https://restapi.amap.com/v3/ip?ip=%s&key=%s', $ip, $key);
            $response = Http::timeout(3)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== '1') {
                return null;
            }

            $province = $data['province'] ?? null;
            $city = $data['city'] ?? null;

            if (is_array($province)) {
                $province = null;
            }
            if (is_array($city)) {
                $city = null;
            }

            return [
                'country' => '中国',
                'province' => $province ?: null,
                'city' => $city ?: null,
                'isp' => null,
                'source' => 'amap',
            ];
        } catch (\Throwable $e) {
            Log::debug('Amap IP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function checkAmapRateLimit(): bool
    {
        $current = (int) Cache::get(self::RATE_LIMIT_KEY, 0);

        if ($current >= self::RATE_LIMIT_PER_MINUTE) {
            return false;
        }

        Cache::put(self::RATE_LIMIT_KEY, $current + 1, 60);

        return true;
    }

    private function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
