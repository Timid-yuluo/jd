<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Services\Admin\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final class OAuthStateService
{
    private const SESSION_KEY = 'oauth.pending_states';

    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function issue(Request $request, string $scene, string $provider): string
    {
        $expiresAt = now()->addSeconds($this->resolveTtlSeconds())->timestamp;
        $record = [
            'state' => '',
            'scene' => $scene,
            'provider' => $provider,
            'user_id' => $scene === 'binding' ? (int) ($request->user()?->id ?? 0) : null,
            'jti' => (string) Str::uuid(),
            'expires_at' => $expiresAt,
        ];
        $state = $this->buildSignedState($record);
        $record['state'] = $state;
        $pending = $this->all($request);
        $pending[$this->buildKey($scene, $provider)] = $record;
        $request->session()->put(self::SESSION_KEY, $pending);
        Cache::put($this->buildCacheKey($provider, $state), $record, now()->addSeconds($this->resolveTtlSeconds()));

        return $state;
    }

    public function consume(Request $request, string $scene, string $provider, ?string $state): bool
    {
        if (! is_string($state) || trim($state) === '') {
            return false;
        }

        $trimmedState = trim($state);
        $signedRecord = $this->decodeSignedState($provider, $trimmedState);
        if (is_array($signedRecord) && $this->recordMatches($signedRecord, $scene, $provider, $request)) {
            $ttl = max(60, ((int) $signedRecord['expires_at']) - now()->timestamp + 60);
            if (! Cache::add($this->buildConsumedStateKey($provider, (string) ($signedRecord['jti'] ?? '')), 1, now()->addSeconds($ttl))) {
                return false;
            }

            Cache::forget($this->buildCacheKey($provider, $trimmedState));
            $this->forgetSessionRecord($request, $scene, $provider);

            return true;
        }

        $cacheRecord = Cache::get($this->buildCacheKey($provider, $trimmedState));
        if (is_array($cacheRecord)) {
            if ($this->recordMatches($cacheRecord, $scene, $provider, $request)) {
                Cache::forget($this->buildCacheKey($provider, trim($state)));
                $this->forgetSessionRecord($request, $scene, $provider);

                return true;
            }
        }

        $key = $this->buildKey($scene, $provider);
        $pending = $this->all($request);
        $record = $pending[$key] ?? null;

        if (! is_array($record)) {
            return false;
        }

        $expiresAt = (int) ($record['expires_at'] ?? 0);
        if ($expiresAt <= 0 || now()->timestamp > $expiresAt) {
            unset($pending[$key]);
            $request->session()->put(self::SESSION_KEY, $pending);

            return false;
        }

        if (! hash_equals((string) ($record['state'] ?? ''), trim($state))) {
            return false;
        }

        unset($pending[$key]);
        $request->session()->put(self::SESSION_KEY, $pending);

        return true;
    }

    public function peek(Request $request, string $scene, string $provider, ?string $state): bool
    {
        if (! is_string($state) || trim($state) === '') {
            return false;
        }

        $trimmedState = trim($state);
        $signedRecord = $this->decodeSignedState($provider, $trimmedState);
        if (is_array($signedRecord) && $this->recordMatches($signedRecord, $scene, $provider, $request, false)) {
            return true;
        }

        $cacheRecord = Cache::get($this->buildCacheKey($provider, $trimmedState));
        if (is_array($cacheRecord) && $this->recordMatches($cacheRecord, $scene, $provider, $request, false)) {
            return true;
        }

        $key = $this->buildKey($scene, $provider);
        $pending = $this->all($request);
        $record = $pending[$key] ?? null;

        if (! is_array($record)) {
            return false;
        }

        $expiresAt = (int) ($record['expires_at'] ?? 0);
        if ($expiresAt <= 0 || now()->timestamp > $expiresAt) {
            return false;
        }

        return hash_equals((string) ($record['state'] ?? ''), $trimmedState);
    }

    public function detectScene(string $provider, ?string $state): ?string
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        $record = $this->decodeSignedState($provider, trim($state));
        if (! is_array($record)) {
            return null;
        }

        $scene = (string) ($record['scene'] ?? '');

        return in_array($scene, ['login', 'binding'], true) ? $scene : null;
    }

    public function extractBindingUserId(string $provider, ?string $state): ?int
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        $record = $this->decodeSignedState($provider, trim($state));
        if (! is_array($record) || ($record['scene'] ?? null) !== 'binding') {
            return null;
        }

        $userId = (int) ($record['user_id'] ?? 0);

        return $userId > 0 ? $userId : null;
    }

    /**
     * @return array<string, array{state:string,expires_at:int}>
     */
    private function all(Request $request): array
    {
        $value = $request->session()->get(self::SESSION_KEY, []);

        return is_array($value) ? $value : [];
    }

    private function buildKey(string $scene, string $provider): string
    {
        return sprintf('%s:%s', $scene, $provider);
    }

    private function buildCacheKey(string $provider, string $state): string
    {
        return sprintf('oauth:pending-state:%s:%s', $provider, hash('sha256', $state));
    }

    private function buildConsumedStateKey(string $provider, string $jti): string
    {
        return sprintf('oauth:consumed-state:%s:%s', $provider, $jti);
    }

    private function forgetSessionRecord(Request $request, string $scene, string $provider): void
    {
        $pending = $this->all($request);
        unset($pending[$this->buildKey($scene, $provider)]);
        $request->session()->put(self::SESSION_KEY, $pending);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function recordMatches(
        array $record,
        string $scene,
        string $provider,
        ?Request $request = null,
        bool $checkConsumed = true,
    ): bool {
        $expiresAt = (int) ($record['expires_at'] ?? 0);
        if ($expiresAt <= 0 || now()->timestamp > $expiresAt) {
            return false;
        }

        if (($record['scene'] ?? null) !== $scene || ($record['provider'] ?? null) !== $provider) {
            return false;
        }

        $userId = (int) ($record['user_id'] ?? 0);
        if ($scene === 'binding' && $userId > 0) {
            if (! $request?->user() || (int) $request->user()->id !== $userId) {
                return false;
            }
        }

        $jti = (string) ($record['jti'] ?? '');
        if ($checkConsumed && $jti !== '' && Cache::has($this->buildConsumedStateKey($provider, $jti))) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function buildSignedState(array $record): string
    {
        $payload = [
            'scene' => (string) ($record['scene'] ?? ''),
            'provider' => (string) ($record['provider'] ?? ''),
            'user_id' => (int) ($record['user_id'] ?? 0),
            'jti' => (string) ($record['jti'] ?? ''),
            'expires_at' => (int) ($record['expires_at'] ?? 0),
        ];

        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedPayload, $this->resolveSigningKey());

        return $encodedPayload.'.'.$signature;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeSignedState(string $provider, string $state): ?array
    {
        if (substr_count($state, '.') !== 1) {
            return null;
        }

        [$encodedPayload, $signature] = explode('.', $state, 2);
        if ($encodedPayload === '' || $signature === '') {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $encodedPayload, $this->resolveSigningKey());
        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decodedPayload = $this->base64UrlDecode($encodedPayload);
        if ($decodedPayload === null) {
            return null;
        }

        $payload = json_decode($decodedPayload, true);
        if (! is_array($payload)) {
            return null;
        }

        if (($payload['provider'] ?? null) !== $provider) {
            return null;
        }

        return $payload;
    }

    private function resolveSigningKey(): string
    {
        $key = Config::get('app.key');
        if (! is_string($key) || $key === '') {
            throw new \RuntimeException('APP_KEY must be set for OAuth state signing.');
        }

        return hash('sha256', $key);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    private function resolveTtlSeconds(): int
    {
        $raw = (int) $this->settings->get('auth_oauth_state_ttl_seconds', '600');

        if ($raw < 60) {
            return 600;
        }

        return min($raw, 1800);
    }
}
