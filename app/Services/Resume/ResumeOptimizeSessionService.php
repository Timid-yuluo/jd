<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Domain\Events\DomainEventBusInterface;
use App\Domain\Events\Resume\ResumeOptimizeSessionCreated;
use App\Domain\Events\Resume\ResumeOptimizeSessionFailed;
use App\Domain\Events\Resume\ResumeOptimizeSessionSucceeded;
use App\Domain\Resume\ResumeOptimizeStateMachine;
use App\Infrastructure\AI\AiManager;
use App\Jobs\OptimizeResumeSessionJob;
use App\Models\Resume;
use App\Models\ResumeModule;
use App\Models\ResumeOptimizeSession;
use App\Models\ResumeOptimizeVersion;
use App\Models\User;
use App\Services\Membership\QuotaService;
use App\Services\Resume\Support\ResumeOptimizationPayloadSanitizer;
use App\Services\Resume\Support\ResumeOptimizeDiffAnalyzer;
use App\Services\Resume\Support\ResumeOptimizeErrorMapper;
use App\Services\Resume\Support\ResumeOptimizeResultParser;
use App\Services\Resume\Support\ResumeOptimizeSessionFailureHandler;
use App\Support\Resume\ResumeModuleSerializer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ResumeOptimizeSessionService
{
    public function __construct(
        private readonly AiManager $aiManager,
        private readonly ResumeOptimizeHealthService $healthService,
        private readonly ResumeOptimizationPayloadSanitizer $payloadSanitizer,
        private readonly OptimizeAbBucketService $abBucketService,
        private readonly ResumeOptimizeResultParser $resultParser,
        private readonly ResumeOptimizeDiffAnalyzer $diffAnalyzer,
        private readonly ResumeOptimizeSelectionService $selectionService,
        private readonly QuotaService $quotaService,
        private readonly ResumeOptimizeErrorMapper $errorMapper,
        private readonly ResumeOptimizeSessionFailureHandler $failureHandler,
        private readonly DomainEventBusInterface $eventBus,
        private readonly ResumeOptimizeStateMachine $stateMachine,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function createSession(Resume $resume, User $user, array $payload): ResumeOptimizeSession
    {
        $idempotencyKey = trim((string) ($payload['idempotency_key'] ?? ''));
        if ($idempotencyKey !== '') {
            $existing = ResumeOptimizeSession::query()
                ->where('resume_id', $resume->id)
                ->where('idempotency_key', $idempotencyKey)
                ->latest('id')
                ->first();
            if ($existing instanceof ResumeOptimizeSession) {
                return $existing;
            }
        }

        $modulesSnapshot = is_array($payload['modules_snapshot'] ?? null)
            ? $this->normalizeModulesArray($payload['modules_snapshot'])
            : $this->resumeModulesToArray($resume);
        $sourceRaw = $this->buildRawFromModulesArray($modulesSnapshot, (string) $resume->content_raw);
        $resumeProfile = $this->payloadSanitizer->sanitizeResumeProfile($payload['resume_profile'] ?? null);
        $moduleStrategies = $this->payloadSanitizer->sanitizeModuleStrategies($payload['module_strategies'] ?? null);
        $abVariant = $this->abBucketService->resolveVariant((int) $user->id, (int) $resume->id);

        $queueName = $this->resolveOptimizeQueueName($user, $payload);

        $config = [
            'target_job' => trim((string) ($payload['target_job'] ?? $resume->target_job ?? '')),
            'target_company' => trim((string) ($payload['target_company'] ?? $resume->target_company ?? '')),
            'target_job_title' => trim((string) ($payload['target_job_title'] ?? $resume->target_job_title ?? '')),
            'target_job_description' => trim((string) ($payload['target_job_description'] ?? $resume->target_job_description ?? '')),
            'optimize_mode' => trim((string) ($payload['optimize_mode'] ?? 'balanced')),
            'optimize_goals' => $this->normalizeOptimizeGoals(is_array($payload['optimize_goals'] ?? null) ? $payload['optimize_goals'] : []),
            'focus_keywords' => array_values(array_filter(
                is_array($payload['focus_keywords'] ?? null) ? $payload['focus_keywords'] : [],
                static fn ($item): bool => is_string($item) && trim($item) !== ''
            )),
            'prompt_strategy_template' => trim((string) ($payload['prompt_strategy_template'] ?? 'general')),
            'resume_profile' => $resumeProfile,
            'module_strategies' => $moduleStrategies,
            'ab_variant' => $abVariant,
            'modules_snapshot' => $modulesSnapshot,
            'source_raw' => $sourceRaw,
            'resume_updated_at' => optional($resume->updated_at)?->toIso8601String(),
            'quota_consumption' => $this->normalizeQuotaConsumptionContext($payload['quota_consumption'] ?? null),
            'dispatch_queue' => $queueName,
            'career_track_keywords' => $resume->careerTrack?->keywords ?? [],
            'career_track_avoid_words' => $resume->careerTrack?->avoid_words ?? [],
            'career_track_focus' => $resume->careerTrack?->optimization_focus ?? [],
        ];
        $retriedFromSessionId = (int) ($payload['retried_from_session_id'] ?? 0);
        if ($retriedFromSessionId > 0) {
            $config['retried_from_session_id'] = $retriedFromSessionId;
        }

        $session = ResumeOptimizeSession::query()->create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => ResumeOptimizeSession::STATUS_QUEUED,
            'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
            'progress' => 0,
            'config' => $config,
            'queued_at' => now(),
        ]);

        // 分发领域事件：会话已创建
        $this->eventBus->dispatch(new ResumeOptimizeSessionCreated(
            sessionId: $session->id,
            sessionUuid: (string) $session->uuid,
            resumeId: $resume->id,
            userId: $user->id,
            optimizeMode: (string) ($config['optimize_mode'] ?? 'balanced')
        ));

        try {
            $dispatch = OptimizeResumeSessionJob::dispatch($session->id);
            $dispatch->onQueue($queueName);
        } catch (Throwable $exception) {
            $this->markSessionFailed(
                $session,
                ResumeOptimizeErrorMapper::CODE_QUEUE_DISPATCH_FAILED,
                '优化任务派发失败，请稍后重试。',
                'queue_dispatch_failed',
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw new \RuntimeException('优化任务派发失败，请稍后重试。', previous: $exception);
        }

        $this->emitOptimizeEvent('resume_optimize_requested', [
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'target_job' => (string) ($config['target_job'] ?? ''),
            'optimize_mode' => (string) ($config['optimize_mode'] ?? 'balanced'),
            'prompt_strategy_template' => (string) ($config['prompt_strategy_template'] ?? 'general'),
            'ab_variant' => (string) ($config['ab_variant'] ?? 'baseline'),
            'queue' => $queueName,
        ]);
        $this->safeLogInfo('resume_optimize_session_queued', [
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'queue' => $queueName,
        ]);

        return $session;
    }

    public function retrySession(ResumeOptimizeSession $sourceSession, User $user): ResumeOptimizeSession
    {
        $resume = $sourceSession->resume;
        if (! $resume instanceof Resume) {
            throw new \RuntimeException('原始优化任务关联的简历不存在，无法重试。');
        }

        if ((int) $sourceSession->user_id !== (int) $user->id) {
            throw new \RuntimeException('无权重试该优化任务。');
        }

        $config = is_array($sourceSession->config) ? $sourceSession->config : [];
        unset(
            $config['quota_consumed_at'],
            $config['last_failure'],
            $config['retried_from_session_id']
        );

        $config['retried_from_session_id'] = $sourceSession->id;
        if ($this->shouldFallbackRetryToDefaultQueue($sourceSession, $user)) {
            $config['queue_override'] = (string) config('resume.optimize_session.default_queue', 'default');
            $this->safeLogWarning('resume_optimize_session_retry_queue_fallback', [
                'source_session_id' => $sourceSession->id,
                'source_session_uuid' => $sourceSession->uuid,
                'resume_id' => $sourceSession->resume_id,
                'user_id' => $sourceSession->user_id,
                'from_queue' => (string) (($sourceSession->config['dispatch_queue'] ?? '') ?: $this->resolveOptimizeQueueName($user)),
                'to_queue' => (string) $config['queue_override'],
                'error_code' => (string) $sourceSession->error_code,
            ]);
        }

        return $this->createSession($resume, $user, array_merge($config, [
            'idempotency_key' => 'retry-'.$sourceSession->uuid.'-'.Str::lower(Str::random(12)),
            'modules_snapshot' => is_array($config['modules_snapshot'] ?? null)
                ? $config['modules_snapshot']
                : $this->resumeModulesToArray($resume),
            'quota_consumption' => $this->normalizeQuotaConsumptionContext($config['quota_consumption'] ?? null),
        ]));
    }

    /**
     * @return array<string,mixed>
     */
    public function assessQueueHealth(): array
    {
        return $this->healthService->assessQueueHealth();
    }

    /**
     * @param  array<int,mixed>  $goals
     * @return array<int,string>
     */
    private function normalizeOptimizeGoals(array $goals): array
    {
        $allowed = [
            'ats_keywords',
            'structure',
            'quantified',
            'skill_match',
            'language',
            'highlights',
            'tailor_job',
            'concise',
            'authenticity',
            'readability',
            'industry_fit',
            'career_pivot',
            'leadership',
            'i18n_expression',
            'project_impact',
            'tech_depth',
            'cross_cultural',
            'certification',
            'innovation',
            'data_driven',
        ];
        $lookup = array_fill_keys($allowed, true);
        $result = [];
        foreach ($goals as $goal) {
            $key = trim((string) $goal);
            if ($key === '' || ! isset($lookup[$key]) || in_array($key, $result, true)) {
                continue;
            }
            $result[] = $key;
        }
        $guardEnabled = (bool) config('resume.optimize_session.authenticity_guard.default_goal', true);
        if ($guardEnabled && ! in_array('authenticity', $result, true)) {
            $result[] = 'authenticity';
        }

        return $result;
    }

    public function processSessionById(int $sessionId): void
    {
        $startedAtMs = (int) round(microtime(true) * 1000);
        /** @var ResumeOptimizeSession|null $session */
        $session = ResumeOptimizeSession::query()
            ->with(['resume.modules'])
            ->find($sessionId);
        if (! $session instanceof ResumeOptimizeSession) {
            return;
        }

        if (! in_array($session->status, [
            ResumeOptimizeSession::STATUS_QUEUED,
            ResumeOptimizeSession::STATUS_RUNNING,
            ResumeOptimizeSession::STATUS_DRAFT,
        ], true)) {
            return;
        }

        $lock = Cache::lock('resume-optimize-session:'.$session->id, 60);
        if (! $lock->get()) {
            return;
        }

        try {
            // 通过状态机进行状态转换，确保合法性
            $this->stateMachine->transition($session, ResumeOptimizeSession::STATUS_RUNNING);

            $session->forceFill([
                'progress' => 15,
                'started_at' => $session->started_at ?? now(),
                'error_code' => null,
                'error_message' => null,
            ])->save();
            $this->safeLogInfo('resume_optimize_session_started', [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'resume_id' => $session->resume_id,
                'user_id' => $session->user_id,
            ]);

            $resume = $session->resume;
            if (! $resume instanceof Resume) {
                $this->markSessionFailed(
                    $session,
                    ResumeOptimizeErrorMapper::CODE_RESUME_NOT_FOUND,
                    '关联简历不存在或已删除。',
                    'resume_missing'
                );

                return;
            }

            $config = is_array($session->config) ? $session->config : [];
            $sourceRaw = trim((string) ($config['source_raw'] ?? ''));
            if ($sourceRaw === '') {
                $sourceRaw = (string) $resume->content_raw;
            }

            $options = [
                'target_company' => (string) ($config['target_company'] ?? ''),
                'target_job_title' => (string) ($config['target_job_title'] ?? ''),
                'target_job_description' => (string) ($config['target_job_description'] ?? ''),
                'optimize_goals' => is_array($config['optimize_goals'] ?? null) ? $config['optimize_goals'] : [],
                'optimize_mode' => (string) ($config['optimize_mode'] ?? 'balanced'),
                'focus_keywords' => is_array($config['focus_keywords'] ?? null) ? $config['focus_keywords'] : [],
                'prompt_strategy_template' => (string) ($config['prompt_strategy_template'] ?? 'general'),
                'resume_profile' => $this->payloadSanitizer->sanitizeResumeProfile($config['resume_profile'] ?? null),
                'module_strategies' => $this->payloadSanitizer->sanitizeModuleStrategies($config['module_strategies'] ?? null),
                'ab_variant' => (string) ($config['ab_variant'] ?? 'baseline'),
            ];
            $targetJob = (string) ($config['target_job'] ?? $resume->target_job ?? '');

            // 阶段 2：准备 AI 调用上下文（进度 30）
            $this->updateSessionProgress($session, 30, 'preparing_ai_call');

            $driverOrder = $this->aiManager->orderedDrivers();
            $primaryDriver = $driverOrder[0] ?? null;

            // AI 调用（含降级重试）
            [$result, $usedDriver] = $this->executeOptimizeWithFallback(
                $session, $resume, $sourceRaw, $targetJob, $options, $driverOrder
            );

            if (! is_array($result)) {
                throw new \RuntimeException('未能获取有效的 AI 优化结果。');
            }

            $optimizedRaw = trim((string) ($result['optimized_text'] ?? ''));
            if ($optimizedRaw === '') {
                throw new \RuntimeException('优化结果为空，无法生成对比内容。');
            }

            // 阶段 4：AI 调用完成，开始结果处理（进度 80）
            $this->updateSessionProgress($session, 80, 'processing_result');

            $beforeModules = $this->normalizeModulesArray($config['modules_snapshot'] ?? $this->resumeModulesToArray($resume));
            $afterModules = $this->normalizeModulesArray($this->resultParser->parseRawToModules($optimizedRaw));

            $scoreBefore = $this->diffAnalyzer->estimateScore($beforeModules, (string) $resume->content_raw);
            $scoreAfter = $this->diffAnalyzer->estimateScore($afterModules, $optimizedRaw);
            $scoreDelta = [
                'before' => $scoreBefore,
                'after' => $scoreAfter,
                'delta' => $scoreAfter - $scoreBefore,
            ];

            $diffMap = $this->diffAnalyzer->buildDiffMap($beforeModules, $afterModules);
            $riskTips = $this->diffAnalyzer->buildRiskTips($beforeModules, $afterModules);
            $highlights = is_array($result['highlights'] ?? null) ? $result['highlights'] : [];

            DB::transaction(function () use ($resume, $session, $targetJob, $options, $optimizedRaw, $beforeModules, $afterModules, $diffMap, $scoreDelta, $riskTips, $highlights, $startedAtMs, $config, $usedDriver, $primaryDriver): void {
                $resume->forceFill([
                    'target_job' => $targetJob !== '' ? $targetJob : $resume->target_job,
                    'target_company' => $options['target_company'] !== '' ? $options['target_company'] : null,
                    'target_job_title' => $options['target_job_title'] !== '' ? $options['target_job_title'] : null,
                    'target_job_description' => $options['target_job_description'] !== '' ? $options['target_job_description'] : null,
                    'optimize_goals' => $options['optimize_goals'] !== [] ? $options['optimize_goals'] : null,
                    'optimized_text' => $optimizedRaw,
                    'highlights' => $highlights,
                ])->save();

                ResumeOptimizeVersion::query()->updateOrCreate(
                    ['session_id' => $session->id],
                    [
                        'before_raw' => (string) $resume->content_raw,
                        'after_raw' => $optimizedRaw,
                        'before_modules' => $beforeModules,
                        'after_modules' => $afterModules,
                        'diff_map' => $diffMap,
                        'score_delta' => $scoreDelta,
                        'risk_tips' => $riskTips,
                        'highlights' => $highlights,
                    ]
                );

                $sessionConfig = $config;
                $sessionConfig['used_driver'] = $usedDriver;
                $sessionConfig['fallback_from_driver'] = $usedDriver !== null && $primaryDriver !== null && $usedDriver !== $primaryDriver
                    ? $primaryDriver
                    : null;

                // 通过状态机进行状态转换，确保合法性
                $this->stateMachine->transition($session, ResumeOptimizeSession::STATUS_SUCCEEDED);

                $session->forceFill([
                    'progress' => 100,
                    'finished_at' => now(),
                    'config' => $sessionConfig,
                ])->save();
                $this->safeLogInfo('resume_optimize_session_succeeded', [
                    'session_id' => $session->id,
                    'session_uuid' => $session->uuid,
                    'resume_id' => $resume->id,
                    'user_id' => $session->user_id,
                    'driver' => $usedDriver,
                    'score_delta' => $scoreDelta['delta'] ?? 0,
                    'duration_ms' => max(0, (int) round(microtime(true) * 1000) - $startedAtMs),
                ]);
                $this->emitOptimizeEvent('resume_optimize_succeeded', [
                    'session_id' => $session->id,
                    'session_uuid' => $session->uuid,
                    'resume_id' => $resume->id,
                    'user_id' => $session->user_id,
                    'status' => $session->status,
                    'score_delta' => (int) ($scoreDelta['delta'] ?? 0),
                    'duration_ms' => max(0, (int) round(microtime(true) * 1000) - $startedAtMs),
                    'ab_variant' => (string) ($config['ab_variant'] ?? 'baseline'),
                ]);
                $this->warnWhenSessionSlow($session, $startedAtMs);

                // 分发领域事件：会话已成功完成
                $this->eventBus->dispatch(new ResumeOptimizeSessionSucceeded(
                    sessionId: $session->id,
                    sessionUuid: (string) $session->uuid,
                    resumeId: $resume->id,
                    userId: $session->user_id,
                    usedDriver: $usedDriver,
                    primaryDriver: $primaryDriver,
                    scoreBefore: (int) ($scoreDelta['before'] ?? 0),
                    scoreAfter: (int) ($scoreDelta['after'] ?? 0)
                ));
            });
            $this->consumeQuotaWhenSucceeded($session);
        } catch (Throwable $exception) {
            if ($this->isAiRateLimitException($exception)) {
                $this->markSessionQueuedForRetry($session, $exception);
                throw $exception;
            }

            $this->markSessionFailed(
                $session,
                ResumeOptimizeErrorMapper::CODE_OPTIMIZE_FAILED,
                Str::limit($exception->getMessage(), 500),
                'optimize_processing_failed',
                [
                    'duration_ms' => max(0, (int) round(microtime(true) * 1000) - $startedAtMs),
                    'ab_variant' => (string) ($config['ab_variant'] ?? 'baseline'),
                ]
            );
            $this->warnWhenSessionSlow($session, $startedAtMs);

            // 分发领域事件：会话已失败
            $this->eventBus->dispatch(new ResumeOptimizeSessionFailed(
                sessionId: $session->id,
                sessionUuid: (string) $session->uuid,
                resumeId: $session->resume_id,
                userId: $session->user_id,
                errorCode: ResumeOptimizeErrorMapper::CODE_OPTIMIZE_FAILED,
                errorMessage: Str::limit($exception->getMessage(), 500),
                retryable: $this->errorMapper->isRetryable(ResumeOptimizeErrorMapper::CODE_OPTIMIZE_FAILED)
            ));
        } finally {
            $lock->release();
        }
    }

    /**
     * 执行 AI 优化调用（含多驱动降级和同驱动退避重试）
     *
     * @param  array<int,string>  $driverOrder
     * @return array{0:array<string,mixed>|null, 1:string|null}
     */
    private function executeOptimizeWithFallback(
        ResumeOptimizeSession $session,
        Resume $resume,
        string $sourceRaw,
        string $targetJob,
        array $options,
        array $driverOrder
    ): array {
        $result = null;
        $usedDriver = null;
        $lastException = null;

        foreach ($driverOrder as $driverName) {
            $driverAttempt = 0;

            while (true) {
                $driverAttempt++;

                try {
                    $result = $this->aiManager->provider($driverName)->optimizeResume($sourceRaw, $targetJob, $options);
                    $usedDriver = $driverName;
                    break 2;
                } catch (Throwable $exception) {
                    $lastException = $exception;
                    $this->safeLogWarning('resume_optimize_session_driver_failed', [
                        'session_id' => $session->id,
                        'resume_id' => $resume->id,
                        'driver' => $driverName,
                        'driver_attempt' => $driverAttempt,
                        'error' => $exception->getMessage(),
                        'is_rate_limited' => $this->aiManager->isRateLimitException($exception),
                    ]);

                    if (! $this->aiManager->shouldRetryWithFallback($exception)) {
                        throw $exception;
                    }

                    if ($this->aiManager->shouldRetrySameDriverAfterBackoff($driverName, $exception, $driverAttempt)) {
                        $backoffMs = $this->aiManager->resolveBackoffMilliseconds($exception, $driverAttempt);
                        $this->safeLogWarning('resume_optimize_session_driver_backoff_retrying', [
                            'session_id' => $session->id,
                            'resume_id' => $resume->id,
                            'driver' => $driverName,
                            'driver_attempt' => $driverAttempt,
                            'backoff_ms' => $backoffMs,
                            'error' => $exception->getMessage(),
                        ]);

                        usleep($backoffMs * 1000);

                        continue;
                    }

                    break;
                }
            }
        }

        if (! is_array($result)) {
            throw $lastException instanceof Throwable
                ? $lastException
                : new \RuntimeException('未能获取有效的 AI 优化结果。');
        }

        return [$result, $usedDriver];
    }

    public function reconcileSessionState(ResumeOptimizeSession $session): ResumeOptimizeSession
    {
        if (! in_array($session->status, [
            ResumeOptimizeSession::STATUS_QUEUED,
            ResumeOptimizeSession::STATUS_RUNNING,
        ], true)) {
            return $session;
        }

        $staleAfterSeconds = max(60, (int) config('resume.optimize_session.stale_after_seconds', 300));
        $referenceAt = $session->started_at ?? $session->queued_at ?? $session->created_at;
        if ($referenceAt === null) {
            return $session;
        }

        $staleBefore = now()->subSeconds($staleAfterSeconds);
        if ($referenceAt->greaterThan($staleBefore)) {
            return $session;
        }

        $ageSeconds = max(0, $referenceAt->diffInSeconds(now()));

        return $this->markSessionFailed(
            $session->fresh() ?? $session,
            ResumeOptimizeErrorMapper::CODE_QUEUE_STALLED,
            '优化任务长时间未完成，可能是队列处理超时或 worker 未运行，请点击重试。',
            'queue_stalled',
            [
                'age_seconds' => $ageSeconds,
                'stale_after_seconds' => $staleAfterSeconds,
            ]
        );
    }

    public function markSessionFailedAfterQueueFailure(int $sessionId, ?Throwable $exception = null): void
    {
        $this->failureHandler->handleAfterQueueFailure($sessionId, $exception);
    }

    /**
     * @param  array<int,array<string,mixed>>  $selections
     * @return array<string,mixed>
     */
    public function applySelections(ResumeOptimizeSession $session, User $user, array $selections, ?string $resumeUpdatedAt = null): array
    {
        return $this->selectionService->applySelections($session, $user, $selections, $resumeUpdatedAt);
    }

    /**
     * @return array{source:string,quota_key:string,credit_id?:int}|null
     */
    private function normalizeQuotaConsumptionContext(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $source = trim((string) ($value['source'] ?? ''));
        $quotaKey = trim((string) ($value['quota_key'] ?? ''));
        if ($source === '' || $quotaKey === '') {
            return null;
        }

        $context = [
            'source' => $source,
            'quota_key' => $quotaKey,
        ];

        if ($source === 'credit') {
            $creditId = (int) ($value['credit_id'] ?? 0);
            if ($creditId < 1) {
                return null;
            }

            $context['credit_id'] = $creditId;
        }

        return $context;
    }

    private function consumeQuotaWhenSucceeded(ResumeOptimizeSession $session): void
    {
        $config = is_array($session->config) ? $session->config : [];
        $context = $this->normalizeQuotaConsumptionContext($config['quota_consumption'] ?? null);
        if ($context === null) {
            return;
        }

        if (filled($config['quota_consumed_at'] ?? null)) {
            return;
        }

        try {
            $this->quotaService->consumeFromContext($context, (int) $session->user_id);
            $config['quota_consumed_at'] = now()->toIso8601String();
            $session->forceFill(['config' => $config])->save();
        } catch (Throwable $exception) {
            $this->safeLogWarning('resume_optimize_session_quota_consume_failed', [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'resume_id' => $session->resume_id,
                'user_id' => $session->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveOptimizeQueueName(User $user, array $payload = []): string
    {
        $queueOverride = trim((string) ($payload['queue_override'] ?? ''));
        if ($queueOverride !== '') {
            return $queueOverride;
        }

        if ((bool) $user->currentPlan()?->hasFeature('priority_queue')) {
            return (string) config('resume.optimize_session.priority_queue', 'default');
        }

        return (string) config('resume.optimize_session.default_queue', 'default');
    }

    private function shouldFallbackRetryToDefaultQueue(ResumeOptimizeSession $sourceSession, User $user): bool
    {
        $config = is_array($sourceSession->config) ? $sourceSession->config : [];
        $currentQueue = trim((string) ($config['dispatch_queue'] ?? ''));
        $defaultQueue = (string) config('resume.optimize_session.default_queue', 'default');

        if ($currentQueue === '') {
            $currentQueue = $this->resolveOptimizeQueueName($user);
        }

        return $currentQueue !== $defaultQueue
            && $this->isQueueFailureCode((string) $sourceSession->error_code);
    }

    private function isQueueFailureCode(string $errorCode): bool
    {
        return $this->failureHandler->isQueueFailureCode($errorCode);
    }

    private function markSessionQueuedForRetry(ResumeOptimizeSession $session, Throwable $exception): void
    {
        $this->failureHandler->markQueuedForRetry($session, $exception);
    }

    /**
     * 更新会话进度（仅当新进度大于当前进度时更新，避免回退）
     *
     * 阶段定义：
     * - 15: 会话开始处理
     * - 30: 准备 AI 调用上下文
     * - 50: AI 调用中（可选，流式场景由 StreamService 推送）
     * - 80: AI 调用完成，处理结果
     * - 100: 全部完成
     *
     * @param  string  $stage  阶段标识，用于日志追踪
     */
    private function updateSessionProgress(ResumeOptimizeSession $session, int $progress, string $stage = ''): void
    {
        $current = (int) $session->progress;
        if ($progress <= $current) {
            return;
        }

        $session->forceFill(['progress' => $progress])->save();

        if ($stage !== '') {
            $this->safeLogInfo('resume_optimize_session_progress', [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'progress' => $progress,
                'stage' => $stage,
            ]);
        }
    }

    private function isAiRateLimitException(?Throwable $exception): bool
    {
        return $this->failureHandler->isAiRateLimitException($exception);
    }

    /**
     * 标记会话失败，委托给 FailureHandler 统一处理
     *
     * @param  array<string,mixed>  $meta
     */
    private function markSessionFailed(
        ResumeOptimizeSession $session,
        string $errorCode,
        string $message,
        string $stage,
        array $meta = []
    ): ResumeOptimizeSession {
        return $this->failureHandler->markFailed($session, $errorCode, $message, $stage, $meta);
    }

    /**
     * @param  array<int,array<string,mixed>>  $modules
     * @return array<int,array<string,mixed>>
     */
    private function normalizeModulesArray(array $modules): array
    {
        $rows = [];
        $typeIndex = [];
        foreach ($modules as $idx => $module) {
            $type = trim((string) ($module['type'] ?? ''));
            if ($type === '') {
                continue;
            }
            $typeIndex[$type] = ($typeIndex[$type] ?? 0) + 1;
            $position = $typeIndex[$type];
            $rows[] = [
                'module_key' => $type.':'.$position,
                'type' => $type,
                'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
                'sort_order' => is_numeric($module['sort_order'] ?? null) ? (int) $module['sort_order'] : $idx,
            ];
        }

        return array_values($rows);
    }

    /**
     * @return array<int,array{type:string,data:array<string,mixed>,sort_order:int}>
     */
    private function resumeModulesToArray(Resume $resume): array
    {
        $modules = $resume->relationLoaded('modules')
            ? $resume->modules
            : $resume->modules()->orderBy('sort_order')->get();

        return $modules->map(static fn (ResumeModule $module): array => [
            'type' => (string) $module->type,
            'data' => is_array($module->data) ? $module->data : [],
            'sort_order' => (int) $module->sort_order,
        ])->values()->all();
    }

    /**
     * @param  array<int,array<string,mixed>>  $modules
     */
    private function buildRawFromModulesArray(array $modules, string $fallbackRaw): string
    {
        if ($modules === []) {
            return $fallbackRaw;
        }

        return ResumeModuleSerializer::toRawText($modules);
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function safeLogInfo(string $message, array $context): void
    {
        try {
            Log::info($message, $context);
        } catch (Throwable) {
            // ignore logging failures in restricted environments
        }
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function safeLogWarning(string $message, array $context): void
    {
        try {
            Log::warning($message, $context);
        } catch (Throwable) {
            // ignore logging failures in restricted environments
        }
    }

    private function warnWhenSessionSlow(ResumeOptimizeSession $session, int $startedAtMs): void
    {
        $durationMs = max(0, (int) round(microtime(true) * 1000) - $startedAtMs);
        $threshold = max(1000, (int) config('resume.optimize_session.warning_threshold_ms', 45000));
        if ($durationMs <= $threshold) {
            return;
        }

        $this->safeLogWarning('resume_optimize_session_slow', [
            'session_id' => $session->id,
            'session_uuid' => $session->uuid,
            'resume_id' => $session->resume_id,
            'user_id' => $session->user_id,
            'duration_ms' => $durationMs,
            'threshold_ms' => $threshold,
            'status' => $session->status,
        ]);
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function emitOptimizeEvent(string $event, array $context): void
    {
        $payload = array_merge([
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
        ], $context);
        $this->safeLogInfo('resume_optimize_event', $payload);
    }
}
