<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Application\Actions\JobMatching\AnalyzeJobMatchAction;
use App\Exceptions\JobMatchingAnalysisException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\InteractsWithAsyncResponses;
use App\Jobs\ProcessBatchJobMatch;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Http\Requests\User\AnalyzeJobMatchRequest;
use App\Models\JobMatchBatch;
use App\Models\JobBookmark;
use App\Models\ExternalRecruitment;
use Illuminate\Support\Facades\DB;
use App\Models\JobMatchAnalysis;
use App\Models\Resume;
use App\Models\UserCredit;
use App\Services\ExternalRecruitmentFormatterService;
use App\Services\JobMatching\JobMatchAuditLogger;
use App\Services\Membership\QuotaService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class JobMatchingController extends Controller
{
    use InteractsWithAsyncResponses;
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly AnalyzeJobMatchAction $analyzeJobMatchAction,
        private readonly ExternalRecruitmentFormatterService $externalRecruitmentFormatter,
        private readonly JobMatchAuditLogger $jobMatchAuditLogger,
        private readonly QuotaService $quotaService,
    ) {}

    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $resumes = $request->user()->resumes()
            ->select(['id', 'title', 'target_job', 'updated_at'])
            ->withCount('modules')
            ->latest()
            ->get();

        $quotaCacheKey = "job_match_page:{$userId}";
        $cached = \Illuminate\Support\Facades\Cache::remember($quotaCacheKey, 60, function () use ($request) {
            $user = $request->user();
            $quotaCheck = $this->quotaService->check($user, 'job_match');
            $autoCreditCheck = $quotaCheck['allowed']
                ? null
                : $this->quotaService->checkWithAutoCredit($user, 'job_match');
            $availableCredits = UserCredit::getAvailableCredits($user->id, 'job_match');
            $userPlan = $user->activeSubscription?->plan;
            $creditsHint = $this->buildCreditsHint($quotaCheck, $availableCredits, $userPlan);
            return [
                'quotaCheck' => $quotaCheck,
                'autoCreditCheck' => $autoCreditCheck,
                'availableCreditIds' => $availableCredits->pluck('id')->toArray(),
                'availableCreditsCount' => $availableCredits->count(),
                'userPlanSlug' => $userPlan?->slug,
                'userPlanName' => $userPlan?->name,
                'creditsHint' => $creditsHint,
            ];
        });

        $quotaCheck = $cached['quotaCheck'];
        $autoCreditCheck = $cached['autoCreditCheck'];
        $availableCreditIds = $cached['availableCreditIds'] ?? [];
        $availableCredits = !empty($availableCreditIds)
            ? UserCredit::whereIn('id', $availableCreditIds)->where('remaining', '>', 0)->get()
            : collect();
        $userPlan = $cached['userPlanSlug'] ? \App\Models\Plan::findBySlug($cached['userPlanSlug']) : null;
        $creditsHint = $cached['creditsHint'];
        $autoAnalyze = $request->boolean('auto_analyze', false);
        $historyQuery = JobMatchAnalysis::query()
            ->where('user_id', $request->user()->id)
            ->with('resume:id,title');
        $historySearch = (string) $request->input('history_search', '');
        if ($historySearch !== '') {
            $historyQuery->where(function ($q) use ($historySearch) {
                $q->where('job_description', 'like', '%'.$historySearch.'%')
                  ->orWhere('summary', 'like', '%'.$historySearch.'%')
                  ->orWhereHas('resume', function ($rq) use ($historySearch) {
                      $rq->where('title', 'like', '%'.$historySearch.'%');
                  });
            });
        }
        $recentAnalyses = $historyQuery
            ->latest()
            ->limit((int) config('ui.limit.related_items', 10))
            ->get();
        $prefillHistory = null;
        $prefillExternal = null;
        $prefillHistoryId = (int) $request->integer('from_history', 0);
        if ($prefillHistoryId > 0) {
            $prefillHistory = JobMatchAnalysis::query()
                ->where('user_id', $request->user()->id)
                ->find($prefillHistoryId);
        }
        $prefillExternalId = (int) $request->integer('from_external', 0);
        if ($prefillExternalId > 0) {
            $prefillExternal = ExternalRecruitment::query()
                ->where('review_status', ExternalRecruitment::REVIEW_APPROVED)
                ->find($prefillExternalId);
        }
        $prefillJobDescription = old(
            'job_description',
            (string) ($prefillHistory?->job_description ?: $this->externalRecruitmentFormatter->buildPrefillJobDescription($prefillExternal))
        );

        $prefillBookmarkId = (int) $request->integer('from_bookmark', 0);
        if ($prefillBookmarkId > 0 && $prefillJobDescription === '') {
            $bookmark = JobBookmark::query()
                ->where('user_id', $request->user()->id)
                ->find($prefillBookmarkId);
            if ($bookmark) {
                $prefillJobDescription = (string) $bookmark->job_description;
            }
        }

        return view('user.jobs.index', compact(
            'resumes',
            'quotaCheck',
            'autoCreditCheck',
            'availableCredits',
            'userPlan',
            'creditsHint',
            'recentAnalyses',
            'prefillHistory',
            'prefillExternal',
            'prefillJobDescription',
            'autoAnalyze',
            'historySearch'
        ));
    }

    public function analyze(AnalyzeJobMatchRequest $request): View|RedirectResponse|JsonResponse
    {
        $request->validated();
        $user = $request->user();
        $expectsJson = $this->expectsAsyncResponse($request);

        try {
            $analysis = $this->analyzeJobMatchAction->execute(
                $user,
                $request->jobDescription(),
                $request->selectedResumeId(),
            );
        } catch (DomainException $exception) {
            return $this->respondNoResumeAvailable($request, $expectsJson);
        } catch (JobMatchingAnalysisException $exception) {
            $quotaContext = $this->resolveQuotaConsumptionContext($request);
            $this->jobMatchAuditLogger->logFailure($user, $request, $this->resolveResumeForAudit($request), [
                'failure_type' => $exception->failureType(),
                'retryable' => $exception->retryable(),
                'attempted_drivers' => $exception->attemptedDrivers(),
                'driver' => $exception->driver(),
                'model' => $exception->model(),
                'latency_ms' => $exception->latencyMs(),
                'quota_source' => $quotaContext['source'] ?? null,
                'credit_id' => $quotaContext['credit_id'] ?? null,
                'job_description_length' => $request->jobDescriptionLength(),
                'job_description_preview' => $request->jobDescriptionPreview(),
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return $this->respondAnalyzeFailure($request, $expectsJson);
        } catch (\Throwable $exception) {
            $quotaContext = $this->resolveQuotaConsumptionContext($request);
            $this->jobMatchAuditLogger->logFailure($user, $request, null, [
                'failure_type' => 'unexpected_error',
                'retryable' => false,
                'quota_source' => $quotaContext['source'] ?? null,
                'credit_id' => $quotaContext['credit_id'] ?? null,
                'job_description_length' => $request->jobDescriptionLength(),
                'job_description_preview' => $request->jobDescriptionPreview(),
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return $this->respondAnalyzeFailure($request, $expectsJson);
        }

        $this->markQuotaConsumptionSuccess($request);
        $quotaContext = $this->resolveQuotaConsumptionContext($request);
        $this->jobMatchAuditLogger->logSuccess($analysis['analysis_history'], $request, [
            'driver' => $analysis['audit']['driver'] ?? null,
            'model' => $analysis['audit']['model'] ?? null,
            'latency_ms' => $analysis['audit']['latency_ms'] ?? null,
            'attempted_drivers' => $analysis['audit']['attempted_drivers'] ?? [],
            'quota_source' => $quotaContext['source'] ?? null,
            'credit_id' => $quotaContext['credit_id'] ?? null,
            'job_description_length' => $request->jobDescriptionLength(),
            'job_description_preview' => $request->jobDescriptionPreview(),
            'match_score' => $analysis['result']['match_score'] ?? null,
        ]);

        if ($expectsJson) {
            return $this->respondSuccessPayload([
                'message' => '岗位分析已完成',
                'data' => [
                    'analysis_id' => $analysis['analysis_history']->id,
                    'redirect_url' => route('user.jobs.analyze.history', ['analysis' => $analysis['analysis_history']->id]),
                ],
            ]);
        }

        return view('user.jobs.analyze', [
            'resume' => $analysis['resume'],
            'jobDescription' => $analysis['job_description'],
            'result' => $analysis['result'],
            'analysisHistory' => $analysis['analysis_history'],
        ]);
    }

    public function showHistory(Request $request, int $analysis): View
    {
        $history = JobMatchAnalysis::query()
            ->where('user_id', $request->user()->id)
            ->with('resume')
            ->findOrFail($analysis);

        return view('user.jobs.analyze', [
            'resume' => $history->resume,
            'jobDescription' => (string) $history->job_description,
            'result' => is_array($history->result) ? $history->result : [],
            'analysisHistory' => $history,
        ]);
    }

    /**
     * 导出匹配报告为打印友好页面
     */
    public function exportPdf(Request $request, int $analysis): View
    {
        $history = JobMatchAnalysis::query()
            ->where('user_id', $request->user()->id)
            ->with('resume')
            ->findOrFail($analysis);

        return view('user.jobs.analyze-pdf', [
            'resume' => $history->resume,
            'jobDescription' => (string) $history->job_description,
            'result' => is_array($history->result) ? $history->result : [],
            'analysisHistory' => $history,
        ]);
    }

    public function history(Request $request): View
    {
        $analyses = JobMatchAnalysis::query()
            ->where('user_id', $request->user()->id)
            ->with('resume:id,title')
            ->latest()
            ->paginate((int) config('ui.pagination.admin_table', 20));

        return view('user.jobs.history', compact('analyses'));
    }

    public function destroyHistory(Request $request, int $analysis): RedirectResponse
    {
        $history = JobMatchAnalysis::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($analysis);

        $this->jobMatchAuditLogger->logDeletion($history, $request, [
            'job_description_length' => mb_strlen((string) $history->job_description),
            'match_score' => $history->match_score,
        ]);

        $history->delete();

        return back()->with('success', '历史岗位匹配分析记录已删除。');
    }

    /**
     * 批量删除历史岗位匹配分析记录
     */
    public function batchDestroyHistory(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['ok' => false, 'message' => '请选择要删除的记录'], 422);
        }

        // 限制单次批量删除上限，防止恶意传入大量ID
        if (count($ids) > 100) {
            return response()->json(['ok' => false, 'message' => '单次最多删除100条记录'], 422);
        }

        $deleted = JobMatchAnalysis::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->delete();

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    public function batch(Request $request): View
    {
        $resumes = $request->user()->resumes()->select(['id', 'title'])->latest()->get();
        $recentBatches = JobMatchBatch::query()
            ->where('user_id', $request->user()->id)
            ->with('resume:id,title')
            ->latest()
            ->limit(5)
            ->get();
        $quotaCheck = $this->quotaService->check($request->user(), 'job_match');
        $availableCredits = UserCredit::getAvailableCredits($request->user()->id, 'job_match');
        $userPlan = $request->user()->activeSubscription?->plan;
        $creditsHint = $this->buildCreditsHint($quotaCheck, $availableCredits, $userPlan);

        return view('user.jobs.batch', compact(
            'resumes', 'recentBatches', 'quotaCheck', 'availableCredits', 'userPlan', 'creditsHint'
        ));
    }

    public function batchProgress(Request $request, JobMatchBatch $batch): JsonResponse
    {
        if ($batch->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => '无权访问。'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $batch->status,
                'total' => $batch->total,
                'completed' => $batch->completed,
                'failed' => $batch->failed ?? 0,
                'progress_percent' => $batch->total > 0 ? (int) round(($batch->completed / $batch->total) * 100) : 0,
            ],
        ]);
    }

    public function batchSubmit(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'resume_id' => ['nullable', 'integer', 'exists:resumes,id'],
            'job_descriptions' => ['required', 'string', 'max:50000'],
        ]);

        $user = $request->user();
        $lines = array_values(array_filter(array_map('trim', explode("\n---\n", $validated['job_descriptions'])), function ($l) {
            return mb_strlen($l) >= 50;
        }));

        if (empty($lines)) {
            return $this->expectsAsyncResponse($request)
                ? $this->fail('请至少输入一段不少于 50 字的岗位描述', 422)
                : redirect()->back()->withInput()->with('error', '请至少输入一段不少于 50 字的岗位描述');
        }

        $maxBatch = (int) config('job-matching.batch_max_items', 10);
        if (count($lines) > $maxBatch) {
            $msg = "批量分析最多 {$maxBatch} 段，请减少后重试。";
            return $this->expectsAsyncResponse($request)
                ? $this->fail($msg, 422)
                : redirect()->back()->withInput()->with('error', $msg);
        }

        $batch = DB::transaction(function () use ($user, $validated, $lines) {
            return JobMatchBatch::create([
                'user_id' => $user->id,
                'resume_id' => $validated['resume_id'] ?? null,
                'total' => count($lines),
                'status' => 'processing',
            ]);
        });

        ProcessBatchJobMatch::dispatch($batch->id, $user->id, $lines, $validated['resume_id'] ?? null);

        if ($this->expectsAsyncResponse($request)) {
            return $this->respondSuccessPayload([
                'message' => '批量分析已提交，正在后台处理。',
                'data' => [
                    'batch_id' => $batch->id,
                    'redirect_url' => route('user.jobs.batch'),
                ],
            ]);
        }

        return redirect()->route('user.jobs.batch')->with('success', '批量分析已提交，共 ' . count($lines) . ' 条，正在后台处理。');
    }

    private function buildCreditsHint(array $quotaCheck, $credits, $plan): string
    {
        $freeLeft = (int) ($quotaCheck['remaining'] ?? 0);
        $monthlyLimit = (int) ($quotaCheck['monthly_limit'] ?? 0);
        $parts = [];
        if ($monthlyLimit > 0) {
            $parts[] = "本月免费：{$freeLeft}/{$monthlyLimit} 次";
        }
        $creditsCount = is_countable($credits) ? count($credits) : 0;
        if ($creditsCount > 0) {
            $parts[] = '可用次卡：' . $creditsCount . ' 张';
        }
        if ($plan) {
            $parts[] = "当前套餐：{$plan->name}";
        } elseif (empty($credits) && $freeLeft <= 0) {
            $parts[] = '建议购买次卡或升级套餐';
        }

        return implode(' · ', $parts);
    }

    public function bookmarks(Request $request): View
    {
        $bookmarks = JobBookmark::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) config('ui.pagination.user_list', 20));

        return view('user.jobs.bookmarks', compact('bookmarks'));
    }

    public function storeBookmark(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'company' => ['nullable', 'string', 'max:200'],
            'job_description' => ['required', 'string', 'min:10', 'max:50000'],
            'external_recruitment_id' => ['nullable', 'integer'],
        ]);

        if (!empty($validated['external_recruitment_id'])) {
            $exists = ExternalRecruitment::query()
                ->where('id', $validated['external_recruitment_id'])
                ->where('review_status', ExternalRecruitment::REVIEW_APPROVED)
                ->exists();
            if (!$exists) {
                unset($validated['external_recruitment_id']);
            }
        }

        $bookmark = JobBookmark::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?? '',
            'company' => $validated['company'] ?? '',
            'job_description' => $validated['job_description'],
            'external_recruitment_id' => $validated['external_recruitment_id'] ?? null,
        ]);

        if ($this->expectsAsyncResponse($request)) {
            return $this->respondSuccessPayload([
                'message' => '岗位已收藏',
                'data' => ['bookmark_id' => $bookmark->id],
            ]);
        }

        return redirect()->route('user.jobs.bookmarks')->with('success', '岗位已收藏');
    }

    public function destroyBookmark(Request $request, int $bookmark): RedirectResponse
    {
        JobBookmark::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($bookmark)
            ->delete();

        return back()->with('success', '已取消收藏');
    }

    private function respondNoResumeAvailable(Request $request, bool $expectsJson): RedirectResponse|JsonResponse
    {
        if ($expectsJson) {
            return $this->fail('请先创建一份简历', 422);
        }

        return redirect()->back()->with('error', '请先创建一份简历');
    }

    private function respondAnalyzeFailure(Request $request, bool $expectsJson): RedirectResponse|JsonResponse
    {
        if ($expectsJson) {
            return $this->fail('分析失败，请稍后重试。若持续失败，请检查AI服务配置。', 500);
        }

        return redirect()
            ->route('user.jobs.analyze')
            ->withInput()
            ->with('error', '分析失败，请稍后重试。若持续失败，请检查AI服务配置。');
    }

    private function resolveResumeForAudit(AnalyzeJobMatchRequest $request): ?Resume
    {
        $resumeId = $request->selectedResumeId();
        if ($resumeId === null) {
            return null;
        }

        return $request->user()->resumes()->find($resumeId);
    }
}
