<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\BatchDismissRecommendationsRequest;
use App\Http\Requests\User\DismissRecommendationRequest;
use App\Http\Requests\User\JobRecommendationGenerateRequest;
use App\Jobs\GenerateJobRecommendations;
use App\Models\JobRecommendation;
use App\Services\JobRecommendation\RecommendationInteractionRepository;
use App\Services\JobRecommendation\RecommendationQuotaService;
use App\Services\JobRecommendation\RecommendationStatsAction;
use App\Services\JobRecommendationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 智能岗位推荐控制器
 */
final class JobRecommendationController extends Controller
{
    public function __construct(
        private readonly JobRecommendationService $recommendationService,
        private readonly RecommendationQuotaService $quotaService,
        private readonly RecommendationStatsAction $statsAction,
        private readonly RecommendationInteractionRepository $interactionRepository,
    ) {}

    /**
     * 推荐列表页（支持按匹配分/时间/城市筛选）
     * #19 配额提示：今日剩余生成次数
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = JobRecommendation::where('user_id', $user->id)
            ->whereNotIn('status', [JobRecommendation::STATUS_DISMISSED])
            // #1 预加载 externalRecruitment，避免卡片渲染时 N+1 查询
            ->with('externalRecruitment');

        // 城市筛选
        if ($city = $request->string('city')->toString()) {
            $query->where('city', $city);
        }

        // #24 最低匹配分筛选
        if ($minScore = (int) $request->integer('min_score')) {
            $query->where('match_score', '>=', $minScore);
        }

        // #24 仅看收藏
        if ($request->boolean('favorited')) {
            $query->whereNotNull('favorited_at');
        }

        // #24 状态筛选（默认排除 dismissed，可显式查 applied）
        if ($status = $request->string('status')->toString()) {
            if (in_array($status, ['new', 'applied', 'viewed'], true)) {
                $query->where('status', $status);
            }
        }

        // #28 看相似：按 company 过滤，并排除当前推荐
        if ($similarTo = (int) $request->integer('similar_to')) {
            $current = JobRecommendation::find($similarTo);
            if ($current && $current->user_id === $user->id) {
                $query->where('company', $current->company)
                      ->where('id', '!=', $similarTo);
            }
        }

        // 排序：默认按匹配分降序
        $sort = $request->string('sort', 'score')->toString();
        match ($sort) {
            'time' => $query->latest(),
            'score' => $query->orderByDesc('match_score'),
            default => $query->orderByDesc('match_score'),
        };

        $recommendations = $query->paginate(15)->withQueryString();

        // #11 统计数据：委托 RecommendationStatsAction（合并 4 次 COUNT 为 1 次条件聚合）
        $stats = $this->statsAction->forUser($user->id);

        // #13 可选城市列表：5 分钟缓存，生成新推荐时通过 Job 主动 forget
        $citiesCacheKey = 'job_rec_cities:'.$user->id;
        $cities = \Illuminate\Support\Facades\Cache::remember($citiesCacheKey, 300, function () use ($user) {
            return JobRecommendation::where('user_id', $user->id)
                ->whereNotNull('city')
                ->whereNotIn('status', [JobRecommendation::STATUS_DISMISSED])
                ->distinct()
                ->pluck('city')
                ->filter()
                ->sort()
                ->values();
        });

        // #3 配额提示：今日剩余生成次数（原子操作读取）
        $remainingQuota = $this->quotaService->remainingToday($user->id);
        $dailyQuota = $this->quotaService->dailyQuota();

        return view('user.job-recommendations.index', compact(
            'recommendations',
            'stats',
            'cities',
            'remainingQuota',
            'dailyQuota'
        ));
    }

    /**
     * 触发推荐生成（队列异步）
     * #3 配额检查：原子操作 tryConsume，避免并发计数丢失
     */
    public function generate(JobRecommendationGenerateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // #3 原子扣减配额
        if (! $this->quotaService->tryConsume($user->id)) {
            $dailyQuota = $this->quotaService->dailyQuota();

            return redirect()
                ->route('user.recommendations.index')
                ->with('error', "今日生成次数已达上限（{$dailyQuota} 次），请明日再试");
        }

        // 检查是否有简历
        $resumeId = $request->input('resume_id');
        $resume = $resumeId
            ? $user->resumes()->find($resumeId)
            : $user->resumes()->latest('updated_at')->first();

        if ($resume === null) {
            // 无简历：归还配额
            $this->quotaService->release($user->id);

            return redirect()
                ->route('user.recommendations.index')
                ->with('error', '请先创建简历后再生成推荐');
        }

        // 标记进度为排队中
        $this->recommendationService->setProgress($user->id, 'queued', 0, 0);

        // 分发队列任务
        GenerateJobRecommendations::dispatch($user->id, $resume->id);

        return redirect()
            ->route('user.recommendations.progress')
            ->with('success', '推荐生成任务已提交，请稍候查看');
    }

    /**
     * 轮询生成进度
     */
    public function progress(Request $request): JsonResponse|View
    {
        $progress = $this->recommendationService->getProgress($request->user()->id);

        if ($request->expectsJson()) {
            return response()->json($progress);
        }

        return view('user.job-recommendations.generate', compact('progress'));
    }

    /**
     * #15 标记推荐为已查看
     *
     * 用户点击查看详情时调用，写入 viewed_at 时间戳
     */
    public function markViewed(Request $request, JobRecommendation $recommendation): JsonResponse
    {
        $this->authorizeRecommendation($request->user()->id, $recommendation);

        if ($recommendation->viewed_at === null) {
            $recommendation->update(['viewed_at' => now()]);

            // #25 数据埋点
            $this->interactionRepository->record($request->user()->id, $recommendation->id, 'view');
        }

        return response()->json(['success' => true]);
    }

    /**
     * 标记已投递
     */
    public function markApplied(Request $request, JobRecommendation $recommendation): JsonResponse
    {
        $this->authorizeRecommendation($request->user()->id, $recommendation);

        $recommendation->update(['status' => JobRecommendation::STATUS_APPLIED]);

        // #25 数据埋点
        $this->interactionRepository->record($request->user()->id, $recommendation->id, 'applied');

        return response()->json(['success' => true, 'message' => '已标记为已投递']);
    }

    /**
     * 忽略单条推荐
     * #18 reason 经 FormRequest 枚举校验
     */
    public function dismiss(DismissRecommendationRequest $request, JobRecommendation $recommendation): JsonResponse
    {
        $this->authorizeRecommendation($request->user()->id, $recommendation);

        $recommendation->update([
            'status' => JobRecommendation::STATUS_DISMISSED,
            'dismissed_at' => now(),
        ]);

        // #25 数据埋点
        $this->interactionRepository->record(
            $request->user()->id,
            $recommendation->id,
            'dismiss',
            $request->input('reason')
        );

        return response()->json(['success' => true, 'message' => '已忽略']);
    }

    /**
     * #19 切换收藏状态
     */
    public function toggleFavorite(Request $request, JobRecommendation $recommendation): JsonResponse
    {
        $this->authorizeRecommendation($request->user()->id, $recommendation);

        $isFavorited = $recommendation->favorited_at !== null;
        $recommendation->update([
            'favorited_at' => $isFavorited ? null : now(),
        ]);

        return response()->json([
            'success' => true,
            'favorited' => ! $isFavorited,
            'message' => $isFavorited ? '已取消收藏' : '已收藏',
        ]);
    }

    /**
     * #20 推荐详情页：站内查看完整 match_reasons / skill_gaps / 外链
     */
    public function show(Request $request, JobRecommendation $recommendation): View
    {
        $this->authorizeRecommendation($request->user()->id, $recommendation);

        $recommendation->load('externalRecruitment');

        // #5 source_url 安全校验
        $rawUrl = $recommendation->externalRecruitment?->source_url;
        $safeUrl = '';
        if ($rawUrl) {
            $parsed = parse_url((string) $rawUrl);
            if (isset($parsed['scheme']) && in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
                $safeUrl = $rawUrl;
            }
        }

        return view('user.job-recommendations.show', [
            'recommendation' => $recommendation,
            'safeUrl' => $safeUrl,
        ]);
    }

    /**
     * 批量忽略
     * #11 使用 FormRequest；#12 归属校验在 SQL 层面执行（user_id 限定）
     */
    public function batchDismiss(BatchDismissRecommendationsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // #12 归属校验：在 SQL 中限制 user_id，自动过滤不属于当前用户的推荐
        $updated = JobRecommendation::where('user_id', $request->user()->id)
            ->whereIn('id', $validated['ids'])
            ->whereNotIn('status', [JobRecommendation::STATUS_DISMISSED])
            ->update([
                'status' => JobRecommendation::STATUS_DISMISSED,
                'dismissed_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "已忽略 {$updated} 条推荐",
            'count' => $updated,
        ]);
    }

    /**
     * 鉴权：确保推荐属于当前用户
     */
    private function authorizeRecommendation(int $userId, JobRecommendation $recommendation): void
    {
        if ($recommendation->user_id !== $userId) {
            abort(403, '无权操作此推荐');
        }
    }
}
