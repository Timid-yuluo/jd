<?php

declare(strict_types=1);

namespace App\Domain\Resume;

use App\Models\ResumeOptimizeSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 简历优化流程状态机
 *
 * 封装所有合法的状态转换，确保业务状态流转的确定性。
 * 非法转换将抛出 InvalidStateTransitionException。
 *
 * 特性：
 * - 状态转换映射表保证合法性
 * - 原子锁防止并发转换竞态
 * - 审计日志追踪所有状态变更
 */
final class ResumeOptimizeStateMachine
{
    /**
     * 状态转换锁的 TTL（秒）：防止锁泄漏
     */
    private const LOCK_TTL_SECONDS = 10;

    /**
     * 合法状态转换映射表
     *
     * 键为当前状态，值为允许转换到的目标状态列表。
     */
    private const TRANSITIONS = [
        ResumeOptimizeSession::STATUS_DRAFT => [
            ResumeOptimizeSession::STATUS_QUEUED,
        ],
        ResumeOptimizeSession::STATUS_QUEUED => [
            ResumeOptimizeSession::STATUS_RUNNING,
            ResumeOptimizeSession::STATUS_CANCELED,
        ],
        ResumeOptimizeSession::STATUS_RUNNING => [
            ResumeOptimizeSession::STATUS_SUCCEEDED,
            ResumeOptimizeSession::STATUS_FAILED,
            ResumeOptimizeSession::STATUS_CANCELED,
            ResumeOptimizeSession::STATUS_QUEUED, // 限流重试场景
        ],
        ResumeOptimizeSession::STATUS_FAILED => [
            ResumeOptimizeSession::STATUS_QUEUED, // 用户手动重试
        ],
        ResumeOptimizeSession::STATUS_CANCELED => [
            ResumeOptimizeSession::STATUS_QUEUED, // 用户重新发起
        ],
        ResumeOptimizeSession::STATUS_SUCCEEDED => [
            ResumeOptimizeSession::STATUS_APPLYING,
        ],
        ResumeOptimizeSession::STATUS_APPLYING => [
            ResumeOptimizeSession::STATUS_APPLIED,
            ResumeOptimizeSession::STATUS_SUCCEEDED, // 应用失败回退
        ],
        ResumeOptimizeSession::STATUS_APPLIED => [],
    ];

    /**
     * 终态集合：这些状态不可再转换
     */
    private const TERMINAL_STATUSES = [
        ResumeOptimizeSession::STATUS_APPLIED,
    ];

    /**
     * 检查状态转换是否合法
     */
    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * 获取当前状态允许转换到的目标状态列表
     *
     * @return array<int,string>
     */
    public function allowedTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    /**
     * 检查当前状态是否为终态（不可再变更）
     */
    public function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }

    /**
     * 检查当前状态是否属于活跃状态（正在处理中）
     */
    public function isActive(string $status): bool
    {
        return in_array($status, [
            ResumeOptimizeSession::STATUS_QUEUED,
            ResumeOptimizeSession::STATUS_RUNNING,
            ResumeOptimizeSession::STATUS_APPLYING,
        ], true);
    }

    /**
     * 检查当前状态是否属于已完成状态（成功或已应用）
     */
    public function isCompleted(string $status): bool
    {
        return in_array($status, [
            ResumeOptimizeSession::STATUS_SUCCEEDED,
            ResumeOptimizeSession::STATUS_APPLIED,
        ], true);
    }

    /**
     * 检查当前状态是否属于失败状态（失败或取消）
     */
    public function isFailed(string $status): bool
    {
        return in_array($status, [
            ResumeOptimizeSession::STATUS_FAILED,
            ResumeOptimizeSession::STATUS_CANCELED,
        ], true);
    }

    /**
     * 可重试的状态：失败或取消后允许重新发起
     */
    public function isRetryable(string $status): bool
    {
        return $this->canTransition($status, ResumeOptimizeSession::STATUS_QUEUED);
    }

    /**
     * 执行状态转换，同时校验合法性
     *
     * 流程：
     * 1. 获取会话级原子锁，防止并发转换竞态
     * 2. 重新加载会话状态，避免脏读
     * 3. 校验状态转换合法性
     * 4. 持久化新状态
     * 5. 记录审计日志
     *
     * @throws InvalidResumeSessionStateTransitionException
     * @throws \RuntimeException 锁获取失败时抛出
     */
    public function transition(ResumeOptimizeSession $session, string $to): ResumeOptimizeSession
    {
        $lockKey = $this->lockKey($session->id);
        $lock = Cache::lock($lockKey, self::LOCK_TTL_SECONDS);

        if (! $lock->block(2)) {
            throw new \RuntimeException(
                "无法获取会话状态锁，可能存在并发操作。session_id={$session->id}"
            );
        }

        try {
            // 重新加载最新状态，避免内存中脏读
            $session->refresh();
            $from = $session->status;

            if (! $this->canTransition($from, $to)) {
                throw new InvalidResumeSessionStateTransitionException(
                    $session,
                    $from,
                    $to,
                    $this->allowedTransitions($from)
                );
            }

            $session->forceFill(['status' => $to])->save();

            $this->logTransition($session->id, $from, $to);

            return $session;
        } finally {
            $lock->release();
        }
    }

    /**
     * 不持久化，仅校验转换是否合法（用于批量操作前的预检）
     *
     * @throws InvalidResumeSessionStateTransitionException
     */
    public function guard(string $from, string $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidResumeSessionStateTransitionException(
                session: null,
                from: $from,
                to: $to,
                allowed: $this->allowedTransitions($from)
            );
        }
    }

    /**
     * 生成会话级状态锁的键名
     */
    private function lockKey(int $sessionId): string
    {
        return "resume_optimize:state_lock:{$sessionId}";
    }

    /**
     * 记录状态转换审计日志
     */
    private function logTransition(int $sessionId, string $from, string $to): void
    {
        try {
            Log::info('resume_optimize_state_transition', [
                'session_id' => $sessionId,
                'from' => $from,
                'to' => $to,
                'occurred_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable) {
            // 日志写入失败不影响业务流程
        }
    }
}