<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Models\Resume;
use App\Models\ResumeOptimizeSession;
use App\Models\ResumeOptimizeVersion;
use App\Services\Membership\PlanFeatureService;
use App\Services\Resume\ResumeOptimizeSessionService;
use App\Services\Resume\Support\ResumeOptimizeErrorMapper;
use App\Services\Resume\Support\ResumeOptimizationPayloadSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

final class ResumeOptimizeSessionController extends Controller
{
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeOptimizeSessionService $sessionService,
        private readonly ResumeOptimizationPayloadSanitizer $payloadSanitizer,
        private readonly PlanFeatureService $planFeatureService,
        private readonly ResumeOptimizeErrorMapper $errorMapper,
    ) {}

    public function create(Request $request, Resume $resume): JsonResponse
    {
        abort_unless((bool) config('resume.optimize_session.enabled', true), 404);
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'target_job' => ['nullable', 'string', 'max:255'],
            'target_company' => ['nullable', 'string', 'max:120'],
            'target_job_title' => ['nullable', 'string', 'max:120'],
            'target_job_description' => ['nullable', 'string', 'max:5000'],
            'optimize_mode' => ['nullable', 'string', Rule::in(['quick', 'balanced', 'deep'])],
            'prompt_strategy_template' => ['nullable', 'string', 'max:80'],
            'optimize_goals' => ['nullable', 'array'],
            'optimize_goals.*' => ['string', 'in:ats_keywords,structure,quantified,skill_match,language,highlights,tailor_job,concise,authenticity,readability,industry_fit,career_pivot,leadership,i18n_expression,project_impact,tech_depth,cross_cultural,certification,innovation,data_driven'],
            'focus_keywords' => ['nullable', 'array'],
            'focus_keywords.*' => ['string', 'max:64'],
            'resume_profile' => ['nullable', 'array'],
            'resume_profile.keyword_missing' => ['nullable', 'array'],
            'resume_profile.keyword_missing.*' => ['string', 'max:64'],
            'resume_profile.weak_modules' => ['nullable', 'array'],
            'resume_profile.weak_modules.*' => ['string', 'max:64'],
            'module_strategies' => ['nullable', 'array'],
            'module_strategies.*' => ['string', Rule::in(['balanced', 'results', 'technical'])],
            'modules_snapshot' => ['nullable', 'array'],
        ]);
        if ((bool) config('resume.optimize_session.accept_extended_signals', true)) {
            $validated['resume_profile'] = $this->payloadSanitizer->sanitizeResumeProfile($request->input('resume_profile'));
            $validated['module_strategies'] = $this->payloadSanitizer->sanitizeModuleStrategies($validated['module_strategies'] ?? null);
        } else {
            $validated['resume_profile'] = [];
            $validated['module_strategies'] = [];
        }
        $allowCreditOverride = $request->attributes->get('quota_source') === 'credit'
            && $request->attributes->get('quota_key') === 'optimize_full';
        $optimizeAccess = $this->planFeatureService->resolveOptimizeAccess(
            $request->user(),
            is_string($validated['optimize_mode'] ?? null) ? $validated['optimize_mode'] : null,
            is_string($validated['prompt_strategy_template'] ?? null) ? $validated['prompt_strategy_template'] : null,
            $allowCreditOverride
        );

        Log::info('resume_optimize_session_access_resolved', [
            'resume_id' => $resume->id,
            'user_id' => $request->user()?->id,
            'requested_mode' => (string) ($validated['optimize_mode'] ?? 'balanced'),
            'requested_template' => (string) ($validated['prompt_strategy_template'] ?? 'general'),
            'quota_source' => $request->attributes->get('quota_source'),
            'quota_key' => $request->attributes->get('quota_key'),
            'credit_id' => $request->attributes->get('credit_id'),
            'allow_credit_override' => $allowCreditOverride,
            'resolved_allowed' => $optimizeAccess['allowed'],
            'resolved_mode' => $optimizeAccess['optimize_mode'] ?? null,
            'resolved_template' => $optimizeAccess['prompt_strategy_template'] ?? null,
        ]);

        if (! $optimizeAccess['allowed']) {
            Log::warning('resume_optimize_session_access_denied', [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
                'requested_mode' => (string) ($validated['optimize_mode'] ?? 'balanced'),
                'requested_template' => (string) ($validated['prompt_strategy_template'] ?? 'general'),
                'quota_source' => $request->attributes->get('quota_source'),
                'quota_key' => $request->attributes->get('quota_key'),
                'credit_id' => $request->attributes->get('credit_id'),
                'message' => $optimizeAccess['message'] ?? '',
            ]);

            return $this->fail($optimizeAccess['message'], 403);
        }
        $validated['optimize_mode'] = $optimizeAccess['optimize_mode'];
        $validated['prompt_strategy_template'] = $optimizeAccess['prompt_strategy_template'];
        $validated['quota_consumption'] = $this->resolveQuotaConsumptionContext($request);
        $queueHealth = $this->sessionService->assessQueueHealth();
        if (($queueHealth['healthy'] ?? true) !== true) {
            Log::warning('resume_optimize_session_queue_unhealthy', [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
                'optimize_mode' => $validated['optimize_mode'],
                'queue_health' => $queueHealth,
            ]);

            return $this->queueHealthFailureResponse(
                $queueHealth,
                (string) ($validated['optimize_mode'] ?? 'balanced'),
                (int) $request->attributes->get('credit_id', 0) > 0
            );
        }

        try {
            $session = $this->sessionService->createSession($resume, $request->user(), $validated);
        } catch (Throwable $exception) {
            Log::warning('resume_optimize_session_create_failed', [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->fail($exception->getMessage(), 503);
        }

        Log::info('resume_optimize_session_created', [
            'resume_id' => $resume->id,
            'user_id' => $request->user()?->id,
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'quota_source' => $request->attributes->get('quota_source'),
            'quota_key' => $request->attributes->get('quota_key'),
            'credit_id' => $request->attributes->get('credit_id'),
            'used_credit_override' => $allowCreditOverride,
        ]);

        return $this->respondSuccessPayload([
            'session_id' => $session->uuid,
            'status' => $session->status,
            'poll_url' => route('user.resumes.optimize-sessions.status', [$resume, $session]),
            'compare_url' => route('user.resumes.optimize-compare.show', [$resume, $session]),
        ]);
    }

    public function status(Request $request, Resume $resume, ResumeOptimizeSession $session): JsonResponse
    {
        abort_unless((bool) config('resume.optimize_session.enabled', true), 404);
        $this->authorize('update', $resume);
        $this->ensureSessionBelongsToResume($resume, $session);

        $session = $this->sessionService->reconcileSessionState($session);
        $session->loadMissing('version');

        // 通过错误映射器生成结构化错误信息，便于前端展示
        $errorInfo = $session->status === ResumeOptimizeSession::STATUS_FAILED
            ? $this->errorMapper->resolve($session->error_code)
            : [
                'code' => '',
                'message' => '',
                'retryable' => false,
                'hint' => '',
                'severity' => '',
                'category' => '',
            ];

        // 根据进度阶段生成用户可读的阶段文案
        $stageLabel = $this->resolveStageLabel($session);

        // 计算预估剩余时间（秒），帮助用户了解等待预期
        $estimatedRemainingSeconds = $this->estimateRemainingSeconds($session);

        return $this->respondSuccessPayload([
            'session_id' => $session->uuid,
            'status' => $session->status,
            'progress' => (int) $session->progress,
            'stage_label' => $stageLabel,
            'estimated_remaining_seconds' => $estimatedRemainingSeconds,
            'error_code' => $session->error_code,
            'error_message' => $session->error_message,
            'error' => $errorInfo,
            'compare_ready' => $session->status === ResumeOptimizeSession::STATUS_SUCCEEDED || $session->status === ResumeOptimizeSession::STATUS_APPLIED,
            'compare_url' => route('user.resumes.optimize-compare.show', [$resume, $session]),
            'can_retry' => in_array($session->status, [ResumeOptimizeSession::STATUS_FAILED, ResumeOptimizeSession::STATUS_CANCELED], true)
                && $errorInfo['retryable'],
            'retry_url' => route('user.resumes.optimize-sessions.retry', [$resume, $session]),
            'started_at' => optional($session->started_at)?->toIso8601String(),
            'finished_at' => optional($session->finished_at)?->toIso8601String(),
        ]);
    }

    /**
     * 根据会话状态和进度返回用户可读的阶段文案
     */
    private function resolveStageLabel(ResumeOptimizeSession $session): string
    {
        return match ($session->status) {
            ResumeOptimizeSession::STATUS_QUEUED => '任务排队中，请稍候...',
            ResumeOptimizeSession::STATUS_RUNNING => match (true) {
                $session->progress < 30 => '正在解析简历内容...',
                $session->progress < 50 => '正在调用 AI 模型...',
                $session->progress < 80 => 'AI 正在生成优化内容...',
                $session->progress < 100 => '正在处理优化结果...',
                default => '即将完成...',
            },
            ResumeOptimizeSession::STATUS_SUCCEEDED => '优化完成',
            ResumeOptimizeSession::STATUS_APPLIED => '已应用优化结果',
            ResumeOptimizeSession::STATUS_FAILED => '优化失败',
            ResumeOptimizeSession::STATUS_CANCELED => '任务已取消',
            default => '处理中...',
        };
    }

    /**
     * 根据已用时间和进度估算剩余时间（秒）
     *
     * 算法：基于已耗时和当前进度百分比推算总耗时，再计算剩余时间。
     * 仅在 RUNNING 状态下有意义，其他状态返回 null。
     */
    private function estimateRemainingSeconds(ResumeOptimizeSession $session): ?int
    {
        if ($session->status !== ResumeOptimizeSession::STATUS_RUNNING) {
            return null;
        }

        $progress = max(1, min(99, (int) $session->progress));
        $startedAt = $session->started_at ?? $session->queued_at;

        if (! $startedAt) {
            return null;
        }

        $elapsedSeconds = max(1, $startedAt->diffInSeconds(now()));

        // 基于线性进度估算：剩余 = 已耗时 * (剩余比例 / 已完成比例)
        $remainingRatio = (100 - $progress) / $progress;
        $estimated = (int) ceil($elapsedSeconds * $remainingRatio);

        // 限制在合理范围内：最少 3 秒，最多 300 秒
        return max(3, min(300, $estimated));
    }

    public function history(Request $request, Resume $resume): JsonResponse
    {
        abort_unless((bool) config('resume.optimize_session.enabled', true), 404);
        $this->authorize('update', $resume);

        $sessions = ResumeOptimizeSession::query()
            ->with('version')
            ->where('resume_id', $resume->id)
            ->where('user_id', $request->user()?->id)
            ->whereIn('status', [
                ResumeOptimizeSession::STATUS_QUEUED,
                ResumeOptimizeSession::STATUS_RUNNING,
                ResumeOptimizeSession::STATUS_SUCCEEDED,
                ResumeOptimizeSession::STATUS_FAILED,
                ResumeOptimizeSession::STATUS_APPLIED,
                ResumeOptimizeSession::STATUS_CANCELED,
            ])
            ->orderByDesc('id')
            ->limit((int) config('ui.limit.session_history', 30))
            ->get();

        $items = $sessions->map(function (ResumeOptimizeSession $session) use ($resume): array {
            $session = $this->sessionService->reconcileSessionState($session);
            $version = $session->version;
            $afterRaw = $version instanceof ResumeOptimizeVersion ? (string) ($version->after_raw ?? '') : '';
            $config = is_array($session->config) ? $session->config : [];
            $targetJob = trim((string) ($config['target_job'] ?? ''));
            $mode = trim((string) ($config['optimize_mode'] ?? 'balanced'));
            $usedDriver = trim((string) ($config['used_driver'] ?? ''));
            $fallbackFromDriver = trim((string) ($config['fallback_from_driver'] ?? ''));
            $status = (string) $session->status;
            $statusLabel = match ($status) {
                ResumeOptimizeSession::STATUS_QUEUED => '排队中',
                ResumeOptimizeSession::STATUS_RUNNING => '处理中',
                ResumeOptimizeSession::STATUS_SUCCEEDED => '已完成',
                ResumeOptimizeSession::STATUS_APPLIED => '已应用',
                ResumeOptimizeSession::STATUS_FAILED => '已失败',
                ResumeOptimizeSession::STATUS_CANCELED => '已取消',
                default => '未知状态',
            };
            $compareReady = in_array($status, [
                ResumeOptimizeSession::STATUS_SUCCEEDED,
                ResumeOptimizeSession::STATUS_APPLIED,
            ], true);
            $canRetry = in_array($status, [
                ResumeOptimizeSession::STATUS_FAILED,
                ResumeOptimizeSession::STATUS_CANCELED,
            ], true);

            return [
                'session_id' => $session->uuid,
                'status' => $status,
                'status_label' => $statusLabel,
                'target_job' => $targetJob,
                'optimize_mode' => $mode,
                'used_driver' => $usedDriver,
                'fallback_from_driver' => $fallbackFromDriver,
                'progress' => (int) $session->progress,
                'created_at' => optional($session->created_at)?->toDateTimeString(),
                'finished_at' => optional($session->finished_at)?->toDateTimeString(),
                'compare_url' => $compareReady ? route('user.resumes.optimize-compare.show', [$resume, $session]) : '',
                'compare_ready' => $compareReady,
                'after_raw' => $afterRaw,
                'after_preview' => Str::limit($afterRaw, 260),
                'error_code' => $session->error_code,
                'error_message' => $session->error_message,
                'can_retry' => $canRetry,
                'retry_url' => $canRetry ? route('user.resumes.optimize-sessions.retry', [$resume, $session]) : '',
            ];
        })->values()->all();

        return $this->respondSuccessPayload([
            'items' => $items,
        ]);
    }

    public function retry(Request $request, Resume $resume, ResumeOptimizeSession $session): JsonResponse
    {
        abort_unless((bool) config('resume.optimize_session.enabled', true), 404);
        $this->authorize('update', $resume);
        $this->ensureSessionBelongsToResume($resume, $session);

        $session = $this->sessionService->reconcileSessionState($session);
        if (! in_array($session->status, [ResumeOptimizeSession::STATUS_FAILED, ResumeOptimizeSession::STATUS_CANCELED], true)) {
            return $this->fail('当前任务状态不支持重试。', 409);
        }

        $sessionConfig = is_array($session->config) ? $session->config : [];
        $queueHealth = $this->sessionService->assessQueueHealth();
        if (($queueHealth['healthy'] ?? true) !== true) {
            $optimizeMode = (string) ($sessionConfig['optimize_mode'] ?? 'balanced');
            Log::warning('resume_optimize_session_retry_queue_unhealthy', [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
                'source_session_id' => $session->id,
                'source_session_uuid' => $session->uuid,
                'optimize_mode' => $optimizeMode,
                'queue_health' => $queueHealth,
            ]);

            return $this->queueHealthFailureResponse(
                $queueHealth,
                $optimizeMode,
                (int) $request->attributes->get('credit_id', 0) > 0
            );
        }

        try {
            $retrySession = $this->sessionService->retrySession($session, $request->user());
        } catch (Throwable $exception) {
            Log::warning('resume_optimize_session_retry_failed', [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
                'source_session_id' => $session->id,
                'source_session_uuid' => $session->uuid,
                'error' => $exception->getMessage(),
            ]);

            return $this->fail($exception->getMessage(), 409);
        }

        return $this->respondSuccessPayload([
            'message' => '已重新提交优化任务，正在后台处理。',
            'session_id' => $retrySession->uuid,
            'status' => $retrySession->status,
            'poll_url' => route('user.resumes.optimize-sessions.status', [$resume, $retrySession]),
            'compare_url' => route('user.resumes.optimize-compare.show', [$resume, $retrySession]),
        ]);
    }

    public function comparePage(Request $request, Resume $resume, ResumeOptimizeSession $session): View
    {
        abort_unless((bool) config('resume.optimize_session.enabled', true), 404);
        $this->authorize('update', $resume);
        $this->ensureSessionBelongsToResume($resume, $session);

        $session->loadMissing(['version']);
        /** @var ResumeOptimizeVersion|null $version */
        $version = $session->version;
        abort_if(! $version instanceof ResumeOptimizeVersion, 404, '当前会话尚无可对比结果。');

        $compareData = [
            'session' => $session,
            'resume' => $resume,
            'beforeRaw' => (string) ($version->before_raw ?? ''),
            'afterRaw' => (string) ($version->after_raw ?? ''),
            'beforeModules' => is_array($version->before_modules) ? $version->before_modules : [],
            'afterModules' => is_array($version->after_modules) ? $version->after_modules : [],
            'diffMap' => is_array($version->diff_map) ? $version->diff_map : [],
            'scoreDelta' => is_array($version->score_delta) ? $version->score_delta : [],
            'riskTips' => is_array($version->risk_tips) ? $version->risk_tips : [],
            'highlights' => is_array($version->highlights) ? $version->highlights : [],
            'resumeUpdatedAt' => optional($resume->updated_at)?->toIso8601String(),
        ];

        return view('user.resumes.optimize-compare', $compareData);
    }

    public function apply(Request $request, Resume $resume, ResumeOptimizeSession $session): JsonResponse
    {
        abort_unless((bool) config('resume.optimize_session.enabled', true), 404);
        $this->authorize('update', $resume);
        $this->ensureSessionBelongsToResume($resume, $session);

        $validated = $request->validate([
            'resume_updated_at' => ['nullable', 'date'],
            'selections' => ['required', 'array', 'min:1'],
            'selections.*.module_key' => ['required', 'string', 'max:64'],
            'selections.*.action' => ['required', 'string', Rule::in(['replace', 'append', 'skip'])],
            'selections.*.field_key' => ['nullable', 'string', 'max:64'],
            'selections.*.item_index' => ['nullable', 'integer', 'min:0'],
            'selections.*.allow_sensitive' => ['nullable', 'boolean'],
        ]);

        Log::info('resume_optimize_apply_request', [
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'session_status' => $session->status,
            'resume_id' => $resume->id,
            'user_id' => $request->user()?->id,
            'selection_count' => count($validated['selections']),
            'resume_updated_at' => $validated['resume_updated_at'] ?? null,
            'resume_current_updated_at' => optional($resume->updated_at)?->toIso8601String(),
        ]);

        try {
            $result = $this->sessionService->applySelections(
                $session,
                $request->user(),
                is_array($validated['selections'] ?? null) ? $validated['selections'] : [],
                isset($validated['resume_updated_at']) ? (string) $validated['resume_updated_at'] : null
            );
        } catch (\RuntimeException $exception) {
            Log::warning('resume_optimize_apply_failed', [
                'session_id' => $session->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->fail($exception->getMessage(), 409);
        }

        Log::info('resume_optimize_apply_succeeded', [
            'session_id' => $session->id,
            'result' => $result,
        ]);

        return $this->respondSuccessPayload([
            'message' => '已按选中范围应用优化内容。',
            'result' => $result,
            'redirect_url' => route('user.resumes.editor', $resume, false).'?optimize_applied='.$session->uuid,
        ]);
    }

    private function ensureSessionBelongsToResume(Resume $resume, ResumeOptimizeSession $session): void
    {
        abort_unless(
            $session->resume_id === $resume->id && $session->user_id === request()->user()?->id,
            404
        );
    }

    /**
     * @param  array<string,mixed>  $queueHealth
     */
    private function queueHealthFailureResponse(
        array $queueHealth,
        string $optimizeMode,
        bool $usingCredit = false
    ): JsonResponse {
        $message = $usingCredit
            ? '后台会话优化分支当前不可用，请稍后重试。本次未扣次卡。'
            : '后台会话优化分支当前不可用，请稍后重试。';

        return $this->fail($message, 503, [
            'code' => 'QUEUE_UNHEALTHY',
            'optimize_mode' => $optimizeMode,
            'suggested_action' => 'retry_later',
            'queue_health' => array_merge($queueHealth, [
                'degrade_to_stream' => false,
            ]),
        ]);
    }
}
