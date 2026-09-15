<?php

declare(strict_types=1);

namespace App\Services\Resume\Support;

use App\Domain\Resume\ResumeOptimizeStateMachine;
use App\Infrastructure\AI\AiManager;
use App\Models\ResumeOptimizeSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * 简历优化会话失败处理器
 *
 * 从 ResumeOptimizeSessionService 抽离的单一职责类，
 * 负责会话失败标记、限流重试、队列失败处理等逻辑。
 *
 * 特性：
 * - 错误码常量引用，消除魔法字符串
 * - 重试次数追踪，防止无限重试
 * - 指数退避策略，缓解 AI 服务压力
 * - 失败场景分类，便于监控告警
 */
final class ResumeOptimizeSessionFailureHandler
{
    /**
     * 最大重试次数：超过此次数后不再自动重试
     */
    private const MAX_AUTO_RETRY = 3;

    /**
     * 退避基础间隔（秒）：实际间隔 = base * 2^retryCount
     */
    private const BACKOFF_BASE_SECONDS = 30;

    /**
     * 重试计数器缓存 TTL（秒）
     */
    private const RETRY_COUNT_TTL = 3600;

    public function __construct(
        private readonly AiManager $aiManager,
        private readonly ResumeOptimizeErrorMapper $errorMapper,
        private readonly ResumeOptimizeStateMachine $stateMachine,
    ) {}

    /**
     * 标记会话失败，使用错误映射器统一生成用户友好文案
     *
     * @param  array<string,mixed>  $meta
     */
    public function markFailed(
        ResumeOptimizeSession $session,
        string $errorCode,
        string $message,
        string $stage,
        array $meta = []
    ): ResumeOptimizeSession {
        if (in_array($session->status, [
            ResumeOptimizeSession::STATUS_SUCCEEDED,
            ResumeOptimizeSession::STATUS_APPLIED,
        ], true)) {
            return $session;
        }

        // 通过错误映射器获取用户友好文案，避免技术细节外泄
        $friendly = $this->errorMapper->resolve($errorCode);
        $userMessage = $friendly['message'];

        $config = is_array($session->config) ? $session->config : [];
        $retryCount = $this->incrementRetryCount($session->id);
        $config['last_failure'] = array_merge([
            'stage' => $stage,
            'code' => $errorCode,
            'message' => $userMessage,
            'technical_message' => Str::limit($message, 500),
            'retryable' => $friendly['retryable'],
            'hint' => $friendly['hint'],
            'severity' => $friendly['severity'],
            'category' => $friendly['category'],
            'retry_count' => $retryCount,
            'at' => now()->toIso8601String(),
        ], $meta);

        // 通过状态机进行状态转换，确保合法性
        $this->stateMachine->transition($session, ResumeOptimizeSession::STATUS_FAILED);

        $session->forceFill([
            'progress' => 100,
            'error_code' => $errorCode,
            'error_message' => $userMessage,
            'finished_at' => now(),
            'config' => $config,
        ])->save();

        $this->logWarning('resume_optimize_session_failed', [
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'resume_id' => $session->resume_id,
            'user_id' => $session->user_id,
            'error_code' => $errorCode,
            'error_message' => $message,
            'stage' => $stage,
            'severity' => $friendly['severity'],
            'category' => $friendly['category'],
            'retry_count' => $retryCount,
            'meta' => $meta,
        ]);

        // 致命错误立即触发告警
        if ($this->errorMapper->isFatal($errorCode)) {
            $this->logWarning('resume_optimize_fatal_alert', [
                'session_id' => $session->id,
                'error_code' => $errorCode,
                'message' => $userMessage,
                'requires_immediate_attention' => true,
            ]);
        }

        $this->emitEvent('resume_optimize_failed', [
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'resume_id' => $session->resume_id,
            'user_id' => $session->user_id,
            'status' => $session->status,
            'error_code' => $errorCode,
            'error' => Str::limit($message, 300),
            'stage' => $stage,
            'retry_count' => $retryCount,
        ]);

        return $session;
    }

    /**
     * 因 AI 限流将会话重新置为排队，等待重试
     *
     * 采用指数退避策略：
     * - 第 1 次重试：30 秒后
     * - 第 2 次重试：60 秒后
     * - 第 3 次重试：120 秒后
     * - 超过最大次数：转为失败
     */
    public function markQueuedForRetry(ResumeOptimizeSession $session, Throwable $exception): void
    {
        $retryCount = $this->getRetryCount($session->id);

        // 超过最大重试次数，转为失败
        if ($retryCount >= self::MAX_AUTO_RETRY) {
            $this->markFailed(
                $session,
                ResumeOptimizeErrorMapper::CODE_AI_RATE_LIMITED,
                'AI 服务持续限流，已达到最大重试次数。',
                'ai_rate_limited_max_retries',
                [
                    'exception' => $exception->getMessage(),
                    'retry_count' => $retryCount,
                ]
            );

            return;
        }

        $retryCount = $this->incrementRetryCount($session->id);
        $backoffSeconds = $this->calculateBackoff($retryCount);

        // 通过状态机进行状态转换，确保合法性（限流重试仅允许 running → queued）
        $this->stateMachine->transition($session, ResumeOptimizeSession::STATUS_QUEUED);

        $config = is_array($session->config) ? $session->config : [];
        $config['retry_info'] = [
            'count' => $retryCount,
            'backoff_seconds' => $backoffSeconds,
            'reason' => 'ai_rate_limited',
            'last_exception' => Str::limit($exception->getMessage(), 200),
            'next_retry_at' => now()->addSeconds($backoffSeconds)->toIso8601String(),
        ];

        $session->forceFill([
            'progress' => max(5, min(15, (int) $session->progress)),
            'error_code' => null,
            'error_message' => null,
            'finished_at' => null,
            'config' => $config,
        ])->save();

        $this->logWarning('resume_optimize_session_rate_limited_retrying', [
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'resume_id' => $session->resume_id,
            'user_id' => $session->user_id,
            'error' => $exception->getMessage(),
            'retry_count' => $retryCount,
            'backoff_seconds' => $backoffSeconds,
        ]);
    }

    /**
     * 队列任务执行失败后处理会话状态
     */
    public function handleAfterQueueFailure(int $sessionId, ?Throwable $exception = null): void
    {
        /** @var ResumeOptimizeSession|null $session */
        $session = ResumeOptimizeSession::query()->find($sessionId);
        if (! $session instanceof ResumeOptimizeSession) {
            return;
        }

        if ($this->isAiRateLimitException($exception)) {
            $this->markFailed(
                $session,
                ResumeOptimizeErrorMapper::CODE_AI_RATE_LIMITED,
                'AI 服务触发 429/1302 限流，系统已自动退避并切换备用模型后仍未成功，请稍后再试。',
                'ai_rate_limited',
                ['exception' => $exception?->getMessage()]
            );

            return;
        }

        $message = '优化任务在队列执行阶段失败，请稍后重试。';
        if ($exception instanceof Throwable && trim($exception->getMessage()) !== '') {
            $message = Str::limit($exception->getMessage(), 500);
        }

        $this->markFailed(
            $session,
            ResumeOptimizeErrorMapper::CODE_QUEUE_JOB_FAILED,
            $message,
            'queue_job_failed',
            ['exception' => $exception?->getMessage()]
        );
    }

    /**
     * 判断异常是否为 AI 限流
     */
    public function isAiRateLimitException(?Throwable $exception): bool
    {
        if (! $exception instanceof Throwable) {
            return false;
        }

        return $this->aiManager->isRateLimitException($exception);
    }

    /**
     * 判断错误码是否属于队列类失败
     */
    public function isQueueFailureCode(string $errorCode): bool
    {
        return $this->errorMapper->isQueueFailure($errorCode);
    }

    /**
     * 获取当前会话的重试次数
     */
    public function getRetryCount(int $sessionId): int
    {
        return (int) Cache::get($this->retryCountKey($sessionId), 0);
    }

    /**
     * 重置重试计数器（用户手动重试时调用）
     */
    public function resetRetryCount(int $sessionId): void
    {
        Cache::forget($this->retryCountKey($sessionId));
    }

    /**
     * 计算指数退避间隔（秒）
     *
     * @param  int  $retryCount  当前重试次数（从 1 开始）
     */
    private function calculateBackoff(int $retryCount): int
    {
        return self::BACKOFF_BASE_SECONDS * (2 ** max(0, $retryCount - 1));
    }

    /**
     * 递增重试计数器
     */
    private function incrementRetryCount(int $sessionId): int
    {
        $key = $this->retryCountKey($sessionId);
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, self::RETRY_COUNT_TTL);

        return $count;
    }

    /**
     * 生成重试计数器的缓存键
     */
    private function retryCountKey(int $sessionId): string
    {
        return "resume_optimize:retry_count:{$sessionId}";
    }

    /**
     * 安全记录 warning 日志（兼容受限环境）
     *
     * @param  array<string,mixed>  $context
     */
    private function logWarning(string $message, array $context): void
    {
        try {
            Log::warning($message, $context);
        } catch (Throwable) {
            // 受限环境下忽略日志写入失败
        }
    }

    /**
     * 发射优化事件日志
     *
     * @param  array<string,mixed>  $context
     */
    private function emitEvent(string $event, array $context): void
    {
        $payload = array_merge([
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
        ], $context);

        try {
            Log::info('resume_optimize_event', $payload);
        } catch (Throwable) {
            // 受限环境下忽略日志写入失败
        }
    }
}
