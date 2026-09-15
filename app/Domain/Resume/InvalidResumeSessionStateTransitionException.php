<?php

declare(strict_types=1);

namespace App\Domain\Resume;

use App\Models\ResumeOptimizeSession;
use RuntimeException;

/**
 * 简历优化会话状态非法转换异常
 *
 * 当尝试执行不允许的状态转换时抛出。
 */
final class InvalidResumeSessionStateTransitionException extends RuntimeException
{
    /**
     * @param  array<int,string>  $allowed
     */
    public function __construct(
        public readonly ?ResumeOptimizeSession $session,
        public readonly string $from,
        public readonly string $to,
        public readonly array $allowed
    ) {
        $sessionId = $session?->id ?? 'N/A';
        $allowedList = implode(', ', $allowed);
        $message = sprintf(
            'Resume optimize session [%s] 不允许从状态 "%s" 转换到 "%s"。允许的状态：%s。',
            $sessionId,
            $from,
            $to,
            $allowedList !== '' ? $allowedList : '无（终态）'
        );

        parent::__construct($message);
    }

    public function context(): array
    {
        return [
            'session_id' => $this->session?->id,
            'session_uuid' => $this->session?->uuid,
            'from' => $this->from,
            'to' => $this->to,
            'allowed' => $this->allowed,
        ];
    }
}