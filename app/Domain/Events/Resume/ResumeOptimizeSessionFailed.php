<?php

declare(strict_types=1);

namespace App\Domain\Events\Resume;

use App\Domain\Events\DomainEvent;

/**
 * 简历优化会话已失败
 *
 * 在会话状态变更为 failed 后触发。
 * 监听器可用于：告警、记录失败原因、触发自动重试、用户补偿等。
 */
final class ResumeOptimizeSessionFailed extends DomainEvent
{
    public function __construct(
        public readonly int $sessionId,
        public readonly string $sessionUuid,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly string $errorCode,
        public readonly string $errorMessage,
        public readonly bool $retryable = false,
        public readonly ?string $failedDriver = null,
        ?\DateTimeImmutable $occurredAt = null
    ) {
        parent::__construct($sessionId, $occurredAt);
    }
}
