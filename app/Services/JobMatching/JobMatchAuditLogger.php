<?php

declare(strict_types=1);

namespace App\Services\JobMatching;

use App\Models\JobMatchAnalysis;
use App\Models\JobMatchAuditLog;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 岗位匹配审计日志记录器
 *
 * 将分析请求的成功 / 失败 / 删除事件落库到 job_match_audit_logs，
 * 用于运营排查 AI 失败原因、配额消耗追溯与异常诊断。
 */
final class JobMatchAuditLogger
{
    /**
     * 记录分析失败。
     *
     * @param  array<string, mixed>  $context  额外审计字段，可包含：
     *      - failure_type (string)        失败类型
     *      - retryable (bool)             是否可重试
     *      - attempted_drivers (string[]) 尝试过的驱动列表
     *      - driver (string|null)         最终使用的驱动
     *      - model (string|null)          模型标识
     *      - latency_ms (int|null)        耗时（毫秒）
     *      - quota_source (string|null)   配额来源
     *      - credit_id (int|null)         次卡 ID
     *      - job_description_length (int) 岗位描述长度
     *      - job_description_preview (string) 岗位描述预览
     *      - message (string)             异常消息
     */
    public function logFailure(User $user, Request $request, ?Resume $resume, array $context): void
    {
        $this->create([
            'user_id' => $user->id,
            'resume_id' => $resume?->id,
            'job_match_analysis_id' => null,
            'action' => 'analyze',
            'status' => 'failed',
            'driver' => $context['driver'] ?? null,
            'model' => $context['model'] ?? null,
            'failure_type' => $context['failure_type'] ?? null,
            'latency_ms' => $context['latency_ms'] ?? null,
            'quota_source' => $context['quota_source'] ?? null,
            'credit_id' => $context['credit_id'] ?? null,
            'context' => $this->buildContext($request, $context),
        ], $request);
    }

    /**
     * 记录分析成功。
     *
     * @param  array<string, mixed>  $context  额外审计字段，可包含：
     *      - driver (string|null)         实际驱动
     *      - model (string|null)          模型标识
     *      - latency_ms (int|null)        耗时（毫秒）
     *      - attempted_drivers (string[]) 尝试过的驱动列表
     *      - quota_source (string|null)   配额来源
     *      - credit_id (int|null)         次卡 ID
     *      - job_description_length (int) 岗位描述长度
     *      - job_description_preview (string) 岗位描述预览
     *      - match_score (float|null)     匹配分
     */
    public function logSuccess(JobMatchAnalysis $analysis, Request $request, array $context): void
    {
        $this->create([
            'user_id' => $analysis->user_id,
            'resume_id' => $analysis->resume_id,
            'job_match_analysis_id' => $analysis->id,
            'action' => 'analyze',
            'status' => 'success',
            'driver' => $context['driver'] ?? null,
            'model' => $context['model'] ?? null,
            'failure_type' => null,
            'latency_ms' => $context['latency_ms'] ?? null,
            'quota_source' => $context['quota_source'] ?? null,
            'credit_id' => $context['credit_id'] ?? null,
            'context' => $this->buildContext($request, $context),
        ], $request);
    }

    /**
     * 记录历史分析记录删除。
     *
     * @param  array<string, mixed>  $context  额外审计字段，可包含：
     *      - job_description_length (int) 岗位描述长度
     *      - match_score (float|null)     匹配分
     */
    public function logDeletion(JobMatchAnalysis $history, Request $request, array $context): void
    {
        $this->create([
            'user_id' => $history->user_id,
            'resume_id' => $history->resume_id,
            'job_match_analysis_id' => $history->id,
            'action' => 'delete',
            'status' => 'success',
            'driver' => null,
            'model' => null,
            'failure_type' => null,
            'latency_ms' => null,
            'quota_source' => null,
            'credit_id' => null,
            'context' => $this->buildContext($request, $context),
        ], $request);
    }

    /**
     * 落库审计日志条目
     *
     * @param  array<string, mixed>  $attributes  已映射到表字段的属性
     * @param  Request  $request  当前请求实例（用于提取 IP / UA / Request ID）
     */
    private function create(array $attributes, Request $request): void
    {
        $attributes['request_id'] = $request->header('X-Request-ID') ?? Str::uuid()->toString();
        $attributes['ip_address'] = $request->ip();
        $attributes['user_agent'] = mb_substr((string) $request->userAgent(), 0, 500);

        JobMatchAuditLog::create($attributes);
    }

    /**
     * 组装 context JSON 字段：保留传入上下文 + 请求特征
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function buildContext(Request $request, array $context): array
    {
        // 移除已映射到独立列的字段，避免在 context 中重复存储
        $reserved = [
            'driver', 'model', 'failure_type', 'latency_ms',
            'quota_source', 'credit_id', 'message',
        ];
        $extra = array_filter(
            $context,
            static fn ($value, $key): bool => !in_array($key, $reserved, true) && $value !== null,
            ARRAY_FILTER_USE_BOTH,
        );

        return array_merge($extra, [
            'method' => $request->method(),
            'path' => $request->path(),
        ]);
    }
}
