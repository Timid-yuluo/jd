<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

use Illuminate\Support\Facades\Log;

/**
 * 智谱 AI (Zhipu AI / GLM) Provider
 *
 * 对接方式：OpenAI 兼容 HTTP API
 * API Endpoint: https://open.bigmodel.cn/api/paas/v4
 *
 * 当前后台支持的常用模型包括：
 * - glm-4.7
 * - glm-4.7-flash
 * - glm-4.5-flash
 * - glm-4.5
 *
 * 注意: 智谱 API Key 格式为 Bearer {API Key}，与普通 OpenAI 格式一致
 */
class ZhipuAiProvider extends OpenAiCompatibleProvider
{
    private const SAFE_DEFAULT_MODEL = 'glm-4.5-flash';

    /**
     * @var array<int, string>
     */
    private array $modelFallbacks;

    /**
     * @param  array<int, string>  $modelFallbacks
     */
    public function __construct(
        string $baseUrl,
        string $apiKey,
        string $model,
        array $modelFallbacks = []
    ) {
        parent::__construct($baseUrl, $apiKey, $model);

        $this->modelFallbacks = $this->normalizeModelFallbacks($modelFallbacks);
    }

    public static function normalizeModel(string $model, ?string $fallbackModel = null): string
    {
        $candidate = trim($model);
        if ($candidate !== '' && ! self::isBlockedModel($candidate)) {
            return $candidate;
        }

        $fallback = trim((string) $fallbackModel);
        if ($fallback !== '' && ! self::isBlockedModel($fallback)) {
            return $fallback;
        }

        return self::SAFE_DEFAULT_MODEL;
    }

    public static function isBlockedModel(string $model): bool
    {
        $candidate = mb_strtolower(trim($model));
        if ($candidate === '') {
            return false;
        }

        if (preg_match('/^glm-(\d+)/', $candidate, $matches) !== 1) {
            return false;
        }

        return (int) $matches[1] >= 5;
    }

    public function chat(array $messages, array $options = []): array
    {
        $requestedModel = $this->resolveRequestedModel($options);
        $attemptedModels = [];
        $lastException = null;

        foreach ($this->candidateModels($requestedModel) as $candidateModel) {
            $attemptedModels[] = $candidateModel;

            try {
                return parent::chat($messages, array_merge($options, ['model' => $candidateModel]));
            } catch (\Throwable $exception) {
                $lastException = $exception;

                if (! $this->shouldFallbackToAnotherModel($exception, $candidateModel)) {
                    throw $exception;
                }

                $this->logModelFallback($requestedModel, $candidateModel, $exception);
            }
        }

        throw $this->buildFallbackFailedException($requestedModel, $attemptedModels, $lastException);
    }

    protected function streamChat(array $messages, array $options = []): \Generator
    {
        $requestedModel = $this->resolveRequestedModel($options);
        $attemptedModels = [];
        $lastException = null;

        foreach ($this->candidateModels($requestedModel) as $candidateModel) {
            $attemptedModels[] = $candidateModel;

            try {
                yield from parent::streamChat($messages, array_merge($options, ['model' => $candidateModel]));

                return;
            } catch (\Throwable $exception) {
                $lastException = $exception;

                if (! $this->shouldFallbackToAnotherModel($exception, $candidateModel)) {
                    throw $exception;
                }

                $this->logModelFallback($requestedModel, $candidateModel, $exception);
            }
        }

        throw $this->buildFallbackFailedException($requestedModel, $attemptedModels, $lastException);
    }

    private function resolveRequestedModel(array $options): string
    {
        return self::normalizeModel((string) ($options['model'] ?? $this->model), $this->model);
    }

    /**
     * @return array<int, string>
     */
    private function candidateModels(string $requestedModel): array
    {
        $models = [$requestedModel, ...$this->modelFallbacks];

        return $this->normalizeModelFallbacks($models);
    }

    /**
     * @param  array<int, string>  $models
     * @return array<int, string>
     */
    private function normalizeModelFallbacks(array $models): array
    {
        $normalized = [];

        foreach ($models as $model) {
            $candidate = trim((string) $model);
            if ($candidate === '' || in_array($candidate, $normalized, true)) {
                continue;
            }

            if (self::isBlockedModel($candidate)) {
                continue;
            }

            $normalized[] = $candidate;
        }

        return $normalized;
    }

    private function shouldFallbackToAnotherModel(\Throwable $exception, string $failedModel): bool
    {
        return $this->isModelUnavailableException($exception)
            && count($this->candidateModels($failedModel)) > 1;
    }

    private function isModelUnavailableException(\Throwable $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        $hasModelContext = str_contains($message, 'model')
            || str_contains($message, 'glm-')
            || str_contains($message, '模型');

        if (! $hasModelContext) {
            return false;
        }

        return str_contains($message, 'not found')
            || str_contains($message, 'does not exist')
            || str_contains($message, 'not support')
            || str_contains($message, 'unsupported')
            || str_contains($message, 'unavailable')
            || str_contains($message, 'invalid')
            || str_contains($message, 'permission')
            || str_contains($message, 'forbidden')
            || str_contains($message, '不存在')
            || str_contains($message, '不可用')
            || str_contains($message, '不支持')
            || str_contains($message, '无权限')
            || str_contains($message, '未开通')
            || str_contains($message, '无权')
            || str_contains($message, '404');
    }

    /**
     * @param  array<int, string>  $attemptedModels
     */
    private function buildFallbackFailedException(
        string $requestedModel,
        array $attemptedModels,
        ?\Throwable $lastException
    ): \RuntimeException {
        $attemptedModelsText = implode(', ', $attemptedModels);
        $baseMessage = sprintf(
            'Zhipu model fallback exhausted. requested=%s attempted=[%s]',
            $requestedModel,
            $attemptedModelsText
        );

        if ($lastException === null) {
            return new \RuntimeException($baseMessage);
        }

        return new \RuntimeException($baseMessage.' last_error='.$lastException->getMessage(), 0, $lastException);
    }

    private function logModelFallback(string $requestedModel, string $failedModel, \Throwable $exception): void
    {
        Log::warning('Zhipu model unavailable, trying fallback model.', [
            'requested_model' => $requestedModel,
            'failed_model' => $failedModel,
            'fallback_models' => $this->candidateModels($requestedModel),
            'error' => $exception->getMessage(),
        ]);
    }
}
