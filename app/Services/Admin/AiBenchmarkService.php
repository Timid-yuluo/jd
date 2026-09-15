<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Infrastructure\AI\AiManager;
use App\Models\UsageLog;
use Illuminate\Support\Facades\Log;

/**
 * AI 基准测试服务 — 从 AiConfigController::benchmark() 和 ::test() 抽离
 */
final class AiBenchmarkService
{
    /**
     * 对所有已启用的 Provider 运行基准测试
     *
     * @return array{results: array<string, array{available: bool, latency?: int, question?: string, stats_24h?: array{calls: int, avg_latency: int, error_rate: float}, error?: string}>, recommended: string|null, tested_at: string}
     */
    public function run(): array
    {
        $providers = ['deepseek', 'volcano', 'zhipu'];
        $results = [];

        foreach ($providers as $provider) {
            $results[$provider] = $this->benchmarkProvider($provider);
        }

        $recommended = $this->recommendBest($results);

        return [
            'results' => $results,
            'recommended' => $recommended,
            'tested_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * 测试单个 Provider 的指定功能
     *
     * @return array{success: bool, provider: string, test_type: string, latency_ms?: int, result?: array, error?: string}
     */
    public function testProvider(string $provider, string $testType): array
    {
        $providerConfig = config("ai.providers.{$provider}", []);

        if (! (bool) ($providerConfig['enabled'] ?? true)) {
            return [
                'success' => false,
                'provider' => $provider,
                'test_type' => $testType,
                'error' => '该 Provider 当前已关闭，请先开启后再测试。',
            ];
        }

        if (empty($providerConfig['api_key'])) {
            return [
                'success' => false,
                'provider' => $provider,
                'test_type' => $testType,
                'error' => '该 Provider 未配置 API Key，请先保存配置后再测试。',
            ];
        }

        $startTime = microtime(true);

        try {
            $aiManager = app(AiManager::class);
            $aiProvider = $aiManager->provider($provider);

            $result = match ($testType) {
                'chat' => ['message' => '连接测试成功'],
                'resume_optimize' => $aiProvider->optimizeResume(
                    "姓名：张三\n学历：本科，计算机科学与技术\n工作经验：2年Java开发\n项目：电商后台管理系统",
                    'Java开发工程师'
                ),
                'resume_score' => $aiProvider->scoreResume(
                    "姓名：张三\n学历：本科\n技能：Java, Spring Boot, MySQL",
                    'Java开发工程师'
                ),
                'interview_question' => $aiProvider->generateInterviewQuestion('Java开发工程师', 1),
                'interview_evaluate' => $aiProvider->evaluateInterviewAnswer(
                    '请介绍你的项目经验',
                    '我参与了一个电商项目，使用Spring Boot开发，负责订单模块。'
                ),
                default => ['message' => '未知测试类型'],
            };

            $latency = round((microtime(true) - $startTime) * 1000);

            return [
                'success' => true,
                'provider' => $provider,
                'test_type' => $testType,
                'latency_ms' => $latency,
                'result' => $result,
            ];
        } catch (\Throwable $e) {
            Log::warning('AI provider test failed', [
                'provider' => $provider,
                'test_type' => $testType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'provider' => $provider,
                'test_type' => $testType,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 对单个 Provider 运行基准测试
     *
     * @return array{available: bool, latency?: int, question?: string, stats_24h?: array{calls: int, avg_latency: int, error_rate: float}, error?: string}
     */
    private function benchmarkProvider(string $provider): array
    {
        $config = config("ai.providers.{$provider}", []);

        if (! (bool) ($config['enabled'] ?? true)) {
            return [
                'available' => false,
                'error' => 'Provider 已关闭',
            ];
        }

        if (empty($config['api_key'])) {
            return [
                'available' => false,
                'error' => '未配置 API Key',
            ];
        }

        try {
            $startTime = microtime(true);
            $aiManager = app(AiManager::class);
            $aiProvider = $aiManager->provider($provider);
            $result = $aiProvider->generateInterviewQuestion('测试职位', 1);
            $latency = round((microtime(true) - $startTime) * 1000);

            $recentStats = UsageLog::query()
                ->where('provider', $provider)
                ->where('created_at', '>=', now()->subHours(24))
                ->selectRaw('COUNT(*) as calls, AVG(latency_ms) as avg_latency, AVG(CASE WHEN status = "error" THEN 1 ELSE 0 END) * 100 as error_rate')
                ->first();

            return [
                'available' => true,
                'latency' => $latency,
                'question' => $result['question'] ?? 'N/A',
                'stats_24h' => [
                    'calls' => $recentStats->calls ?? 0,
                    'avg_latency' => round($recentStats->avg_latency ?? 0),
                    'error_rate' => round($recentStats->error_rate ?? 0, 1),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 根据延迟和错误率推荐最佳 Provider
     *
     * @param  array<string, array{available: bool, latency?: int, stats_24h?: array{error_rate: float}}>  $results
     */
    private function recommendBest(array $results): ?string
    {
        $bestProvider = null;
        $bestScore = -1;

        foreach ($results as $provider => $data) {
            if (! $data['available']) {
                continue;
            }

            $score = 1000 / (($data['latency'] ?? 999) + 100) - ($data['stats_24h']['error_rate'] ?? 0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestProvider = $provider;
            }
        }

        return $bestProvider;
    }
}
