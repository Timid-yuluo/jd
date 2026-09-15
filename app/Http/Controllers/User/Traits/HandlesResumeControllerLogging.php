<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 简历控制器统一日志Trait — 确保所有日志包含标准上下文字段
 */
trait HandlesResumeControllerLogging
{
    /**
     * 记录用户可见的异常日志，自动补充标准上下文字段
     *
     * @param  string  $event  事件名（如 resume_optimize_failed）
     * @param  \Throwable  $exception  捕获的异常
     * @param  array<string, mixed>  $context  业务上下文（resume_id、user_id 等）
     */
    private function logUserFacingException(string $event, \Throwable $exception, array $context = []): void
    {
        $request = request();

        Log::warning($event, array_merge([
            'trace_id' => $request?->attributes->get('request_id'),
            'user_id' => $request?->user()?->id,
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ], $context));
    }
}
