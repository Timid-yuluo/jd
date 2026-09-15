<?php

declare(strict_types=1);

namespace App\Domain\Events;

/**
 * 领域事件总线接口
 *
 * 抽象领域事件分发能力，底层可基于 Laravel Event Dispatcher 实现，
 * 也可替换为队列驱动的事件分发器。
 */
interface DomainEventBusInterface
{
    /**
     * 分发领域事件
     *
     * @param  DomainEvent  $event  领域事件实例
     */
    public function dispatch(DomainEvent $event): void;

    /**
     * 重放指定聚合的事件（用于调试或状态恢复）
     *
     * @param  string|int  $aggregateId  聚合根 ID
     * @param  int  $limit  最大重放数量
     * @return array<int, array{event_type: string, occurred_at: string, payload: array<string,mixed>}>
     */
    public function replay(string|int $aggregateId, int $limit = 100): array;
}
