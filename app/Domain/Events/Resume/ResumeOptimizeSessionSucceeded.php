<?php

declare(strict_types=1);

namespace App\Domain\Events\Resume;

use App\Domain\Events\DomainEvent;

/**
 * 简历优化会话已成功完成
 *
 * 在优化结果保存、会话状态变更为 succeeded 后触发。
 * 监听器可用于：更新统计、推送通知、触发后续流程（如自动生成对比报告）。
 */
final class ResumeOptimizeSessionSucceeded extends DomainEvent
{
    public function __construct(
        public readonly int $sessionId,
        public readonly string $sessionUuid,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly ?string $usedDriver = null,
        public readonly ?string $primaryDriver = null,
        public readonly int $scoreBefore = 0,
        public readonly int $scoreAfter = 0,
        ?\DateTimeImmutable $occurredAt = null
    ) {
        parent::__construct($sessionId, $occurredAt);
    }

    /**
     * 是否发生了 driver fallback
     */
    public function isFallbackUsed(): bool
    {
        return $this->usedDriver !== null
            && $this->primaryDriver !== null
            && $this->usedDriver !== $this->primaryDriver;
    }
}
