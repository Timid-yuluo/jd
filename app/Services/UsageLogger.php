<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\AI\Contracts\AiProvider;
use App\Infrastructure\AI\Providers\OpenAiCompatibleProvider;
use App\Jobs\RecordUsageLogJob;
use App\Models\UsageLog;

final class UsageLogger
{
    /**
     * 从 AI 调用结果中提取 usage 信息并异步记录日志
     *
     * @param  array<string,mixed>  $aiResult  AI provider 返回的结果（含 __usage 和 __latency_ms）
     */
    public static function log(
        int $userId,
        string $scenario,
        string $provider,
        array $aiResult,
        array $meta = []
    ): UsageLog {
        $usage = $aiResult['__usage'] ?? [];
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $latencyMs = (int) ($aiResult['__latency_ms'] ?? 0);

        // 估算费用
        $costMicros = 0;
        if ($promptTokens > 0 || $completionTokens > 0) {
            $aiProvider = app(AiProvider::class);
            if ($aiProvider instanceof OpenAiCompatibleProvider) {
                $costMicros = $aiProvider->estimateCostMicros($promptTokens, $completionTokens);
            }
        }

        $data = [
            'user_id' => $userId,
            'scenario' => $scenario,
            'provider' => $provider,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'latency_ms' => $latencyMs,
            'cost_micros' => $costMicros,
            'meta' => $meta,
        ];

        // 异步写入，避免阻塞 AI 调用主流程
        RecordUsageLogJob::dispatch($data);

        // 返回一个未持久化的 UsageLog 实例，保持接口兼容
        return new UsageLog($data);
    }
}
