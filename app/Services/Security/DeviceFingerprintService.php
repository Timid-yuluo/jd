<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class DeviceFingerprintService
{
    private const FINGERPRINT_HEADER = 'X-Device-Fingerprint';

    private const FINGERPRINT_COOKIE = '_dfp';

    private const BROWSER_SIGNATURE_HEADERS = [
        'user-agent',
        'accept-language',
        'accept-encoding',
        'sec-ch-ua',
        'sec-ch-ua-platform',
        'sec-ch-ua-mobile',
    ];

    public function getFingerprint(Request $request): string
    {
        $clientFingerprint = $request->header(self::FINGERPRINT_HEADER);

        if ($clientFingerprint && $this->isValidClientFingerprint($clientFingerprint)) {
            return $this->hashFingerprint($clientFingerprint);
        }

        $cookieFingerprint = $request->cookie(self::FINGERPRINT_COOKIE);

        if (is_string($cookieFingerprint) && $this->isValidClientFingerprint($cookieFingerprint)) {
            return $this->hashFingerprint($cookieFingerprint);
        }

        return $this->generateServerSideFingerprint($request);
    }

    public function getBrowserSignature(Request $request): string
    {
        $components = [];

        foreach (self::BROWSER_SIGNATURE_HEADERS as $header) {
            $value = $request->headers->get($header);
            if ($value !== null) {
                $components[$header] = $value;
            }
        }

        $components['ip_prefix'] = $this->getIpPrefix($request->ip());

        return hash('sha256', json_encode($components, JSON_UNESCAPED_UNICODE));
    }

    public function isFingerprintBanned(string $fingerprint): bool
    {
        return Cache::remember(
            "ban_check:device:{$fingerprint}",
            now()->addMinutes(5),
            fn () => \App\Models\AccessBan::query()
                ->active()
                ->where('ban_type', 'device')
                ->where('ban_value', $fingerprint)
                ->exists()
        );
    }

    public function isBrowserBanned(string $signature): bool
    {
        return Cache::remember(
            "ban_check:browser:{$signature}",
            now()->addMinutes(5),
            fn () => \App\Models\AccessBan::query()
                ->active()
                ->where('ban_type', 'browser')
                ->where('ban_value', $signature)
                ->exists()
        );
    }

    public function isIpBanned(string $ip): bool
    {
        return Cache::remember(
            "ban_check:ip:{$ip}",
            now()->addMinutes(5),
            fn () => $this->checkIpBanned($ip)
        );
    }

    private function checkIpBanned(string $ip): bool
    {
        $exactMatch = \App\Models\AccessBan::query()
            ->active()
            ->where('ban_type', 'ip')
            ->where('ban_value', $ip)
            ->exists();

        if ($exactMatch) {
            return true;
        }

        $cidrBans = \App\Models\AccessBan::query()
            ->active()
            ->where('ban_type', 'ip_range')
            ->pluck('ban_value');

        foreach ($cidrBans as $cidr) {
            if ($this->ipMatchesCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (str_contains($cidr, '/')) {
            [$subnet, $mask] = explode('/', $cidr, 2);
            $mask = (int) $mask;

            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);

            if ($ipLong === false || $subnetLong === false || $mask < 0 || $mask > 32) {
                return false;
            }

            $maskLong = $mask === 0 ? 0 : (~0 << (32 - $mask));

            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        $wildcardPos = strpos($cidr, '*');
        if ($wildcardPos !== false) {
            $prefix = substr($cidr, 0, $wildcardPos);

            return str_starts_with($ip, $prefix);
        }

        return $ip === $cidr;
    }

    private function isValidClientFingerprint(string $fingerprint): bool
    {
        $parts = explode('.', $fingerprint, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $hash = $parts[0];
        if (! preg_match('/^[0-9a-f]{8}$/i', $hash)) {
            return false;
        }

        return strlen($parts[1]) <= 128;
    }

    private function hashFingerprint(string $clientFingerprint): string
    {
        return hash('sha256', $clientFingerprint . config('app.key'));
    }

    private function generateServerSideFingerprint(Request $request): string
    {
        $components = [
            'ua' => $request->userAgent() ?? '',
            'ip' => $request->ip(),
            'lang' => $request->headers->get('accept-language', ''),
            'encoding' => $request->headers->get('accept-encoding', ''),
            'platform' => $request->headers->get('sec-ch-ua-platform', ''),
        ];

        return hash('sha256', json_encode($components, JSON_UNESCAPED_UNICODE) . config('app.key'));
    }

    private function getIpPrefix(?string $ip): string
    {
        if ($ip === null) {
            return '';
        }

        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0].'.'.$parts[1];
        }

        return $ip;
    }
}
