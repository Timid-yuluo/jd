<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\BuildsResumeRawText;
use App\Http\Controllers\User\Traits\HandlesResumeControllerLogging;
use App\Http\Controllers\User\Traits\InteractsWithAsyncResponses;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Models\Resume;
use App\Models\ResumeAtsScoreLog;
use App\Models\UsageLog;
use App\Services\Api\V1\ResumeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class ResumeAnalysisController extends Controller
{
    use BuildsResumeRawText;
    use HandlesResumeControllerLogging;
    use InteractsWithAsyncResponses;
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeService $resumeService,
    ) {}

    public function atsScoreRedirect(Resume $resume): RedirectResponse
    {
        return redirect()->route('user.resumes.editor', $resume);
    }

    public function atsScore(Request $request, Resume $resume): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $resume);

        try {
            $user = $request->user();
            $dailyLimit = (int) config('ats.daily_limit_per_resume', 3);

            $isFirstScore = !is_numeric($resume->ats_score) || (int) $resume->ats_score === 0;

            if (!$isFirstScore) {
                $todayCount = UsageLog::query()
                    ->where('user_id', $user->id)
                    ->where('scenario', 'resume_ats')
                    ->whereJsonContains('meta->resume_id', $resume->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->count();

                if ($todayCount >= $dailyLimit) {
                    $message = "该简历今日 ATS 评分已达 {$dailyLimit} 次上限，明天可继续使用。";

                    if ($this->expectsAsyncResponse($request)) {
                        return $this->fail($message, 429);
                    }

                    return redirect()->back()->with('warning', $message);
                }
            }

            $contentRaw = null;
            if ($request->isJson()) {
                $modules = $request->input('modules');
                if (is_array($modules) && ! empty($modules)) {
                    $contentRaw = $this->buildRawFromModulesArray($modules);
                }
            }

            $result = $this->resumeService->atsScore($resume, $contentRaw);
            $resume->refresh();

            // 延迟记录 ATS 评分历史，避免阻塞主请求
            register_shutdown_function(function () use ($resume, $user, $result): void {
                try {
                    ResumeAtsScoreLog::create([
                        'resume_id' => $resume->id,
                        'user_id' => $user->id,
                        'score' => $resume->ats_score ?? 0,
                        'level' => $result['level'] ?? null,
                        'summary' => array_filter([
                            'summary' => $result['summary'] ?? null,
                            'highlights' => $result['highlights'] ?? null,
                        ]),
                    ]);
                } catch (\Throwable) {
                    // 静默失败，不影响主流程
                }
            });

            $this->markQuotaConsumptionSuccess($request);
            if ($this->expectsAsyncResponse($request)) {
                return $this->respondSuccessPayload([
                    'message' => 'ATS 评分已完成。',
                    'ats_score' => $resume->ats_score,
                    'level' => $result['level'] ?? '',
                    'summary' => $result['summary'] ?? '',
                ]);
            }

            return redirect()->back()->with('success', 'ATS 评分已完成。');
        } catch (\Exception $e) {
            $this->logUserFacingException('resume_ats_score_failed', $e, [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
            ]);

            if ($this->expectsAsyncResponse($request)) {
                return $this->fail('ATS 评分暂时不可用，请稍后重试。', 500);
            }

            return redirect()->back()->with('error', 'ATS 评分暂时不可用，请稍后重试。');
        }
    }

    public function atsReport(Request $request, Resume $resume): View
    {
        $this->authorize('view', $resume);

        $atsMeta = [];
        if (is_array($resume->content_structured) && isset($resume->content_structured['ats']) && is_array($resume->content_structured['ats'])) {
            $atsMeta = $resume->content_structured['ats'];
        }

        $suggestions = [];
        if (isset($atsMeta['suggestions']) && is_array($atsMeta['suggestions'])) {
            foreach ($atsMeta['suggestions'] as $suggestion) {
                if (is_array($suggestion) && isset($suggestion['text'])) {
                    $suggestions[] = [
                        'dimension' => (string) ($suggestion['dimension'] ?? ''),
                        'priority' => (string) ($suggestion['priority'] ?? 'medium'),
                        'text' => trim((string) $suggestion['text']),
                    ];
                } elseif (is_string($suggestion) && trim($suggestion) !== '') {
                    $suggestions[] = [
                        'dimension' => '',
                        'priority' => 'medium',
                        'text' => trim($suggestion),
                    ];
                }
            }
        }

        $breakdown = [];
        if (isset($atsMeta['breakdown']) && is_array($atsMeta['breakdown'])) {
            $breakdown = $atsMeta['breakdown'];
        }

        $moduleScores = [];
        if (isset($atsMeta['module_scores']) && is_array($atsMeta['module_scores'])) {
            $moduleScores = $atsMeta['module_scores'];
        }

        $history = [];
        if (isset($atsMeta['history']) && is_array($atsMeta['history'])) {
            $history = $atsMeta['history'];
        }

        $scoredAt = null;
        if (isset($atsMeta['scored_at']) && is_string($atsMeta['scored_at']) && trim($atsMeta['scored_at']) !== '') {
            try {
                $scoredAt = Carbon::parse($atsMeta['scored_at']);
            } catch (\Throwable) {
                $scoredAt = null;
            }
        }

        $isOutdated = $scoredAt !== null && $resume->updated_at->gt($scoredAt);
        $scoredTargetJob = isset($atsMeta['target_job']) && is_string($atsMeta['target_job']) ? $atsMeta['target_job'] : null;

        $percentile = null;
        $score = is_numeric($resume->ats_score) ? (int) $resume->ats_score : null;
        if ($score !== null) {
            $cached = \Illuminate\Support\Facades\Cache::get('ats_percentile_data');
            if ($cached === null) {
                // 缓存不存在时全量计算并缓存
                $cached = \Illuminate\Support\Facades\Cache::remember('ats_percentile_data', 21600, function () {
                    $total = Resume::query()->whereNotNull('ats_score')->count();
                    $distribution = Resume::query()
                        ->whereNotNull('ats_score')
                        ->selectRaw('ats_score, COUNT(*) as cnt')
                        ->groupBy('ats_score')
                        ->pluck('cnt', 'ats_score')
                        ->all();
                    return ['total' => $total, 'distribution' => $distribution];
                });
            }
            if (($cached['total'] ?? 0) > 0) {
                $below = 0;
                foreach ($cached['distribution'] as $s => $cnt) {
                    if ((int) $s < $score) $below += $cnt;
                }
                $percentile = (int) round(($below / $cached['total']) * 100);
            }
        }

        // P1-4: 评分前后对比 — 获取上次评分数据
        $previousScore = null;
        $previousBreakdown = null;
        $historyCount = count($history);
        if ($historyCount >= 2) {
            $prev = $history[$historyCount - 2];
            $previousScore = $prev['score'] ?? null;
            // 从 history 中无法获取上次 breakdown，需从 content_structured 中获取
        }
        $previousAts = null;
        if (is_array($resume->content_structured) && isset($resume->content_structured['ats']['history'])) {
            $allHistory = $resume->content_structured['ats']['history'];
            $histCount = count($allHistory);
            if ($histCount >= 2) {
                $previousAts = $allHistory[$histCount - 2];
            }
        }

        // P1-6: 岗位类别基准分
        $jobBenchmark = null;
        if ($score !== null && !empty($resume->target_job)) {
            $jobBenchmark = $this->getJobBenchmark($resume->target_job);
        }

        $user = $request->user();
        $dailyLimit = (int) config('ats.daily_limit_per_resume', 3);
        $isFirstScore = $score === null || $score === 0;
        $todayUsed = 0;
        $dailyRemaining = $dailyLimit;

        if (!$isFirstScore) {
            $todayUsed = UsageLog::query()
                ->where('user_id', $user->id)
                ->where('scenario', 'resume_ats')
                ->whereJsonContains('meta->resume_id', $resume->id)
                ->whereDate('created_at', now()->toDateString())
                ->count();
            $dailyRemaining = max(0, $dailyLimit - $todayUsed);
        }

        return view('user.resumes.ats-report', compact('resume', 'suggestions', 'breakdown', 'moduleScores', 'history', 'scoredAt', 'isOutdated', 'scoredTargetJob', 'percentile', 'dailyLimit', 'dailyRemaining', 'todayUsed', 'isFirstScore', 'previousAts', 'jobBenchmark'));
    }

    /**
     * P1-6: 获取岗位类别基准分（基于缓存的全站统计）
     */
    private function getJobBenchmark(string $targetJob): ?array
    {
        $category = $this->categorizeJob($targetJob);
        if ($category === null) {
            return null;
        }

        $cacheKey = 'ats_benchmark_' . md5($category);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 21600, function () use ($category): ?array {
            $keywords = $this->getCategoryKeywords($category);
            if (empty($keywords)) {
                return null;
            }

            $query = Resume::query()->whereNotNull('ats_score');
            $query->where(function ($q) use ($keywords): void {
                foreach ($keywords as $kw) {
                    $q->orWhere('target_job', 'like', "%{$kw}%");
                }
            });

            $stats = $query->selectRaw('COUNT(*) as total, AVG(ats_score) as avg_score, MIN(ats_score) as min_score, MAX(ats_score) as max_score')->first();

            if ($stats === null || (int) $stats->total < 3) {
                return null;
            }

            return [
                'category' => $category,
                'total' => (int) $stats->total,
                'avg_score' => (int) round((float) $stats->avg_score),
                'min_score' => (int) $stats->min_score,
                'max_score' => (int) $stats->max_score,
            ];
        });
    }

    /**
     * 根据目标岗位推断类别（覆盖各行各业）
     */
    private function categorizeJob(string $targetJob): ?string
    {
        $job = mb_strtolower($targetJob);
        $categories = config('jd_keywords.job_categories', []);

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($job, $kw)) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * 获取类别对应的关键词（用于查询同类简历）
     */
    private function getCategoryKeywords(string $category): array
    {
        $map = config('jd_keywords.category_keywords', []);

        return $map[$category] ?? [];
    }
}
