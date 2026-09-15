<?php

declare(strict_types=1);

namespace App\Domain\Events\Resume;

use App\Domain\Events\DomainEvent;

/**
 * 简历优化会话已创建
 *
 * 在用户发起优化请求、会话持久化后触发。
 * 监听器可用于：发送通知、记录用户行为、初始化审计追踪等。
 */
final class ResumeOptimizeSessionCreated extends DomainEvent
{
    public function __construct(
        public readonly int $sessionId,
        public readonly string $sessionUuid,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly string $optimizeMode = 'balanced',
        ?\DateTimeImmutable $occurredAt = null
    ) {
        parent::__construct($sessionId, $occurredAt);
    }
}
