<?php

declare(strict_types=1);

namespace App\Domain\Events;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 领域事件总线实现
 *
 * 基于 Laravel Event Dispatcher 封装，提供：
 * - 类型安全的领域事件分发
 * - 统一的事件日志记录
 * - 自动将领域事件映射到 Laravel 监听器
 * - 事件存储与重放机制（基于缓存，用于调试和状态恢复）
 *
 * 监听器注册方式：
 * - 在 EventServiceProvider 中以 `App\Domain\Events\XxxEvent::class => [Listener::class]` 形式注册
 * - 或通过 DomainEventServiceProvider 集中注册
 */
final class DomainEventBus implements DomainEventBusInterface
{
    /**
     * 事件存储的缓存键前缀
     */
    private const STORAGE_KEY_PREFIX = 'domain_events:';

    /**
     * 事件存储 TTL（秒）：7 天
     */
    private const STORAGE_TTL = 604800;

    /**
     * 每个聚合最大存储事件数
     */
    private const MAX_STORED_EVENTS = 200;

    public function __construct(
        private readonly EventDispatcher $dispatcher,
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        $payload = $this->extractPayload($event);

        Log::info('domain_event_dispatched', [
            'event_type' => $event->eventType(),
            'aggregate_id' => $event->aggregateId,
            'occurred_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
            'payload' => $payload,
        ]);

        // 存储事件用于后续重放
        $this->storeEvent($event, $payload);

        // 委托给 Laravel Event Dispatcher，触发已注册的监听器
        $this->dispatcher->dispatch($event);
    }

    public function replay(string|int $aggregateId, int $limit = 100): array
    {
        $key = self::STORAGE_KEY_PREFIX . $aggregateId;
        $events = Cache::get($key, []);

        if (! is_array($events)) {
            return [];
        }

        // 取最近 $limit 条事件
        $events = array_slice(array_reverse($events), 0, $limit);

        Log::info('domain_event_replay', [
            'aggregate_id' => $aggregateId,
            'replayed_count' => count($events),
        ]);

        return $events;
    }

    /**
     * 存储事件到缓存，用于后续重放
     *
     * @param  array<string,mixed>  $payload
     */
    private function storeEvent(DomainEvent $event, array $payload): void
    {
        if ($event->aggregateId === null) {
            return;
        }

        $key = self::STORAGE_KEY_PREFIX . $event->aggregateId;

        try {
            $events = Cache::get($key, []);
            if (! is_array($events)) {
                $events = [];
            }

            $events[] = [
                'event_type' => $event->eventType(),
                'occurred_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
                'payload' => $payload,
            ];

            // 保留最近 MAX_STORED_EVENTS 条事件，防止内存膨胀
            if (count($events) > self::MAX_STORED_EVENTS) {
                $events = array_slice($events, -self::MAX_STORED_EVENTS);
            }

            Cache::put($key, $events, self::STORAGE_TTL);
        } catch (Throwable) {
            // 缓存写入失败不影响事件分发
        }
    }

    /**
     * 提取事件的公共属性作为日志 payload
     *
     * @return array<string,mixed>
     */
    private function extractPayload(DomainEvent $event): array
    {
        $reflection = new \ReflectionClass($event);
        $payload = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            // 跳过基类已单独记录的字段
            if (in_array($name, ['aggregateId', 'occurredAt'], true)) {
                continue;
            }

            $value = $property->getValue($event);
            // 仅记录可序列化的标量与数组
            if (is_scalar($value) || is_array($value) || $value === null) {
                $payload[$name] = $value;
            } else {
                $payload[$name] = '<object>';
            }
        }

        return $payload;
    }
}
