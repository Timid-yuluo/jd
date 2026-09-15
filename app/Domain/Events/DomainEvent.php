<?php

declare(strict_types=1);

namespace App\Domain\Events;

/**
 * 领域事件基类
 *
 * 所有领域事件应继承此类，携带发生时间与聚合 ID，
 * 便于事件总线统一追踪与日志记录。
 */
abstract class DomainEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string|int|null $aggregateId = null,
        ?\DateTimeImmutable $occurredAt = null
    ) {
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
    }

    /**
     * 事件类型名称，默认使用类名（去掉命名空间）
     */
    public function eventType(): string
    {
        $shortName = (new \ReflectionClass($this))->getShortName();

        // 将 PascalCase 转为 snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName) ?? $shortName);
    }
}
