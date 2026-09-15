<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\HandlesInterviewReport;
use App\Http\Controllers\User\Traits\HandlesInterviewSession;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Http\Requests\User\InterviewStoreRequest;
use App\Http\ViewModels\User\InterviewCreateViewModel;
use App\Jobs\EvaluateInterviewAnswerJob;
use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Models\Resume;
use App\Models\User;
use App\Services\Interview\InterviewAnswerEvaluationService;
use App\Services\Interview\InterviewAnswerSubmissionService;
use App\Services\Interview\InterviewQuestionGeneratorService;
use App\Services\Interview\InterviewReportService;
use App\Services\Interview\InterviewSessionService;
use App\Services\Interview\StartInterviewService;
use App\Models\JobBookmark;
use App\Models\JobMatchAnalysis;
use App\Services\Membership\QuotaService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class InterviewController extends Controller
{
    use HandlesInterviewReport;
    use HandlesInterviewSession;
    use RespondsWithJsonSuccess;

    private const REPORT_VERSION = 3;

    public function __construct(
        private readonly StartInterviewService $startInterviewService,
        private readonly InterviewQuestionGeneratorService $interviewQuestionGeneratorService,
        private readonly InterviewAnswerEvaluationService $interviewAnswerEvaluationService,
        private readonly InterviewAnswerSubmissionService $interviewAnswerSubmissionService,
        private readonly InterviewSessionService $interviewSessionService,
        private readonly InterviewReportService $interviewReportService,
        private readonly QuotaService $quotaService,
    ) {}

    protected function submissionService(): InterviewAnswerSubmissionService
    {
        return $this->interviewAnswerSubmissionService;
    }

    protected function interviewSessionService(): InterviewSessionService
    {
        return $this->interviewSessionService;
    }

    protected function interviewReportService(): InterviewReportService
    {
        return $this->interviewReportService;
    }

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->string('status', ''),
            'type' => (string) $request->string('type', ''),
            'jd_mode' => (string) $request->string('jd_mode', ''),
            'candidate_profile' => (string) $request->string('candidate_profile', ''),
        ];

        $query = InterviewSession::where('user_id', $request->user()->id)
            ->with('resume')
            ->orderByDesc('created_at');

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        if ($filters['candidate_profile'] !== '') {
            $query->where('candidate_profile', $filters['candidate_profile']);
        }

        if ($filters['jd_mode'] === 'with_jd') {
            $query->whereNotNull('job_description')
                ->where('job_description', '!=', '');
        }
        if ($filters['jd_mode'] === 'without_jd') {
            $query->where(static function (Builder $builder): void {
                $builder->whereNull('job_description')
                    ->orWhere('job_description', '');
            });
        }

        $interviews = $query->paginate((int) config('ui.pagination.user_list', 10))->appends($filters);
        $interviewPlanSummary = $this->resolveInterviewPlanSummary($request->user());

        return view('user.interviews.index', compact('interviews', 'filters', 'interviewPlanSummary'));
    }

    public function jdSources(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookmarks = JobBookmark::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get(['id', 'title', 'company', 'job_description'])
            ->map(fn (JobBookmark $bm): array => [
                'id' => $bm->id,
                'title' => $bm->title ?: '未命名',
                'company' => $bm->company,
                'summary' => Str::limit($bm->job_description, 60),
                'source' => 'bookmark',
            ]);

        $histories = JobMatchAnalysis::query()
            ->where('user_id', $user->id)
            ->whereNotNull('job_description')
            ->where('job_description', '!=', '')
            ->latest()
            ->limit(10)
            ->get(['id', 'job_description', 'match_score', 'created_at'])
            ->map(fn (JobMatchAnalysis $h): array => [
                'id' => $h->id,
                'title' => '#' . $h->id . ' · ' . $h->created_at?->format('m-d H:i'),
                'company' => null,
                'match_score' => $h->match_score,
                'summary' => Str::limit($h->job_description, 60),
                'source' => 'history',
            ]);

        return response()->json([
            'bookmarks' => $bookmarks,
            'histories' => $histories,
        ]);
    }

    public function jdContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'in:bookmark,history'],
            'id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        if ($validated['source'] === 'bookmark') {
            $bm = JobBookmark::query()
                ->where('user_id', $user->id)
                ->where('id', $validated['id'])
                ->first(['id', 'job_description', 'company']);

            if ($bm === null) {
                return response()->json(['error' => '未找到'], 404);
            }

            return response()->json([
                'job_description' => $bm->job_description,
                'company' => $bm->company,
            ]);
        }

        $h = JobMatchAnalysis::query()
            ->where('user_id', $user->id)
            ->where('id', $validated['id'])
            ->first(['id', 'job_description']);

        if ($h === null) {
            return response()->json(['error' => '未找到'], 404);
        }

        return response()->json([
            'job_description' => $h->job_description,
            'company' => null,
        ]);
    }

    public function keywords(string $type): JsonResponse
    {
        $allKeywords = config('interview.tech_keywords', []);

        if ($type === 'all') {
            $merged = [];
            foreach ($allKeywords as $group) {
                foreach ($group as $kw) {
                    $merged[$kw] = true;
                }
            }

            return response()->json(array_keys($merged));
        }

        $keywords = $allKeywords[$type] ?? $allKeywords['common'] ?? [];

        return response()->json($keywords);
    }

    public function resumeHighlights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resume_id' => ['required', 'integer'],
        ]);

        $resume = Resume::where('user_id', $request->user()->id)
            ->where('id', $validated['resume_id'])
            ->first(['id', 'highlights', 'target_job']);

        if ($resume === null) {
            return response()->json(['error' => '未找到'], 404);
        }

        return response()->json([
            'highlights' => $resume->highlights ?? [],
            'target_job' => $resume->target_job,
        ]);
    }

    public function create(Request $request): View
    {
        $resumes = Resume::where('user_id', $request->user()->id)
            ->select(['id', 'title', 'target_job', 'updated_at', 'ats_score'])
            ->withCount('modules')
            ->orderByDesc('created_at')
            ->get();
        $interviewPlanSummary = $this->resolveInterviewPlanSummary($request->user());
        $viewModel = new InterviewCreateViewModel($resumes, $interviewPlanSummary);

        return view('user.interviews.create', compact('viewModel'));
    }

    /**
     * @return array{
     *     customQuestionsEnabled: bool,
     *     interviewMaxQuestions: int,
     *     interviewSessionQuotaCheck: array<string,mixed>,
     *     interviewEvaluationQuotaCheck: array<string,mixed>,
     *     interviewSessionAutoCreditCheck: array<string,mixed>|null,
     *     interviewEvaluationAutoCreditCheck: array<string,mixed>|null,
     *     interviewSessionNotice: array<string,mixed>|null,
     *     interviewEvaluationNotice: array<string,mixed>|null
     * }
     */
    private function resolveInterviewPlanSummary(User $user): array
    {
        $cacheKey = "interview_plan_summary:{$user->id}";

        return Cache::remember($cacheKey, 120, function () use ($user): array {
            $plan = $user->currentPlan();
            $planQuotas = is_array($plan?->quotas ?? null) ? $plan->quotas : [];
            $interviewSessionQuotaCheck = $this->quotaService->check($user, 'interview_sessions');
            $interviewSessionAutoCreditCheck = (($interviewSessionQuotaCheck['allowed'] ?? true) === false)
                ? $this->quotaService->checkWithAutoCredit($user, 'interview_sessions')
                : null;

            return [
                'customQuestionsEnabled' => (bool) ($planQuotas['custom_questions'] ?? false),
                'interviewMaxQuestions' => max(1, (int) ($planQuotas['interview_sessions']['max_questions'] ?? config('interview.max_questions', 5))),
                'interviewSessionQuotaCheck' => $interviewSessionQuotaCheck,
                'interviewSessionAutoCreditCheck' => $interviewSessionAutoCreditCheck,
                'interviewSessionNotice' => $this->buildInterviewQuotaNotice(
                    'AI 面试场次',
                    'interview_sessions',
                    $interviewSessionQuotaCheck
                ),
            ];
        });
    }

    /**
     * @param  array<string,mixed>  $quotaCheck
     * @return array<string,mixed>|null
     */
    private function buildInterviewQuotaNotice(string $label, string $quotaKey, array $quotaCheck): ?array
    {
        if (($quotaCheck['allowed'] ?? true) === true) {
            return null;
        }

        $creditAvailable = ($quotaCheck['reason'] ?? null) === 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE';

        return [
            'quota_key' => $quotaKey,
            'credit_available' => $creditAvailable,
            'message' => $creditAvailable
                ? "{$label}本月免费次数已用完，但你仍可使用专用次卡或通用次卡继续。"
                : "{$label}本月免费次数已用完，请升级套餐或购买次卡后继续。",
        ];
    }

    /**
     * @return array{
     *     answeredCount:int,
     *     questionCount:int,
     *     sessionQuotaConsumed:int,
     *     evaluationQuotaConsumed:int,
     *     jdEnabled:bool
     * }
     */
    private function resolveInterviewUsageSummary(InterviewSession $interview): array
    {
        $answeredCount = $interview->questions()->whereNotNull('answer')->count();
        $questionCount = max((int) ($interview->question_count ?? 0), $interview->questions()->count());

        return [
            'answeredCount' => $answeredCount,
            'questionCount' => $questionCount,
            'sessionQuotaConsumed' => $questionCount > 0 ? 1 : 0,
            'jdEnabled' => trim((string) ($interview->job_description ?? '')) !== '',
        ];
    }

    public function store(InterviewStoreRequest $request): RedirectResponse
    {
        $interview = $this->startInterviewService->start($request->user()->id, $request->validated());

        // 练习模式不消耗额度
        if (! (bool) $request->input('is_practice', false)) {
            $this->markQuotaConsumptionSuccess($request);
            // 配额变更后清除面试计划摘要缓存
            Cache::forget("interview_plan_summary:{$request->user()->id}");
        }

        DashboardController::clearUserCache($request->user()->id);

        return redirect()->route('user.interviews.session', $interview)
            ->with('success', '面试已创建，请开始答题。');
    }

    public function show(Request $request, InterviewSession $interview): View
    {
        $this->authorize('view', $interview);
        $interview->load('resume', 'questions');
        $interviewPlanSummary = $this->resolveInterviewPlanSummary($request->user());
        $interviewUsageSummary = $this->resolveInterviewUsageSummary($interview);

        // 历史面试趋势数据（最近10场已完成的面试得分）
        $interviewTrend = InterviewSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', InterviewSession::STATUS_COMPLETED)
            ->whereNotNull('overall_score')
            ->select(['id', 'position', 'overall_score', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return view('user.interviews.show', compact('interview', 'interviewPlanSummary', 'interviewUsageSummary', 'interviewTrend'));
    }

    public function session(Request $request, InterviewSession $interview): View
    {
        $this->authorize('view', $interview);

        $timeoutMinutes = max(1, (int) config('interview.session_timeout_minutes', 30));
        $heartbeatKey = $this->heartbeatCacheKey($interview->id);
        $lastActiveAtRaw = Cache::get($heartbeatKey);
        $lastActiveAtTs = is_numeric($lastActiveAtRaw) ? (int) $lastActiveAtRaw : null;
        if ($interview->status === InterviewSession::STATUS_IN_PROGRESS && $lastActiveAtTs !== null && (time() - $lastActiveAtTs) > ($timeoutMinutes * 60)) {
            $interview->update(['status' => InterviewSession::STATUS_COMPLETED]);
            // 练习模式不生成评分报告
            if (! $interview->is_practice) {
                $this->persistInterviewReport($interview->fresh());
            }
            Log::info('interview_session_auto_completed_by_timeout', [
                'interview_id' => $interview->id,
                'user_id' => $request->user()->id,
                'timeout_minutes' => $timeoutMinutes,
            ]);
        }

        if ($interview->status === InterviewSession::STATUS_PENDING && $interview->questions()->exists()) {
            $interview->update(['status' => InterviewSession::STATUS_IN_PROGRESS]);
        }

        Cache::put($heartbeatKey, time(), now()->addMinutes($timeoutMinutes * 2));

        $interview->load(['questions' => fn ($q) => $q->orderBy('round_no')]);
        $interviewPlanSummary = $this->resolveInterviewPlanSummary($request->user());

        if ($interview->mode === 'voice') {
            return view('user.interviews.voice-session', compact('interview', 'interviewPlanSummary'));
        }

        return view('user.interviews.session', compact('interview', 'interviewPlanSummary'));
    }

    public function heartbeat(Request $request, InterviewSession $interview): JsonResponse
    {
        $this->authorize('update', $interview);
        if ($interview->status === InterviewSession::STATUS_COMPLETED) {
            return $this->fail('面试已结束。', 422);
        }

        $timeoutMinutes = max(1, (int) config('interview.session_timeout_minutes', 30));
        Cache::put($this->heartbeatCacheKey($interview->id), time(), now()->addMinutes($timeoutMinutes * 2));

        return $this->success();
    }

    public function pause(Request $request, InterviewSession $interview): JsonResponse
    {
        $this->authorize('update', $interview);
        if ($interview->status !== InterviewSession::STATUS_IN_PROGRESS) {
            return $this->fail('只有进行中的面试可以暂停。', 422);
        }

        $interview->update(['status' => InterviewSession::STATUS_PAUSED]);

        return $this->success(['message' => '面试已暂停。']);
    }

    public function resume(Request $request, InterviewSession $interview): JsonResponse
    {
        $this->authorize('update', $interview);
        if ($interview->status !== InterviewSession::STATUS_PAUSED) {
            return $this->fail('只有已暂停的面试可以恢复。', 422);
        }

        $interview->update(['status' => InterviewSession::STATUS_IN_PROGRESS]);

        $timeoutMinutes = max(1, (int) config('interview.session_timeout_minutes', 30));
        Cache::put($this->heartbeatCacheKey($interview->id), time(), now()->addMinutes($timeoutMinutes * 2));

        return $this->success(['message' => '面试已恢复。']);
    }

    public function submitAnswer(\App\Http\Requests\User\InterviewSubmitAnswerRequest $request, InterviewSession $interview): JsonResponse
    {
        $this->authorize('update', $interview);

        $validated = $request->validated();
        $answer = trim((string) $validated['answer']);
        $answerHash = hash('sha256', $answer);
        $lock = Cache::lock("interview:submit:{$interview->id}:{$validated['question_id']}", 10);

        if (! $lock->get()) {
            return $this->fail('提交过于频繁，请稍后再试', 429);
        }

        try {
            $question = InterviewQuestion::where('interview_session_id', $interview->id)
                ->where('id', $validated['question_id'])
                ->firstOrFail();

            if ($question->answer !== null) {
                $isSameAnswer = ($question->answer_hash !== null && hash_equals((string) $question->answer_hash, $answerHash))
                    || trim((string) $question->answer) === $answer;

                if ($isSameAnswer) {
                    return $this->buildSubmitAnswerResponse($interview->fresh(), $question->fresh(), true);
                }

                return response()->json(['error' => '此题已回答'], 422);
            }

            $result = $this->submissionService()->submit($interview, $question, $answer, $answerHash);

            if ($result['early_termination'] !== null) {
                return $this->finishInterviewByEarlyTermination(
                    $result['interview'],
                    $result['question'],
                    $result['early_termination']['reason'],
                    $result['early_termination']['consecutive']
                );
            }

            if ($result['should_dispatch_job']) {
                EvaluateInterviewAnswerJob::dispatch((int) $result['question']->id)
                    ->onQueue((string) config('interview.evaluation_queue', 'default'));
            }

            return $this->buildSubmitAnswerResponse(
                $result['interview'],
                $result['question'],
                false,
                $result['ai_dialogue_decision']
            );
        } finally {
            $lock->release();
        }
    }

    public function evaluationStatus(
        Request $request,
        InterviewSession $interview,
        InterviewAnswerEvaluationService $evaluationService
    ): JsonResponse {
        $this->authorize('update', $interview);

        $validated = $request->validate([
            'question_id' => 'required|integer',
        ]);

        /** @var InterviewQuestion $question */
        $question = InterviewQuestion::query()
            ->where('interview_session_id', $interview->id)
            ->where('id', (int) $validated['question_id'])
            ->firstOrFail();

        $isPending = $question->answer !== null && $question->score === null;
        if ($isPending) {
            $pendingTimeoutSeconds = max(30, (int) config('interview.evaluation_pending_timeout_seconds', 90));
            $pendingSeconds = $question->updated_at !== null
                ? max(0, now()->diffInSeconds($question->updated_at))
                : 0;

            if ($pendingSeconds >= $pendingTimeoutSeconds) {
                $fallback = $evaluationService->evaluate(
                    (string) $question->question,
                    (string) $question->answer,
                    (int) $question->interview_session_id,
                    (int) $question->id,
                    false,
                    [
                        'interview_type' => (string) $interview->type,
                        'dimension' => (string) $question->dimension,
                    ]
                );
                $fallbackFeedback = is_array($fallback['feedback'] ?? null) ? $fallback['feedback'] : [];
                $fallbackFluency = is_array($fallback['fluency'] ?? null) ? $fallback['fluency'] : [];
                $fallbackFluencyIssues = is_array($fallbackFluency['issues'] ?? null) ? $fallbackFluency['issues'] : [];
                if ($fallbackFluencyIssues !== []) {
                    $fallbackFeedback['fluency_issues'] = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $fallbackFluencyIssues)));
                    $fallbackFeedback['fluency_severity'] = (string) ($fallbackFluency['severity'] ?? 'medium');
                }
                $fallbackFeedback['fluency_confidence'] = max(0.0, min(1.0, (float) ($fallbackFluency['confidence'] ?? 0.0)));
                $fallbackFeedback['fluency_detected_by'] = (string) ($fallbackFluency['detected_by'] ?? 'rule');
                $fallbackDialogue = is_array($fallback['dialogue'] ?? null) ? $fallback['dialogue'] : [];
                $fallbackFeedback['dialogue_action'] = (string) ($fallbackDialogue['action'] ?? 'continue');
                $fallbackFeedback['dialogue_confidence'] = max(0.0, min(1.0, (float) ($fallbackDialogue['confidence'] ?? 0.0)));

                $question->forceFill([
                    'score' => (int) ($fallback['score'] ?? 6),
                    'feedback' => $fallbackFeedback !== [] ? $fallbackFeedback : [
                        'comment' => '评分超时，已启用快速评分。',
                        'suggestion' => '建议补充关键动作与量化结果。',
                    ],
                ])->save();

                $question->refresh();
                $isPending = false;
            }
        }

        return response()->json([
            'question_id' => (int) $question->id,
            'scoring_pending' => $isPending,
            'score' => $question->score !== null ? (int) $question->score : null,
            'feedback' => is_array($question->feedback) ? $question->feedback : [],
        ]);
    }

    public function finish(Request $request, InterviewSession $interview): RedirectResponse
    {
        $this->authorize('update', $interview);

        if ($interview->status !== InterviewSession::STATUS_COMPLETED) {
            $interview->update(['status' => InterviewSession::STATUS_COMPLETED]);
        }
        // 练习模式不生成评分报告
        if (! $interview->is_practice) {
            $this->persistInterviewReport($interview->fresh());
        }

        return redirect()->route('user.interviews.report', $interview);
    }

    public function report(Request $request, InterviewSession $interview): View
    {
        $this->authorize('view', $interview);
        $report = is_array($interview->report) ? $interview->report : [];
        $reportVersion = (int) ($report['report_version'] ?? 0);
        $needRebuild = empty($report)
            || ! array_key_exists('dimension_scores', $report)
            || $reportVersion < self::REPORT_VERSION;
        if ($interview->status === InterviewSession::STATUS_COMPLETED && $needRebuild && ! $interview->is_practice) {
            $interview = $this->persistInterviewReport($interview);
        }
        $interview->load(['resume', 'questions' => fn ($q) => $q->orderBy('round_no')]);
        $interviewPlanSummary = $this->resolveInterviewPlanSummary($request->user());
        $interviewUsageSummary = $this->resolveInterviewUsageSummary($interview);

        return view('user.interviews.report', compact('interview', 'interviewPlanSummary', 'interviewUsageSummary'));
    }

    public function reportPdf(Request $request, InterviewSession $interview): View
    {
        $this->authorize('view', $interview);
        $interview->load(['resume', 'questions' => fn ($q) => $q->orderBy('round_no')]);
        $report = is_array($interview->report) ? $interview->report : [];

        return view('user.interviews.report-pdf', compact('interview', 'report'));
    }

    public function destroy(Request $request, InterviewSession $interview): RedirectResponse
    {
        $this->authorize('delete', $interview);
        $interview->delete();

        return redirect()->route('user.interviews.index')
            ->with('success', '面试记录已删除。');
    }

    public function generateQrCode(Request $request, InterviewSession $interview): JsonResponse
    {
        $this->authorize('view', $interview);

        $qrToken = Str::random(32);
        $expiresAt = now()->addHours(24);

        $interview->update([
            'qr_token' => $qrToken,
            'qr_expires_at' => $expiresAt,
        ]);

        $qrUrl = route('user.interviews.voice-mobile', ['token' => $qrToken]);

        return $this->respondSuccessPayload([
            'qr_url' => $qrUrl,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function voiceMobile(Request $request): View|RedirectResponse
    {
        $token = $request->string('token');

        if (empty($token)) {
            $token = $request->session()->get('qr_token');
        }

        if (empty($token)) {
            return redirect()->route('user.interviews.index')
                ->with('error', '无效的面试链接。');
        }

        $interview = InterviewSession::where('qr_token', $token)
            ->where('qr_expires_at', '>', now())
            ->first();

        if (! $interview) {
            $request->session()->forget('qr_token');
            return redirect()->route('user.interviews.index')
                ->with('error', '面试链接已过期或无效。');
        }

        if ($interview->user_id !== $request->user()?->id) {
            return redirect()->route('login')
                ->with('error', '请先登录后再进行面试。');
        }

        if ($request->has('token') && !$request->session()->has('qr_token')) {
            $request->session()->put('qr_token', $token);
            return redirect()->route('user.interviews.voice-mobile');
        }

        $interview->load(['questions' => fn ($q) => $q->orderBy('round_no')]);

        if ($interview->status === InterviewSession::STATUS_COMPLETED) {
            return redirect()->route('user.interviews.report', $interview)
                ->with('info', '该面试已结束。');
        }

        if ($interview->status === InterviewSession::STATUS_PENDING) {
            $interview->update(['status' => InterviewSession::STATUS_IN_PROGRESS]);
        }

        $interviewPlanSummary = $this->resolveInterviewPlanSummary($request->user());

        return view('user.interviews.voice-session', compact('interview', 'interviewPlanSummary'));
    }

    public function compare(Request $request): View
    {
        $user = $request->user();
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) < 2) {
            abort(422, '请选择至少两个面试进行对比。');
        }

        $interviews = InterviewSession::where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->where('status', InterviewSession::STATUS_COMPLETED)
            ->with('resume')
            ->get();

        if ($interviews->count() < 2) {
            abort(422, '选择的面试中至少需要两个已完成的面试。');
        }

        return view('user.interviews.compare', compact('interviews'));
    }

    /**
     * 面试日历页面
     */
    public function calendar(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $interviews = JobApplication::where('user_id', $user->id)
            ->whereNotNull('interview_at')
            ->whereBetween('interview_at', [$startOfMonth, $endOfMonth])
            ->orderBy('interview_at')
            ->get();

        $interviewSessions = InterviewSession::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->orderBy('created_at')
            ->get();

        return view('user.interviews.calendar', compact('interviews', 'interviewSessions', 'month', 'startOfMonth', 'endOfMonth'));
    }
}
