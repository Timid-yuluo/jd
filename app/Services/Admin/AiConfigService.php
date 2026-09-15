<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Infrastructure\AI\AiManager;
use App\Infrastructure\AI\Providers\ZhipuAiProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class AiConfigService
{
    private const CACHE_KEY = 'admin:ai_config:all';

    private const CACHE_TTL = 600;

    private const AI_KEYS = [
        'AI_PROVIDER',
        'RESUME_EDITOR_OPTIMIZE_PRIMARY_CHANNEL',
        'DEEPSEEK_ENABLED',
        'DEEPSEEK_API_KEY',
        'DEEPSEEK_MODEL',
        'VOLCANO_ENABLED',
        'VOLCANO_API_KEY',
        'VOLCANO_MODEL',
        'ZHIPU_ENABLED',
        'ZHIPU_API_KEY',
        'ZHIPU_MODEL',
    ];

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_PROVIDERS = ['deepseek', 'volcano', 'zhipu'];

    public function __construct(
        private readonly SystemSettingService $settingService,
    ) {}

    /**
     * @return array<string,string>
     */
    public function all(): array
    {
        /** @var array<string,string> $configs */
        $configs = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $result = [];

            foreach (self::AI_KEYS as $key) {
                $dbValue = $this->settingService->get($key);
                $result[$key] = $dbValue !== '' ? $dbValue : $this->defaultValueForKey($key);
            }

            return $result;
        });

        return $configs;
    }

    public function get(string $key, string $default = ''): string
    {
        $configs = $this->all();

        return $configs[$key] ?? $default;
    }

    /**
     * @param  array<string,string|null>  $input
     * @return array<int, array{key:string,old_value:string|null,new_value:string|null}>
     */
    public function saveMany(array $input): array
    {
        $filtered = [];

        foreach ($input as $key => $value) {
            if (in_array($key, self::AI_KEYS, true) && $value !== null) {
                $filtered[$key] = $this->normalizeConfigValue($key, (string) $value);
            }
        }

        if ($filtered === []) {
            return [];
        }

        $changes = $this->settingService->saveMany($filtered);

        Cache::forget(self::CACHE_KEY);
        $this->applyRuntimeConfig();

        Log::info('AI config updated', [
            'changes' => array_map(static fn (array $c): array => [
                'key' => $c['key'],
                'changed' => $c['old_value'] !== $c['new_value'],
            ], $changes),
        ]);

        return $changes;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function applyRuntimeConfig(): void
    {
        $configs = $this->all();
        $enabledProviders = $this->enabledProvidersFromConfigs($configs);
        $defaultProvider = $this->resolveDefaultProvider(
            (string) ($configs['AI_PROVIDER'] ?? 'zhipu'),
            $enabledProviders
        );

        config([
            'ai.default' => $defaultProvider,
            'resume.editor.optimize_primary_channel' => 'session',
            'ai.providers.deepseek.enabled' => $this->normalizeBooleanConfigValue($configs['DEEPSEEK_ENABLED'] ?? '1'),
            'ai.providers.deepseek.api_key' => $configs['DEEPSEEK_API_KEY'] ?? config('ai.providers.deepseek.api_key'),
            'ai.providers.deepseek.model' => $configs['DEEPSEEK_MODEL'] ?? config('ai.providers.deepseek.model'),
            'ai.providers.volcano.enabled' => $this->normalizeBooleanConfigValue($configs['VOLCANO_ENABLED'] ?? '1'),
            'ai.providers.volcano.api_key' => $configs['VOLCANO_API_KEY'] ?? config('ai.providers.volcano.api_key'),
            'ai.providers.volcano.model' => $configs['VOLCANO_MODEL'] ?? config('ai.providers.volcano.model'),
            'ai.providers.zhipu.enabled' => $this->normalizeBooleanConfigValue($configs['ZHIPU_ENABLED'] ?? '1'),
            'ai.providers.zhipu.api_key' => $configs['ZHIPU_API_KEY'] ?? config('ai.providers.zhipu.api_key'),
            'ai.providers.zhipu.model' => ZhipuAiProvider::normalizeModel(
                (string) ($configs['ZHIPU_MODEL'] ?? config('ai.providers.zhipu.model')),
                (string) config('ai.providers.zhipu.model')
            ),
        ]);

        app(AiManager::class)->forgetDrivers();
    }

    private function normalizeConfigValue(string $key, string $value): string
    {
        return match ($key) {
            'DEEPSEEK_ENABLED', 'VOLCANO_ENABLED', 'ZHIPU_ENABLED' => $this->normalizeBooleanConfigValue($value) ? '1' : '0',
            'ZHIPU_MODEL' => ZhipuAiProvider::normalizeModel($value),
            default => $value,
        };
    }

    private function defaultValueForKey(string $key): string
    {
        return match ($key) {
            'DEEPSEEK_ENABLED', 'VOLCANO_ENABLED', 'ZHIPU_ENABLED' => (string) env($key, '1'),
            default => (string) env($key, ''),
        };
    }

    private function normalizeBooleanConfigValue(mixed $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @param  array<string, string>  $configs
     * @return array<int, string>
     */
    private function enabledProvidersFromConfigs(array $configs): array
    {
        return array_values(array_filter(
            self::SUPPORTED_PROVIDERS,
            fn (string $provider): bool => $this->normalizeBooleanConfigValue(
                $configs[strtoupper($provider).'_ENABLED'] ?? '1'
            )
        ));
    }

    /**
     * @param  array<int, string>  $enabledProviders
     */
    private function resolveDefaultProvider(string $candidate, array $enabledProviders): string
    {
        $normalized = strtolower(trim($candidate));
        if (in_array($normalized, $enabledProviders, true)) {
            return $normalized;
        }

        return $enabledProviders[0] ?? 'zhipu';
    }
}
