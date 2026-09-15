<?php

declare(strict_types=1);

namespace App\Actions\JobRecommendation;

use App\Models\Resume;
use App\Services\JobRecommendationService;

/**
 * #1 生成推荐 Action
 *
 * 解耦自 Service，只负责"触发生成"动作
 * 便于在 Controller、Job、Console Command 中复用
 */
final class GenerateRecommendationsAction
{
    public function __construct(
        private readonly JobRecommendationService $service,
    ) {}

    /**
     * 同步生成推荐（用于测试或小批量场景）
     *
     * @return array{created: int, high_match: int}
     */
    public function executeSync(Resume $resume, int $userId): array
    {
        return $this->service->generateForResume($resume, $userId);
    }

    /**
     * 异步生成推荐（投递到队列）
     */
    public function executeAsync(int $userId, int $resumeId): void
    {
        $this->service->setProgress($userId, 'queued', 0, 0);

        \App\Jobs\GenerateJobRecommendations::dispatch($userId, $resumeId);
    }
}
