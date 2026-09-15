<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Infrastructure\AI\Providers\ZhipuAiProvider;
use App\Models\AiConfigHistory;

/**
 * AI 配置更新服务 — 从 AiConfigController 的 updateGlobalSettings / updateProviderSettings / logConfigChange 及辅助方法抽离
 */
final class AiConfigUpdateService
{
    public function __construct(
        private readonly AiConfigService $configService,
        private readonly AiConfigVerifier $configVerifier,
    ) {}

    /**
     * 保存全局 AI 配置
     *
     * @param  array<string,mixed>  $validated
     * @return array{success: bool, message: string, scope: string}
     */
    public function updateGlobal(array $validated): array
    {
        $oldConfig = $this->currentConfigSnapshot();

        if (! $this->isEnabledProviderInSnapshot((string) $validated['default_provider'], $oldConfig)) {
            return [
                'success' => false,
                'message' => '默认 AI 提供商必须是已开启状态，请先开启该 Provider。',
                'scope' => 'global',
            ];
        }

        $this->configService->saveMany([
            'AI_PROVIDER' => $validated['default_provider'],
            'RESUME_EDITOR_OPTIMIZE_PRIMARY_CHANNEL' => $validated['resume_optimize_primary_channel'],
        ]);

        $this->logConfigChange($oldConfig, $validated, ['errors' => [], 'warnings' => []]);

        return [
            'success' => true,
            'message' => '全局 AI 配置已保存。',
            'scope' => 'global',
        ];
    }

    /**
     * 保存 Provider 配置
     *
     * @param  array<string,mixed>  $validated
     * @return array{success: bool, message: string, scope: string, provider?: string}
     */
    public function updateProvider(array $validated): array
    {
        $provider = (string) $validated['provider'];
        $oldConfig = $this->currentConfigSnapshot();
        $payload = $this->buildProviderSavePayload($provider, $validated, $oldConfig);
        $verifyInput = $this->buildProviderVerifyPayload($provider, $validated, $oldConfig);

        if (! $this->willHaveAnyEnabledProvider($provider, $payload, $oldConfig)) {
            return [
                'success' => false,
                'message' => '至少需要保留一个已开启的 AI Provider，不能全部关闭。',
                'scope' => 'provider',
                'provider' => $provider,
            ];
        }

        $verifyResults = $this->configVerifier->verifyProviders($verifyInput);

        if (! empty($verifyResults['errors'])) {
            return [
                'success' => false,
                'message' => '配置验证失败：'.implode('；', $verifyResults['errors']),
                'scope' => 'provider',
                'provider' => $provider,
            ];
        }

        $this->configService->saveMany($payload);
        $this->logConfigChange($oldConfig, array_merge($validated, $verifyInput), $verifyResults);

        $providerLabel = $this->providerLabel($provider);
        $warnings = ! empty($verifyResults['warnings']) ? '，警告：'.implode('；', $verifyResults['warnings']) : '';

        return [
            'success' => true,
            'message' => $providerLabel.' 配置已保存'.$warnings.'。',
            'scope' => 'provider',
            'provider' => $provider,
        ];
    }

    /**
     * 记录配置变更历史
     *
     * @param  array<string,mixed>  $oldConfig
     * @param  array<string,mixed>  $newConfig
     * @param  array{errors:array<string>,warnings:array<string>}  $verifyResults
     */
    public function logConfigChange(array $oldConfig, array $newConfig, array $verifyResults): void
    {
        $changes = [];

        // 开关字段变更
        $switchFields = ['deepseek_enabled', 'volcano_enabled', 'zhipu_enabled'];
        foreach ($switchFields as $field) {
            $oldValue = $this->normalizeBooleanChangeValue($oldConfig[$field] ?? null);
            $newValue = $this->normalizeBooleanChangeValue($newConfig[$field] ?? null);

            if ($newValue !== null && $newValue !== $oldValue) {
                $changes[$field] = [
                    'old' => $oldValue ? '开启' : '关闭',
                    'new' => $newValue ? '开启' : '关闭',
                ];
            }
        }

        // API Key 变更
        $fields = ['deepseek_api_key', 'volcano_api_key', 'zhipu_api_key'];
        foreach ($fields as $field) {
            $oldValue = $oldConfig[$field] ?? null;
            $newValue = $newConfig[$field] ?? null;

            if ($newValue !== null && $newValue !== $oldValue) {
                $changes[$field] = [
                    'old' => $oldValue ? '已配置' : '未配置',
                    'new' => $newValue ? '已更新' : '未配置',
                ];
            }
        }

        // 模型变更
        $modelFields = ['deepseek_model', 'volcano_model', 'zhipu_model'];
        foreach ($modelFields as $field) {
            $oldValue = $oldConfig[$field] ?? null;
            $newValue = $newConfig[$field] ?? null;

            if ($newValue !== null && $newValue !== $oldValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        // 默认 Provider 变更
        if (($newConfig['default_provider'] ?? null) !== null
            && ($newConfig['default_provider'] ?? null) !== ($oldConfig['default_provider'] ?? null)) {
            $changes['default_provider'] = [
                'old' => $oldConfig['default_provider'],
                'new' => $newConfig['default_provider'],
            ];
        }

        // 优化通道变更
        if (($newConfig['resume_optimize_primary_channel'] ?? null) !== null
            && ($newConfig['resume_optimize_primary_channel'] ?? null) !== ($oldConfig['resume_optimize_primary_channel'] ?? null)) {
            $changes['resume_optimize_primary_channel'] = [
                'old' => $oldConfig['resume_optimize_primary_channel'],
                'new' => $newConfig['resume_optimize_primary_channel'],
            ];
        }

        if (! empty($changes)) {
            AiConfigHistory::create([
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'changes' => $changes,
                'verify_results' => $verifyResults,
                'ip_address' => request()->ip(),
            ]);
        }
    }

    /**
     * 获取当前配置快照
     *
     * @return array<string,mixed>
     */
    public function currentConfigSnapshot(): array
    {
        return [
            'default_provider' => config('ai.default'),
            'resume_optimize_primary_channel' => config('resume.editor.optimize_primary_channel', 'session'),
            'deepseek_enabled' => (bool) config('ai.providers.deepseek.enabled', true),
            'deepseek_api_key' => config('ai.providers.deepseek.api_key'),
            'deepseek_model' => config('ai.providers.deepseek.model'),
            'volcano_enabled' => (bool) config('ai.providers.volcano.enabled', true),
            'volcano_api_key' => config('ai.providers.volcano.api_key'),
            'volcano_model' => config('ai.providers.volcano.model'),
            'zhipu_enabled' => (bool) config('ai.providers.zhipu.enabled', true),
            'zhipu_api_key' => config('ai.providers.zhipu.api_key'),
            'zhipu_model' => config('ai.providers.zhipu.model'),
        ];
    }

    /**
     * @param  array<string,mixed>  $validated
     * @param  array<string,mixed>  $currentConfig
     * @return array<string,string|null>
     */
    private function buildProviderSavePayload(string $provider, array $validated, array $currentConfig): array
    {
        return match ($provider) {
            'deepseek' => [
                'DEEPSEEK_ENABLED' => $this->booleanSettingValue($validated['deepseek_enabled'] ?? ($currentConfig['deepseek_enabled'] ?? true)),
                'DEEPSEEK_API_KEY' => $this->nullableWhenBlank($validated['deepseek_api_key'] ?? null),
                'DEEPSEEK_MODEL' => $this->stringOrFallback($validated['deepseek_model'] ?? null, (string) ($currentConfig['deepseek_model'] ?? '')),
            ],
            'volcano' => [
                'VOLCANO_ENABLED' => $this->booleanSettingValue($validated['volcano_enabled'] ?? ($currentConfig['volcano_enabled'] ?? true)),
                'VOLCANO_API_KEY' => $this->nullableWhenBlank($validated['volcano_api_key'] ?? null),
                'VOLCANO_MODEL' => $this->stringOrFallback($validated['volcano_model'] ?? null, (string) ($currentConfig['volcano_model'] ?? '')),
            ],
            'zhipu' => [
                'ZHIPU_ENABLED' => $this->booleanSettingValue($validated['zhipu_enabled'] ?? ($currentConfig['zhipu_enabled'] ?? true)),
                'ZHIPU_API_KEY' => $this->nullableWhenBlank($validated['zhipu_api_key'] ?? null),
                'ZHIPU_MODEL' => ZhipuAiProvider::normalizeModel(
                    $this->stringOrFallback($validated['zhipu_model'] ?? null, (string) ($currentConfig['zhipu_model'] ?? '')),
                    (string) ($currentConfig['zhipu_model'] ?? '')
                ),
            ],
            default => [],
        };
    }

    /**
     * @param  array<string,mixed>  $validated
     * @param  array<string,mixed>  $currentConfig
     * @return array<string,mixed>
     */
    private function buildProviderVerifyPayload(string $provider, array $validated, array $currentConfig): array
    {
        return match ($provider) {
            'deepseek' => [
                'deepseek_enabled' => $this->booleanSettingValue($validated['deepseek_enabled'] ?? ($currentConfig['deepseek_enabled'] ?? true)),
                'deepseek_api_key' => $this->stringOrFallback($validated['deepseek_api_key'] ?? null, (string) ($currentConfig['deepseek_api_key'] ?? '')),
                'deepseek_model' => $this->stringOrFallback($validated['deepseek_model'] ?? null, (string) ($currentConfig['deepseek_model'] ?? '')),
            ],
            'volcano' => [
                'volcano_enabled' => $this->booleanSettingValue($validated['volcano_enabled'] ?? ($currentConfig['volcano_enabled'] ?? true)),
                'volcano_api_key' => $this->stringOrFallback($validated['volcano_api_key'] ?? null, (string) ($currentConfig['volcano_api_key'] ?? '')),
                'volcano_model' => $this->stringOrFallback($validated['volcano_model'] ?? null, (string) ($currentConfig['volcano_model'] ?? '')),
            ],
            'zhipu' => [
                'zhipu_enabled' => $this->booleanSettingValue($validated['zhipu_enabled'] ?? ($currentConfig['zhipu_enabled'] ?? true)),
                'zhipu_api_key' => $this->stringOrFallback($validated['zhipu_api_key'] ?? null, (string) ($currentConfig['zhipu_api_key'] ?? '')),
                'zhipu_model' => ZhipuAiProvider::normalizeModel(
                    $this->stringOrFallback($validated['zhipu_model'] ?? null, (string) ($currentConfig['zhipu_model'] ?? '')),
                    (string) ($currentConfig['zhipu_model'] ?? '')
                ),
            ],
            default => [],
        };
    }

    /**
     * @param  array<string,string|null>  $payload
     * @param  array<string,mixed>  $currentConfig
     */
    private function willHaveAnyEnabledProvider(string $provider, array $payload, array $currentConfig): bool
    {
        $enabledMap = [
            'deepseek' => $this->normalizeBooleanChangeValue($currentConfig['deepseek_enabled'] ?? true) ?? true,
            'volcano' => $this->normalizeBooleanChangeValue($currentConfig['volcano_enabled'] ?? true) ?? true,
            'zhipu' => $this->normalizeBooleanChangeValue($currentConfig['zhipu_enabled'] ?? true) ?? true,
        ];

        $enabledSettingKey = match ($provider) {
            'deepseek' => 'DEEPSEEK_ENABLED',
            'volcano' => 'VOLCANO_ENABLED',
            'zhipu' => 'ZHIPU_ENABLED',
            default => null,
        };

        if ($enabledSettingKey !== null && array_key_exists($enabledSettingKey, $payload)) {
            $enabledMap[$provider] = ($payload[$enabledSettingKey] ?? '0') === '1';
        }

        return in_array(true, $enabledMap, true);
    }

    /**
     * @param  array<string,mixed>  $snapshot
     */
    private function isEnabledProviderInSnapshot(string $provider, array $snapshot): bool
    {
        return match ($provider) {
            'deepseek' => $this->normalizeBooleanChangeValue($snapshot['deepseek_enabled'] ?? true) ?? true,
            'volcano' => $this->normalizeBooleanChangeValue($snapshot['volcano_enabled'] ?? true) ?? true,
            'zhipu' => $this->normalizeBooleanChangeValue($snapshot['zhipu_enabled'] ?? true) ?? true,
            default => false,
        };
    }

    private function nullableWhenBlank(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function stringOrFallback(mixed $value, string $fallback): string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : $fallback;
    }

    private function booleanSettingValue(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'on', 'yes'], true) ? '1' : '0';
    }

    private function normalizeBooleanChangeValue(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->booleanSettingValue($value) === '1';
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'deepseek' => 'DeepSeek',
            'volcano' => '火山引擎',
            'zhipu' => '智谱 AI',
            default => 'AI Provider',
        };
    }
}
