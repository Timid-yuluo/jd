<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Infrastructure\AI\AiManager;
use App\Models\Resume;
use App\Services\Membership\PlanFeatureService;
use App\Services\Resume\Support\ResumeOptimizationPayloadSanitizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 简历优化流式输出服务 — 从 ResumeStreamController::optimizeStream() 抽离
 */
final class ResumeOptimizeStreamService
{
    public function __construct(
        private readonly ResumeOptimizationPayloadSanitizer $payloadSanitizer,
        private readonly PlanFeatureService $planFeatureService,
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 执行简历优化流式输出
     *
     * @param  array<string,mixed>  $validated
     * @param  callable(): void  $onSuccess  优化成功后的回调（用于标记配额消耗）
     * @param  string|null  $lastEventId  客户端上次接收到的最后一个事件 ID，用于断点续传
     */
    public function stream(Resume $resume, array $validated, bool $allowCreditOverride, callable $onSuccess, ?string $lastEventId = null): StreamedResponse
    {
        if ((bool) ($validated['prewarm'] ?? false) === true) {
            return $this->prewarmResponse();
        }

        $targetJob = (string) ($validated['target_job'] ?? $resume->target_job ?? '');
        $targetCompany = (string) ($validated['target_company'] ?? $resume->target_company ?? '');
        $targetJobTitle = (string) ($validated['target_job_title'] ?? $resume->target_job_title ?? '');
        $targetJobDescription = (string) ($validated['target_job_description'] ?? $resume->target_job_description ?? '');
        $optimizeGoals = is_array($validated['optimize_goals'] ?? null) ? $validated['optimize_goals'] : [];
        $focusKeywords = is_array($validated['focus_keywords'] ?? null) ? array_values($validated['focus_keywords']) : [];
        $resumeProfile = $this->payloadSanitizer->sanitizeResumeProfile($validated['resume_profile'] ?? null);

        $optimizeAccess = $this->planFeatureService->resolveOptimizeAccess(
            $resume->user,
            is_string($validated['optimize_mode'] ?? null) ? $validated['optimize_mode'] : null,
            is_string($validated['prompt_strategy_template'] ?? null) ? $validated['prompt_strategy_template'] : null,
            $allowCreditOverride
        );

        if (! $optimizeAccess['allowed']) {
            return $this->accessDeniedResponse();
        }

        $options = [
            'target_company' => $targetCompany,
            'target_job_title' => $targetJobTitle,
            'target_job_description' => $targetJobDescription,
            'prompt_strategy_template' => $optimizeAccess['prompt_strategy_template'],
            'optimize_goals' => $optimizeGoals,
            'optimize_mode' => $optimizeAccess['optimize_mode'],
            'focus_keywords' => $focusKeywords,
            'resume_profile' => $resumeProfile,
            'career_track_keywords' => $resume->careerTrack?->keywords ?? [],
            'career_track_avoid_words' => $resume->careerTrack?->avoid_words ?? [],
            'career_track_focus' => $resume->careerTrack?->optimization_focus ?? [],
        ];

        $contentRaw = $this->buildRawFromModules($validated['modules'] ?? null, $resume);
        $aiManager = $this->aiManager;

        // 生成会话级 streamId，用于 Redis 持久化 buffer 以支持断点续传
        $streamId = Str::uuid()->toString();
        $bufferCacheKey = "resume_stream:{$streamId}:buffer";
        $eventSeqCacheKey = "resume_stream:{$streamId}:seq";

        $response = new StreamedResponse(function () use ($resume, $targetJob, $options, $contentRaw, $aiManager, $onSuccess, $lastEventId, $bufferCacheKey, $eventSeqCacheKey) {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            @ob_implicit_flush(true);

            // 断点续传：若客户端携带 Last-Event-ID 且 Redis 中存在已缓存 buffer，先回放历史
            $replayed = 0;
            if ($lastEventId !== null) {
                $cachedBuffer = (string) Cache::get($bufferCacheKey, '');
                if ($cachedBuffer !== '') {
                    $replayed = strlen($cachedBuffer);
                    echo "event: resume\n";
                    echo 'data: '.json_encode([
                        'message' => '已恢复上次进度，继续生成...',
                        'replayed_bytes' => $replayed,
                        'last_event_id' => $lastEventId,
                    ])."\n\n";
                    @flush();
                }
            }

            echo "event: start\n";
            echo 'data: '.json_encode(['message' => 'AI 开始生成优化内容...'])."\n\n";
            @flush();

            // 心跳定时器：每 15 秒发送一次注释心跳，防止 Nginx/代理超时断连
            $lastHeartbeat = time();

            try {
                $attemptDrivers = $aiManager->orderedDrivers();
                $primaryDriver = $attemptDrivers[0] ?? null;
                $buffer = '';
                $usedDriver = null;
                $lastException = null;
                $failedDrivers = [];
                $eventSeq = (int) Cache::get($eventSeqCacheKey, 0);

                foreach ($attemptDrivers as $driverName) {
                    $driverAttempt = 0;

                    while (true) {
                        $driverAttempt++;
                        $driverBuffer = '';
                        $driverHasChunk = false;

                        try {
                            $provider = $aiManager->provider($driverName);

                            foreach ($provider->optimizeResumeStream($contentRaw, $targetJob, $options) as $chunk) {
                                $driverHasChunk = true;
                                $driverBuffer .= $chunk;
                                $eventSeq++;
                                $eventId = "evt-{$eventSeq}";

                                // 持久化 buffer 到 Redis，支持客户端断线重连后续传
                                // 限制 buffer 大小为 512KB，防止超大内容导致 Redis 内存膨胀
                                $fullBuffer = $buffer.$driverBuffer;
                                if (strlen($fullBuffer) <= 524288) {
                                    Cache::put($bufferCacheKey, $fullBuffer, 600);
                                }
                                Cache::put($eventSeqCacheKey, $eventSeq, 600);

                                // 心跳：超过 15 秒未发送数据时发送注释心跳
                                $now = time();
                                if ($now - $lastHeartbeat >= 15) {
                                    echo ": heartbeat {$now}\n\n";
                                    @flush();
                                    $lastHeartbeat = $now;
                                }

                                echo "id: {$eventId}\n";
                                echo "event: chunk\n";
                                echo 'data: '.json_encode(['chunk' => $chunk, 'buffer' => $buffer.$driverBuffer])."\n\n";
                                @flush();

                                if (connection_aborted()) {
                                    break 3;
                                }
                            }

                            $buffer .= $driverBuffer;
                            $usedDriver = $driverName;
                            break 2;
                        } catch (\Throwable $driverException) {
                            $lastException = $driverException;
                            $failedDrivers = $this->appendDriverAttempt($failedDrivers, $driverName);

                            Log::warning('resume_optimize_stream_driver_failed', [
                                'resume_id' => $resume->id,
                                'target_job' => $targetJob,
                                'driver' => $driverName,
                                'driver_attempt' => $driverAttempt,
                                'error' => $driverException->getMessage(),
                                'received_chunk' => $driverHasChunk,
                                'is_rate_limited' => $aiManager->isRateLimitException($driverException),
                            ]);

                            if ($driverHasChunk || ! $aiManager->shouldRetryWithFallback($driverException)) {
                                throw $driverException;
                            }

                            if ($aiManager->shouldRetrySameDriverAfterBackoff($driverName, $driverException, $driverAttempt)) {
                                $backoffMs = $aiManager->resolveBackoffMilliseconds($driverException, $driverAttempt);

                                echo "event: retry\n";
                                echo 'data: '.json_encode([
                                    'driver' => $driverName,
                                    'backoff_ms' => $backoffMs,
                                    'message' => sprintf(
                                        '%s 触发 429/1302 限流，正在等待 %.1f 秒后自动重试。',
                                        $this->driverLabel($driverName),
                                        $backoffMs / 1000
                                    ),
                                ])."\n\n";
                                @flush();

                                usleep($backoffMs * 1000);

                                continue;
                            }

                            $nextDriver = null;
                            foreach ($attemptDrivers as $candidateDriver) {
                                if ($candidateDriver === $driverName) {
                                    continue;
                                }
                                if (in_array($candidateDriver, $failedDrivers, true)) {
                                    continue;
                                }
                                $nextDriver = $candidateDriver;
                                break;
                            }

                            echo "event: fallback\n";
                            echo 'data: '.json_encode([
                                'failed_driver' => $driverName,
                                'next_driver' => $nextDriver,
                                'message' => $this->buildFallbackMessage($driverName, $nextDriver, $driverException),
                            ])."\n\n";
                            @flush();

                            break;
                        }
                    }
                }

                if ($usedDriver === null && $lastException instanceof \Throwable) {
                    throw $lastException;
                }

                $result = json_decode($buffer, true);
                if (is_array($result) && isset($result['optimized_text'])) {
                    $resume->forceFill([
                        'target_job' => $targetJob,
                        'target_company' => $options['target_company'] ?: null,
                        'target_job_title' => $options['target_job_title'] ?: null,
                        'target_job_description' => $options['target_job_description'] ?: null,
                        'optimize_goals' => ! empty($options['optimize_goals']) ? $options['optimize_goals'] : null,
                        'optimized_text' => $result['optimized_text'],
                        'highlights' => $result['highlights'] ?? [],
                    ])->save();

                    $onSuccess();

                    Log::info('resume_optimize_stream_succeeded', [
                        'resume_id' => $resume->id,
                        'user_id' => $resume->user_id,
                        'driver' => $usedDriver,
                    ]);

                    $eventSeq++;
                    echo "id: evt-{$eventSeq}\n";
                    echo "event: done\n";
                    echo 'data: '.json_encode([
                        'success' => true,
                        'optimized_text' => $result['optimized_text'],
                        'highlights' => $result['highlights'] ?? [],
                        'changes_summary' => is_string($result['changes_summary'] ?? null) ? $result['changes_summary'] : '',
                        'driver' => $usedDriver,
                        'fallback_from' => $usedDriver !== null && $primaryDriver !== null && $usedDriver !== $primaryDriver
                            ? $primaryDriver
                            : null,
                    ])."\n\n";
                    @flush();

                    // 完成后清理 Redis 缓存
                    Cache::forget($bufferCacheKey);
                    Cache::forget($eventSeqCacheKey);
                } else {
                    echo "event: error\n";
                    echo 'data: '.json_encode(['error' => 'AI 返回格式不正确，无法解析优化结果。'])."\n\n";
                }
            } catch (\Throwable $e) {
                Log::warning('resume_optimize_stream_failed', [
                    'resume_id' => $resume->id,
                    'target_job' => $targetJob,
                    'error' => $e->getMessage(),
                ]);
                echo "event: error\n";
                $errorMessage = $this->buildStreamErrorMessage($e, $failedDrivers);
                echo 'data: '.json_encode([
                    'error' => $errorMessage,
                    'driver' => $usedDriver,
                    'attempted_drivers' => array_values($failedDrivers),
                ])."\n\n";
            }
            @flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        // 暴露 streamId 给客户端，断线重连时携带以支持续传
        $response->headers->set('X-Stream-Id', $streamId);

        return $response;
    }

    private function prewarmResponse(): StreamedResponse
    {
        return response()->stream(function (): void {
            echo "event: start\n";
            echo 'data: '.json_encode(['message' => 'warmup'])."\n\n";
            echo "event: done\n";
            echo 'data: '.json_encode(['success' => true, 'prewarm' => true])."\n\n";
            @flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function accessDeniedResponse(): StreamedResponse
    {
        return response()->stream(function (): void {
            echo "event: error\n";
            echo 'data: '.json_encode(['error' => '当前套餐不支持高级模型与深度策略，请升级专业版后使用。'])."\n\n";
            @flush();
        }, 403, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>|null  $modules
     */
    private function buildRawFromModules(?array $modules, Resume $resume): string
    {
        if (empty($modules)) {
            return (string) $resume->content_raw;
        }

        $lines = [];
        foreach ($modules as $moduleData) {
            $type = is_string($moduleData['type'] ?? null) ? $moduleData['type'] : '';
            $data = is_array($moduleData['data'] ?? null) ? $moduleData['data'] : [];

            if ($type === 'personal') {
                $lines[] = '## 个人信息';
                if (is_string($data['name'] ?? null) && $data['name'] !== '') {
                    $lines[] = $data['name'];
                }
                if (is_string($data['phone'] ?? null) && $data['phone'] !== '') {
                    $lines[] = '电话: '.$data['phone'];
                }
                if (is_string($data['email'] ?? null) && $data['email'] !== '') {
                    $lines[] = '邮箱: '.$data['email'];
                }
                if (is_string($data['location'] ?? null) && $data['location'] !== '') {
                    $lines[] = '城市: '.$data['location'];
                }
                if (is_string($data['content'] ?? null) && $data['content'] !== '') {
                    $lines[] = $data['content'];
                }
                $lines[] = '';

                continue;
            }

            if ($type === 'objective') {
                $lines[] = '## 求职意向';
                if (is_string($data['target_job'] ?? null) && $data['target_job'] !== '') {
                    $lines[] = '目标岗位: '.$data['target_job'];
                }
                if (is_string($data['content'] ?? null) && $data['content'] !== '') {
                    $lines[] = $data['content'];
                }
                $lines[] = '';

                continue;
            }

            if (isset($data['title']) && is_string($data['title']) && $data['title'] !== '') {
                $lines[] = '## '.$data['title'];
            }
            $meta = [];
            if (isset($data['subtitle']) && is_string($data['subtitle']) && $data['subtitle'] !== '') {
                $meta[] = $data['subtitle'];
            }
            if (isset($data['date']) && is_string($data['date']) && $data['date'] !== '') {
                $meta[] = $data['date'];
            }
            if (isset($data['location']) && is_string($data['location']) && $data['location'] !== '') {
                $meta[] = $data['location'];
            }
            if (! empty($meta)) {
                $lines[] = implode(' | ', $meta);
            }
            if (isset($data['content']) && is_string($data['content']) && $data['content'] !== '') {
                $lines[] = trim($data['content']);
            }
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        $lines[] = '- '.$item;
                    }
                }
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int,string>  $failedDrivers
     */
    private function buildStreamErrorMessage(\Throwable $exception, array $failedDrivers = []): string
    {
        $failedDrivers = array_values(array_filter(array_map(
            static fn ($driver): string => is_string($driver) ? trim($driver) : '',
            $failedDrivers
        )));

        if ($this->aiManager->isRateLimitException($exception)) {
            if (count($failedDrivers) >= 2) {
                return sprintf(
                    '%s 触发 429/1302 限流后，已自动退避并切换到 %s，但当前仍未成功，请稍后重试。',
                    $this->driverLabel($failedDrivers[0]),
                    $this->driverLabel($failedDrivers[1])
                );
            }

            if (count($failedDrivers) === 1) {
                return sprintf(
                    '%s 当前触发 429/1302 限流，请稍后重试。',
                    $this->driverLabel($failedDrivers[0])
                );
            }

            return 'AI 服务当前触发 429/1302 限流，请稍后重试。';
        }

        if (count($failedDrivers) >= 2) {
            return sprintf(
                '%s 不可用后已自动尝试 %s，但当前仍未成功，请稍后重试。',
                $this->driverLabel($failedDrivers[0]),
                $this->driverLabel($failedDrivers[1])
            );
        }

        if (count($failedDrivers) === 1) {
            return sprintf('%s 当前不可用，请稍后重试。', $this->driverLabel($failedDrivers[0]));
        }

        return 'AI 优化暂时不可用，请稍后重试。';
    }

    private function driverLabel(string $driver): string
    {
        return match (strtolower(trim($driver))) {
            'zhipu' => '智谱',
            'deepseek' => 'DeepSeek',
            'volcano' => '火山引擎',
            default => trim($driver) !== '' ? $driver : 'AI',
        };
    }

    /**
     * @param  array<int,string>  $failedDrivers
     * @return array<int,string>
     */
    private function appendDriverAttempt(array $failedDrivers, string $driverName): array
    {
        $driverName = trim($driverName);
        if ($driverName === '' || in_array($driverName, $failedDrivers, true)) {
            return $failedDrivers;
        }

        $failedDrivers[] = $driverName;

        return $failedDrivers;
    }

    private function buildFallbackMessage(string $failedDriver, ?string $nextDriver, \Throwable $exception): string
    {
        $failedLabel = $this->driverLabel($failedDriver);
        $nextLabel = $nextDriver !== null ? $this->driverLabel($nextDriver) : '其他可用模型';
        $isRateLimited = $this->aiManager->isRateLimitException($exception);

        if ($isRateLimited) {
            return sprintf('%s 触发 429/1302 限流，已自动切换到 %s。', $failedLabel, $nextLabel);
        }

        return sprintf('%s 当前不可用，正在自动切换到 %s。', $failedLabel, $nextLabel);
    }
}
