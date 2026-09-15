<?php

declare(strict_types=1);
use App\Services\Admin\AiConfigService;
use App\Services\Admin\SystemSettingService;

$aiConfig = null;

if (class_exists(AiConfigService::class)
    && class_exists(SystemSettingService::class)
) {
    try {
        $aiConfig = app(AiConfigService::class)->all();
    } catch (Throwable) {
        $aiConfig = null;
    }
}

$env = static function (string $key, mixed $default = null) use ($aiConfig): mixed {
    if (is_array($aiConfig) && array_key_exists($key, $aiConfig) && $aiConfig[$key] !== '') {
        return $aiConfig[$key];
    }

    return env($key, $default);
};

$envBool = static function (string $key, bool $default = true) use ($env): bool {
    $value = $env($key, $default ? '1' : '0');

    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string) $value));

    return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
};

$supportedProviders = ['deepseek', 'volcano', 'zhipu'];
$defaultProvider = (string) $env('AI_PROVIDER', 'zhipu');
$fallbackChain = $env('AI_FALLBACK_CHAIN')
    ? array_filter(array_map('trim', explode(',', (string) $env('AI_FALLBACK_CHAIN'))))
    : ['deepseek', 'volcano'];
$fallbackChain = array_values(array_filter(
    $fallbackChain,
    static fn (string $provider): bool => in_array($provider, $supportedProviders, true)
));
$providerEnabledMap = [
    'deepseek' => $envBool('DEEPSEEK_ENABLED', true),
    'volcano' => $envBool('VOLCANO_ENABLED', true),
    'zhipu' => $envBool('ZHIPU_ENABLED', true),
];
$enabledProviders = array_values(array_filter(
    $supportedProviders,
    static fn (string $provider): bool => $providerEnabledMap[$provider] ?? false
));
$defaultProvider = in_array($defaultProvider, $enabledProviders, true)
    ? $defaultProvider
    : ($enabledProviders[0] ?? 'zhipu');
$fallbackChain = array_values(array_filter(
    $fallbackChain,
    static fn (string $provider): bool => $providerEnabledMap[$provider] ?? false
));

return [
    'default' => $defaultProvider,
    'http' => [
        'connect_timeout' => max(1, (int) env('AI_CONNECT_TIMEOUT', 3)),
        'request_timeout' => max(3, (int) env('AI_REQUEST_TIMEOUT', 60)),
        // 差异化超时：按 API 场景设定不同超时（秒）
        'timeouts' => [
            'quick' => (int) env('AI_TIMEOUT_QUICK', 30),    // 面试出题/评分
            'heavy' => (int) env('AI_TIMEOUT_HEAVY', 90),    // 简历优化/ATS评分
            'stream' => (int) env('AI_TIMEOUT_STREAM', 120),  // 流式优化
        ],
    ],
    // Provider 自动降级链：主 Provider 失败时按顺序尝试备用
    'fallback_chain' => $fallbackChain,
    'rate_limit' => [
        'initial_backoff_ms' => max(200, (int) env('AI_RATE_LIMIT_INITIAL_BACKOFF_MS', 1200)),
        'max_backoff_ms' => max(500, (int) env('AI_RATE_LIMIT_MAX_BACKOFF_MS', 4000)),
        'multiplier' => max(1, (int) env('AI_RATE_LIMIT_BACKOFF_MULTIPLIER', 2)),
        'zhipu_max_attempts' => max(1, (int) env('AI_ZHIPU_RATE_LIMIT_MAX_ATTEMPTS', 2)),
    ],
    // AI 响应缓存配置
    'cache' => [
        'enabled' => (bool) env('AI_CACHE_ENABLED', true),
        'ttl_seconds' => (int) env('AI_CACHE_TTL', 1800), // 默认 30 分钟（向后兼容）
        // 差异化 TTL：按 AI 场景设定不同缓存时长（秒）
        // - heavy: 简历优化/ATS评分等高成本且稳定的场景，长缓存
        // - medium: 模块排序/翻译等中等成本场景
        // - quick: 面试出题/评分等时效性较高的场景，短缓存
        // - stable: 关键词提取/解析等输入确定即输出确定的场景，超长缓存
        'ttl_scenarios' => [
            'heavy' => (int) env('AI_CACHE_TTL_HEAVY', 3600),    // 1 小时
            'medium' => (int) env('AI_CACHE_TTL_MEDIUM', 1800),  // 30 分钟
            'quick' => (int) env('AI_CACHE_TTL_QUICK', 600),     // 10 分钟
            'stable' => (int) env('AI_CACHE_TTL_STABLE', 21600), // 6 小时
        ],
    ],
    'providers' => [
        'deepseek' => [
            'name' => 'DeepSeek',
            'enabled' => $providerEnabledMap['deepseek'],
            'base_url' => $env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'api_key' => $env('DEEPSEEK_API_KEY'),
            'model' => $env('DEEPSEEK_MODEL', 'deepseek-v4-flash'),
        ],
        'volcano' => [
            'name' => 'VolcanoEngine',
            'enabled' => $providerEnabledMap['volcano'],
            'base_url' => $env('VOLCANO_BASE_URL', 'https://ark.cn-beijing.volces.com/api/v3'),
            'api_key' => $env('VOLCANO_API_KEY'),
            'model' => $env('VOLCANO_MODEL', 'doubao-lite-4k'),
        ],
        'zhipu' => [
            'name' => '智谱AI',
            'enabled' => $providerEnabledMap['zhipu'],
            'base_url' => $env('ZHIPU_BASE_URL', 'https://open.bigmodel.cn/api/paas/v4'),
            'api_key' => $env('ZHIPU_API_KEY'),
            'model' => $env('ZHIPU_MODEL', 'glm-4.5-flash'),
            'model_fallbacks' => $env('ZHIPU_MODEL_FALLBACKS')
                ? array_values(array_filter(array_map('trim', explode(',', (string) $env('ZHIPU_MODEL_FALLBACKS')))))
                : ['glm-4.7', 'glm-4.7-flash', 'glm-4.5-flash', 'glm-4.5-air', 'glm-4.5'],
        ],
    ],
];
