<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * AI 服务不可用异常 — 供控制器通过统一异常渲染处理，消除重复 try-catch
 */
final class AiServiceUnavailableException extends RuntimeException
{
    /**
     * @param  string  $event  日志事件名（如 resume_optimize_failed）
     * @param  array<string, mixed>  $context  日志上下文
     */
    public function __construct(
        string $message = 'AI 服务暂时不可用，请稍后重试。',
        private readonly string $event = 'ai_service_unavailable',
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public function event(): string
    {
        return $this->event;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
