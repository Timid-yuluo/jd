<?php

declare(strict_types=1);

namespace App\Infrastructure\AI;

use App\Enums\AiCacheScenario;
use App\Infrastructure\AI\Contracts\AiProvider;
use App\Infrastructure\AI\Providers\DeepSeekAiProvider;
use App\Infrastructure\AI\Providers\VolcanoAiProvider;
use App\Infrastructure\AI\Providers\ZhipuAiProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Throwable;

class AiManager extends Manager
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_DRIVERS = ['deepseek', 'volcano', 'zhipu'];

    public function getDefaultDriver(): string
    {
        $defaultDriver = strtolower(trim((string) $this->config->get('ai.default', 'zhipu')));
        $enabledDrivers = $this->enabledDrivers();

        if (in_array($defaultDriver, $enabledDrivers, true)) {
            return $defaultDriver;
        }

        return $enabledDrivers[0] ?? 'zhipu';
    }

    protected function createDeepseekDriver(): AiProvider
    {
        $config = $this->config->get('ai.providers.deepseek', []);
        if (! $this->isProviderEnabled('deepseek')) {
            throw new InvalidArgumentException('DeepSeek Provider is disabled.');
        }

        if (empty($config['api_key'])) {
            throw new InvalidArgumentException('DeepSeek API key is not configured.');
        }

        return new DeepSeekAiProvider(
            $config['base_url'] ?? 'https://api.deepseek.com',
            $config['api_key'],
            $config['model'] ?? 'deepseek-v4-flash'
        );
    }

    protected function createVolcanoDriver(): AiProvider
    {
        $config = $this->config->get('ai.providers.volcano', []);
        if (! $this->isProviderEnabled('volcano')) {
            throw new InvalidArgumentException('Volcano Engine Provider is disabled.');
        }

        if (empty($config['api_key'])) {
            throw new InvalidArgumentException('Volcano Engine API key is not configured.');
        }

        return new VolcanoAiProvider(
            $config['base_url'] ?? 'https://ark.cn-beijing.volces.com/api/v3',
            $config['api_key'],
            $config['model'] ?? 'doubao-lite-4k'
        );
    }

    protected function createZhipuDriver(): AiProvider
    {
        $config = $this->config->get('ai.providers.zhipu', []);
        if (! $this->isProviderEnabled('zhipu')) {
            throw new InvalidArgumentException('Zhipu AI Provider is disabled.');
        }

        if (empty($config['api_key'])) {
            throw new InvalidArgumentException('Zhipu AI API key is not configured.');
        }

        return new ZhipuAiProvider(
            $config['base_url'] ?? 'https://open.bigmodel.cn/api/paas/v4',
            $config['api_key'],
            $config['model'] ?? 'glm-4.5-flash',
            is_array($config['model_fallbacks'] ?? null) ? $config['model_fallbacks'] : []
        );
    }

    public function provider(?string $driver = null): AiProvider
    {
        $requestedDriver = strtolower(trim((string) ($driver ?? $this->getDefaultDriver())));
        if (! in_array($requestedDriver, self::SUPPORTED_DRIVERS, true)) {
            throw new InvalidArgumentException("Driver [{$requestedDriver}] not supported.");
        }

        if (! $this->isProviderEnabled($requestedDriver)) {
            throw new InvalidArgumentException("AI Provider [{$requestedDriver}] is disabled.");
        }

        return $this->driver($requestedDriver);
    }

    /**
     * 获取有序的驱动列表，自动过滤熔断中的驱动
     *
     * @return array<int,string>
     */
    public function orderedDrivers(?string $driver = null): array
    {
        $primaryDriver = $driver ?? $this->getDefaultDriver();
        $drivers = [];

        if (is_string($primaryDriver) && $primaryDriver !== '' && $this->isProviderEnabled($primaryDriver)) {
            $drivers[] = $primaryDriver;
        }

        $fallbackChain = $this->config->get('ai.fallback_chain', []);
        foreach ($fallbackChain as $fallback) {
            if (! is_string($fallback) || $fallback === '') {
                continue;
            }
            if (in_array($fallback, $drivers, true)) {
                continue;
            }
            if (! in_array($fallback, self::SUPPORTED_DRIVERS, true)) {
                continue;
            }
            if (! $this->isProviderEnabled($fallback)) {
                continue;
            }

            $drivers[] = $fallback;
        }

        // 过滤熔断中的驱动：若所有驱动均熔断则保留全部（降级处理）
        $availableDrivers = array_values(array_filter(
            $drivers,
            fn (string $d): bool => ! $this->isDriverCircuitOpen($d)
        ));

        if ($availableDrivers !== []) {
            return $availableDrivers;
        }

        // 所有驱动均熔断，记录告警并返回全部驱动（降级尝试）
        Log::warning('All AI drivers circuit open, falling back to degraded mode', [
            'drivers' => $drivers,
        ]);

        return $drivers;
    }

    public function isRateLimitException(Throwable $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'rate limit')
            || str_contains($message, 'too many requests')
            || str_contains($message, '请求频率')
            || str_contains($message, '达到速率限制')
            || str_contains($message, '速率限制')
            || str_contains($message, '限流')
            || str_contains($message, '"code":"1302"')
            || str_contains($message, "'code':'1302'")
            || str_contains($message, ' code 1302')
            || str_contains($message, '"status":429')
            || str_contains($message, "'status':429")
            || str_contains($message, ' status 429')
            || str_contains($message, 'http 429')
            || str_contains($message, 'http code 429');
    }

    public function shouldRetryWithFallback(Throwable $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return $this->isRateLimitException($exception)
            || str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'connection')
            || str_contains($message, 'connect')
            || str_contains($message, 'stream request failed')
            || str_contains($message, 'request failed');
    }

    /**
     * @return array{type:string,retryable:bool}
     */
    public function classifyException(Throwable $exception): array
    {
        $message = mb_strtolower($exception->getMessage());

        if ($this->isRateLimitException($exception)) {
            return ['type' => 'rate_limited', 'retryable' => true];
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return ['type' => 'timeout', 'retryable' => true];
        }

        if (str_contains($message, 'connection') || str_contains($message, 'connect')) {
            return ['type' => 'connection', 'retryable' => true];
        }

        if (str_contains($message, 'circuit open')) {
            return ['type' => 'circuit_open', 'retryable' => true];
        }

        if (str_contains($message, 'invalid ai response format') || str_contains($message, 'syntax error')) {
            return ['type' => 'invalid_response', 'retryable' => false];
        }

        if (str_contains($message, 'not configured')
            || str_contains($message, 'is disabled')
            || str_contains($message, 'not supported')
            || str_contains($message, 'all ai provider')
            || str_contains($message, '没有已开启的 ai provider 可用')) {
            return ['type' => 'provider_unavailable', 'retryable' => false];
        }

        if (str_contains($message, 'request failed')) {
            return ['type' => 'request_failed', 'retryable' => true];
        }

        return [
            'type' => 'unknown',
            'retryable' => $this->shouldRetryWithFallback($exception),
        ];
    }

    public function shouldRetrySameDriverAfterBackoff(string $driver, Throwable $exception, int $attemptNumber): bool
    {
        $normalizedDriver = strtolower(trim($driver));
        if ($normalizedDriver !== 'zhipu') {
            return false;
        }

        if (! $this->isRateLimitException($exception)) {
            return false;
        }

        $maxAttempts = max(1, (int) $this->config->get('ai.rate_limit.zhipu_max_attempts', 2));

        return $attemptNumber < $maxAttempts;
    }

    public function resolveBackoffMilliseconds(Throwable $exception, int $attemptNumber = 1): int
    {
        $explicitMs = $this->extractRetryAfterMilliseconds($exception);
        if ($explicitMs !== null) {
            $maxMs = max(500, (int) $this->config->get('ai.rate_limit.max_backoff_ms', 4000));

            return max(200, min($maxMs, $explicitMs));
        }

        $initialMs = max(200, (int) $this->config->get('ai.rate_limit.initial_backoff_ms', 1200));
        $maxMs = max($initialMs, (int) $this->config->get('ai.rate_limit.max_backoff_ms', 4000));
        $multiplier = max(1, (int) $this->config->get('ai.rate_limit.multiplier', 2));
        $delayMs = (int) round($initialMs * ($multiplier ** max(0, $attemptNumber - 1)));

        return max(200, min($maxMs, $delayMs));
    }

    public function isDriverCircuitOpen(string $driver, string $scenario = 'default'): bool
    {
        return Cache::has($this->circuitOpenKey($driver, $scenario));
    }

    public function recordDriverSuccess(string $driver, string $scenario = 'default'): void
    {
        Cache::forget($this->circuitFailureCounterKey($driver, $scenario));
        Cache::forget($this->circuitOpenKey($driver, $scenario));
    }

    public function recordDriverFailure(string $driver, string $scenario, Throwable $exception): void
    {
        $classification = $this->classifyException($exception);
        if ($classification['retryable'] !== true) {
            return;
        }

        $threshold = max(1, (int) $this->config->get('job-matching.circuit_breaker_threshold', 3));
        $ttlSeconds = max(60, (int) $this->config->get('job-matching.circuit_breaker_ttl_seconds', 300));
        $counterKey = $this->circuitFailureCounterKey($driver, $scenario);
        $openKey = $this->circuitOpenKey($driver, $scenario);

        // 使用 increment 进行原子计数，避免 Cache::put 将 int 序列化为 string 导致 is_int 判断失败
        // 首次调用时 increment 会自动初始化为 1
        $count = Cache::increment($counterKey);
        // 刷新 TTL，防止长时间未达阈值时计数器过期失效
        Cache::put($counterKey, $count, now()->addSeconds($ttlSeconds));

        if ($count < $threshold) {
            return;
        }

        Cache::put($openKey, true, now()->addSeconds($ttlSeconds));

        Log::warning('AI driver circuit opened', [
            'driver' => $driver,
            'scenario' => $scenario,
            'failure_type' => $classification['type'],
            'threshold' => $threshold,
            'ttl_seconds' => $ttlSeconds,
        ]);
    }

    private function extractRetryAfterMilliseconds(Throwable $exception): ?int
    {
        $message = $exception->getMessage();

        if (preg_match('/retry-after[^0-9]{0,16}(\d+)/i', $message, $matches) === 1) {
            return ((int) $matches[1]) * 1000;
        }

        if (preg_match('/"retry_after"\s*:\s*(\d+)/i', $message, $matches) === 1) {
            return ((int) $matches[1]) * 1000;
        }

        if (preg_match('/"retryAfter"\s*:\s*(\d+)/i', $message, $matches) === 1) {
            return ((int) $matches[1]) * 1000;
        }

        return null;
    }

    private function circuitFailureCounterKey(string $driver, string $scenario): string
    {
        return 'ai:circuit:counter:'.strtolower(trim($scenario)).':'.strtolower(trim($driver));
    }

    private function circuitOpenKey(string $driver, string $scenario): string
    {
        return 'ai:circuit:open:'.strtolower(trim($scenario)).':'.strtolower(trim($driver));
    }

    /**
     * 带降级的 Provider 获取：主 Provider 失败时自动切换备用
     */
    public function providerWithFallback(?string $driver = null): AiProvider
    {
        $drivers = $this->orderedDrivers($driver);
        if ($drivers === []) {
            throw new InvalidArgumentException('没有已开启的 AI Provider 可用。');
        }

        $primaryDriver = $drivers[0] ?? ($driver ?? $this->getDefaultDriver());

        foreach ($drivers as $index => $candidate) {
            try {
                $provider = $this->driver($candidate);
                if ($index > 0) {
                    Log::info("AI Provider 降级到 [{$candidate}]");
                }

                return $provider;
            } catch (Throwable $e) {
                Log::warning("AI Provider [{$candidate}] 不可用，尝试降级", [
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        throw new InvalidArgumentException("所有 AI Provider 均不可用（主: {$primaryDriver}）");
    }

    /**
     * 带缓存的 AI 调用：相同输入在 TTL 内直接返回缓存结果。
     *
     * 默认使用 ai.cache.ttl_seconds 作为 TTL，向后兼容。
     * 如需差异化 TTL，请使用 withCacheFor() 指定场景。
     *
     * @param  callable(AiProvider): mixed  $callback
     */
    public function withCache(string $cacheKey, callable $callback): mixed
    {
        return $this->withCacheFor($cacheKey, $callback, null);
    }

    /**
     * 带缓存（差异化 TTL）的 AI 调用。
     *
     * 根据场景枚举从 ai.cache.ttl_scenarios 读取对应 TTL，
     * 未指定场景时回退到 ai.cache.ttl_seconds。
     *
     * @param  callable(AiProvider): mixed  $callback
     * @param  AiCacheScenario|null         $scenario AI 缓存场景，决定 TTL 时长
     */
    public function withCacheFor(string $cacheKey, callable $callback, ?AiCacheScenario $scenario = null): mixed
    {
        if (! (bool) $this->config->get('ai.cache.enabled', true)) {
            return $callback($this->providerWithFallback());
        }

        $ttl = $this->resolveCacheTtl($scenario);

        return Cache::remember($cacheKey, $ttl, function () use ($callback) {
            return $callback($this->providerWithFallback());
        });
    }

    /**
     * 根据场景解析缓存 TTL（秒）。
     * 场景未指定或配置缺失时回退到默认 ttl_seconds，并保证最小 60 秒。
     */
    private function resolveCacheTtl(?AiCacheScenario $scenario): int
    {
        $defaultTtl = max(60, (int) $this->config->get('ai.cache.ttl_seconds', 1800));

        if ($scenario === null) {
            return $defaultTtl;
        }

        $scenarioTtl = (int) $this->config->get(
            "ai.cache.ttl_scenarios.{$scenario->value}",
            $defaultTtl
        );

        return max(60, $scenarioTtl);
    }

    public function isProviderEnabled(string $driver): bool
    {
        $normalizedDriver = strtolower(trim($driver));

        if (! in_array($normalizedDriver, self::SUPPORTED_DRIVERS, true)) {
            return false;
        }

        return (bool) $this->config->get("ai.providers.{$normalizedDriver}.enabled", true);
    }

    /**
     * @return array<int, string>
     */
    public function enabledDrivers(): array
    {
        return array_values(array_filter(
            self::SUPPORTED_DRIVERS,
            fn (string $driver): bool => $this->isProviderEnabled($driver)
        ));
    }

    /**
     * 获取所有驱动的健康指标摘要
     *
     * @return array<string, array{circuit_open: bool, failure_count: int}>
     */
    public function driversHealthSummary(): array
    {
        $summary = [];
        foreach (self::SUPPORTED_DRIVERS as $driver) {
            if (! $this->isProviderEnabled($driver)) {
                continue;
            }

            $summary[$driver] = [
                'circuit_open' => $this->isDriverCircuitOpen($driver),
                'failure_count' => (int) Cache::get($this->circuitFailureCounterKey($driver, 'default'), 0),
                'enabled' => true,
            ];
        }

        return $summary;
    }
}
