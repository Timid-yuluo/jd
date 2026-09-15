<?php

declare(strict_types=1);

namespace App\Infrastructure\AI;

use App\Infrastructure\AI\Contracts\AiProvider;
use Closure;
use Generator;
use Throwable;

/**
 * AI Driver Fallback 执行器
 *
 * 统一封装同步与流式场景下的多 Driver 重试 + Fallback 逻辑，
 * 消除 ResumeOptimizeSessionService 与 ResumeOptimizeStreamService 中的重复代码。
 */
final class AiFallbackExecutor
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 同步执行：按 driver 顺序尝试，支持同 driver 退避重试 + 跨 driver fallback
     *
     * @param  string  $contentRaw  原始简历内容
     * @param  string  $targetJob  目标岗位
     * @param  array<string,mixed>  $options  AI 调用选项
     * @param  callable(string $driverName, array $result): void  $onSuccess  成功回调
     * @param  callable(string $driverName, Throwable $e, int $attempt): void  $onDriverFailed  单 driver 失败回调
     * @param  callable(string $driverName, int $backoffMs): void  $onBackoff  退避回调
     * @return array{result:array|null, used_driver:string|null, last_exception:Throwable|null, failed_drivers:array<int,string>}
     */
    public function executeSync(
        string $contentRaw,
        string $targetJob,
        array $options,
        callable $onSuccess = null,
        callable $onDriverFailed = null,
        callable $onBackoff = null
    ): array {
        $attemptDrivers = $this->aiManager->orderedDrivers();
        $primaryDriver = $attemptDrivers[0] ?? null;
        $result = null;
        $usedDriver = null;
        $lastException = null;
        $failedDrivers = [];

        foreach ($attemptDrivers as $driverName) {
            $driverAttempt = 0;

            while (true) {
                $driverAttempt++;

                try {
                    $result = $this->aiManager->provider($driverName)->optimizeResume($contentRaw, $targetJob, $options);
                    $usedDriver = $driverName;

                    if ($onSuccess !== null) {
                        $onSuccess($driverName, $result);
                    }

                    break 2;
                } catch (Throwable $exception) {
                    $lastException = $exception;
                    $failedDrivers[] = $driverName;

                    if ($onDriverFailed !== null) {
                        $onDriverFailed($driverName, $exception, $driverAttempt);
                    }

                    if (! $this->aiManager->shouldRetryWithFallback($exception)) {
                        throw $exception;
                    }

                    if ($this->aiManager->shouldRetrySameDriverAfterBackoff($driverName, $exception, $driverAttempt)) {
                        $backoffMs = $this->aiManager->resolveBackoffMilliseconds($exception, $driverAttempt);

                        if ($onBackoff !== null) {
                            $onBackoff($driverName, $backoffMs);
                        }

                        usleep($backoffMs * 1000);

                        // 重试前移除失败标记，避免重复记录
                        array_pop($failedDrivers);
                        continue;
                    }

                    break;
                }
            }
        }

        return [
            'result' => $result,
            'used_driver' => $usedDriver,
            'primary_driver' => $primaryDriver,
            'last_exception' => $lastException,
            'failed_drivers' => array_values(array_unique($failedDrivers)),
        ];
    }

    /**
     * 流式执行：按 driver 顺序尝试，通过回调推送 chunk
     *
     * @param  string  $contentRaw  原始简历内容
     * @param  string  $targetJob  目标岗位
     * @param  array<string,mixed>  $options  AI 调用选项
     * @param  callable(string $chunk, string $buffer): void  $onChunk  chunk 回调
     * @param  callable(string $driverName, Throwable $e, int $attempt, bool $hasChunk): void  $onDriverFailed
     * @param  callable(string $driverName, int $backoffMs): void  $onBackoff
     * @param  callable(string $failedDriver, ?string $nextDriver): void  $onFallback
     * @param  callable(): bool  $shouldAbort  是否中止（如 connection_aborted）
     * @return array{buffer:string, used_driver:string|null, primary_driver:string|null, last_exception:Throwable|null, failed_drivers:array<int,string>}
     */
    public function executeStream(
        string $contentRaw,
        string $targetJob,
        array $options,
        callable $onChunk,
        callable $onDriverFailed = null,
        callable $onBackoff = null,
        callable $onFallback = null,
        callable $shouldAbort = null
    ): array {
        $attemptDrivers = $this->aiManager->orderedDrivers();
        $primaryDriver = $attemptDrivers[0] ?? null;
        $buffer = '';
        $usedDriver = null;
        $lastException = null;
        $failedDrivers = [];

        foreach ($attemptDrivers as $driverName) {
            $driverAttempt = 0;

            while (true) {
                $driverAttempt++;
                $driverBuffer = '';
                $driverHasChunk = false;

                try {
                    $provider = $this->aiManager->provider($driverName);

                    foreach ($provider->optimizeResumeStream($contentRaw, $targetJob, $options) as $chunk) {
                        $driverHasChunk = true;
                        $driverBuffer .= $chunk;
                        $onChunk($chunk, $buffer.$driverBuffer);

                        if ($shouldAbort !== null && $shouldAbort()) {
                            break 3;
                        }
                    }

                    $buffer .= $driverBuffer;
                    $usedDriver = $driverName;
                    break 2;
                } catch (Throwable $driverException) {
                    $lastException = $driverException;
                    $failedDrivers[] = $driverName;

                    if ($onDriverFailed !== null) {
                        $onDriverFailed($driverName, $driverException, $driverAttempt, $driverHasChunk);
                    }

                    if ($driverHasChunk || ! $this->aiManager->shouldRetryWithFallback($driverException)) {
                        throw $driverException;
                    }

                    if ($this->aiManager->shouldRetrySameDriverAfterBackoff($driverName, $driverException, $driverAttempt)) {
                        $backoffMs = $this->aiManager->resolveBackoffMilliseconds($driverException, $driverAttempt);

                        if ($onBackoff !== null) {
                            $onBackoff($driverName, $backoffMs);
                        }

                        usleep($backoffMs * 1000);

                        array_pop($failedDrivers);
                        continue;
                    }

                    // 选择下一个 fallback driver
                    $nextDriver = null;
                    foreach ($attemptDrivers as $candidateDriver) {
                        if ($candidateDriver === $driverName || in_array($candidateDriver, $failedDrivers, true)) {
                            continue;
                        }
                        $nextDriver = $candidateDriver;
                        break;
                    }

                    if ($onFallback !== null) {
                        $onFallback($driverName, $nextDriver);
                    }

                    break;
                }
            }
        }

        return [
            'buffer' => $buffer,
            'used_driver' => $usedDriver,
            'primary_driver' => $primaryDriver,
            'last_exception' => $lastException,
            'failed_drivers' => array_values(array_unique($failedDrivers)),
        ];
    }
}
