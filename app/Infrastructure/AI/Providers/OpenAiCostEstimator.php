<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

final class OpenAiCostEstimator
{
    /**
     * 各模型每百万 token 价格（元），prompt / completion
     *
     * @var array<string, array{float, float}>
     */
    private const PRICING = [
        'deepseek-v4-flash' => [1.0, 2.0],
        'deepseek-v4-pro' => [12.0, 24.0],
        'deepseek-chat' => [1.0, 2.0],
        'deepseek-reasoner' => [1.0, 2.0],
        'glm-5.1' => [6.0, 24.0],
        'glm-5-turbo' => [5.0, 22.0],
        'glm-5' => [4.0, 18.0],
        'glm-4.7' => [0.1, 0.125],
        'glm-4.7-flash' => [0.0, 0.0],
        'glm-4.5' => [0.1, 0.125],
        'glm-4.5-air' => [0.035, 0.05],
        'glm-4.5-flash' => [0.1, 0.05],
        'glm-4-air-250414' => [0.5, 0.25],
        'glm-4-flash-250414' => [0.1, 0.05],
    ];

    /**
     * 根据模型和 token 用量估算费用（微单位，1 微单位 = 0.000001 元）
     */
    public function estimate(string $model, int $promptTokens, int $completionTokens): int
    {
        $key = strtolower($model);

        if (! isset(self::PRICING[$key])) {
            foreach (self::PRICING as $k => $v) {
                if (str_contains($key, $k)) {
                    $key = $k;
                    break;
                }
            }
        }

        $prices = self::PRICING[$key] ?? [5, 5];
        $costYuan = ($promptTokens * $prices[0] + $completionTokens * $prices[1]) / 1_000_000;

        return (int) round($costYuan * 1_000_000);
    }
}
