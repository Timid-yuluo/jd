<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Infrastructure\AI\AiManager;

final class AiConfigVerifier
{
    /**
     * @param  array<string,mixed>  $validated
     * @return array{errors:array<string>,warnings:array<string>}
     */
    public function verifyProviders(array $validated): array
    {
        $errors = [];
        $warnings = [];

        $providersToVerify = [
            'deepseek' => [
                'enabled' => ($validated['deepseek_enabled'] ?? '1') === '1',
                'api_key' => $validated['deepseek_api_key'] ?? null,
                'model' => $validated['deepseek_model'] ?? config('ai.providers.deepseek.model'),
            ],
            'volcano' => [
                'enabled' => ($validated['volcano_enabled'] ?? '1') === '1',
                'api_key' => $validated['volcano_api_key'] ?? null,
                'model' => $validated['volcano_model'] ?? config('ai.providers.volcano.model'),
            ],
            'zhipu' => [
                'enabled' => ($validated['zhipu_enabled'] ?? '1') === '1',
                'api_key' => $validated['zhipu_api_key'] ?? null,
                'model' => $validated['zhipu_model'] ?? config('ai.providers.zhipu.model'),
            ],
        ];

        foreach ($providersToVerify as $provider => $config) {
            if (! ($config['enabled'] ?? true)) {
                continue;
            }

            if (empty($config['api_key'])) {
                continue;
            }

            try {
                config(["ai.providers.{$provider}.enabled" => true]);
                config(["ai.providers.{$provider}.api_key" => $config['api_key']]);
                config(["ai.providers.{$provider}.model" => $config['model']]);

                $aiManager = app(AiManager::class);
                $aiProvider = $aiManager->provider($provider);

                $result = $aiProvider->generateInterviewQuestion('测试职位', 1);

                if (empty($result['question'])) {
                    $warnings[] = "{$provider} API 响应异常，可能配置有误";
                }
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), '403') || str_contains($e->getMessage(), 'Unauthorized')) {
                    $errors[] = "{$provider} API Key 无效或已过期";
                } elseif (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'model')) {
                    $errors[] = "{$provider} 模型名称错误或该模型不存在";
                } elseif (str_contains($e->getMessage(), '429')) {
                    $warnings[] = "{$provider} 请求频率受限，但 API Key 有效";
                } elseif (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Connection')) {
                    $warnings[] = "{$provider} 连接超时，请检查网络或 Base URL";
                } else {
                    $warnings[] = "{$provider} 验证失败：".$e->getMessage();
                }
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }
}
