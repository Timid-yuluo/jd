<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\ResumeTemplate;
use App\Models\TemplateSource;
use App\Models\UserActionLog;
use App\Services\Resume\Template\Source\TemplateSourceApiClient;
use App\Services\Resume\Template\Source\TemplateSourceNormalizer;
use App\Services\Resume\Template\Source\TemplateSourceSyncService;
use App\Services\Resume\Template\TemplateAnalyticsService;
use App\Services\Resume\Template\TemplateApplicationService;
use App\Services\Resume\Template\TemplatePresentationService;
use App\Services\Resume\Template\TemplateRecommendationService;
use App\Services\Resume\Template\TemplateUserDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

final class ResumeTemplateController extends Controller
{
    public function __construct(
        private readonly TemplatePresentationService $presentationService,
        private readonly TemplateUserDataService $userDataService,
        private readonly TemplateRecommendationService $recommendationService,
        private readonly TemplateApplicationService $applicationService,
        private readonly TemplateAnalyticsService $analyticsService,
    ) {}

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $level = trim((string) $request->query('level', ''));
        $sort = trim((string) $request->query('sort', 'featured'));
        $focus = trim((string) $request->query('focus', ''));
        $sourceSlug = trim((string) $request->query('source', ''));
        $defaultDiversify = (bool) config('resume.template.diversify_default_enabled', false);
        $diversify = (int) $request->query('diversify', $defaultDiversify ? 1 : 0) === 1;
        $maxPerPosition = (int) config('resume.template.diversify_max_per_position', 4);
        $maxPerPosition = max(1, min($maxPerPosition, 12));
        $focusStyleMap = $this->presentationService->focusStyleMap();

        $query = ResumeTemplate::query()->active();

        if ($keyword !== '') {
            $safeKeyword = escapeLike($keyword);
            $query->where(function ($builder) use ($safeKeyword): void {
                $builder
                    ->where('name', 'like', "%{$safeKeyword}%")
                    ->orWhere('position', 'like', "%{$safeKeyword}%")
                    ->orWhere('industry', 'like', "%{$safeKeyword}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($level !== '') {
            $query->where('level', $level);
        }

        if ($focus !== '' && isset($focusStyleMap[$focus])) {
            $query->whereIn('style', $focusStyleMap[$focus]);
        }

        if ($sourceSlug === 'builtin') {
            $query->whereNull('source_id');
        } elseif ($sourceSlug !== '') {
            $source = TemplateSource::query()->where('slug', $sourceSlug)->first();
            if ($source) {
                $query->where('source_id', $source->id);
            }
        }

        $rawTotal = (clone $query)->count();

        if ($sort === 'popular') {
            $query->orderByDesc('usage_count')->orderByDesc('id');
        } elseif ($sort === 'latest') {
            $query->orderByDesc('id');
        } else {
            $sort = 'featured';
            $query
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderBy('id');
        }

        $perPage = 24;
        if ($diversify) {
            $allTemplates = $query->get();
            $allTemplates = $this->presentationService->diversifyTemplates($allTemplates, $maxPerPosition);
            $page = max(1, (int) $request->query('page', 1));
            $total = $allTemplates->count();
            $items = $allTemplates->slice(($page - 1) * $perPage, $perPage)->values();
            $templates = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $templates = $query->paginate($perPage)->withQueryString();
        }
        $displayedTotal = $templates->total();
        $templates->getCollection()->transform(function (ResumeTemplate $template): ResumeTemplate {
            $template->setAttribute('diff_points', $this->presentationService->buildDifferentiationPoints($template, 2));
            $template->setAttribute('focus_label', $this->presentationService->focusLabelByStyle((string) $template->style));

            return $template;
        });
        $categories = ResumeTemplate::query()->active()->select('category')->distinct()->orderBy('category')->pluck('category');
        $levels = ResumeTemplate::query()->active()->select('level')->distinct()->orderBy('level')->pluck('level');
        $focusOptions = collect($focusStyleMap)->keys()->all();
        $resumeTargets = $this->userDataService->resumeTargets($request);
        $recentResumes = $this->userDataService->recentResumes($request, 6);
        $recentTemplates = $this->userDataService->recentTemplates($request, 6);
        $activeSources = TemplateSource::query()->active()->orderBy('sort_order')->get(['id', 'name', 'slug']);
        $this->trackTemplateAction($request, 'resume_template_index_view');

        return view('user.resume-templates.index', compact('templates', 'categories', 'levels', 'keyword', 'category', 'level', 'sort', 'diversify', 'focus', 'focusOptions', 'rawTotal', 'displayedTotal', 'resumeTargets', 'recentResumes', 'recentTemplates', 'activeSources', 'sourceSlug'));
    }

    public function show(Request $request, ResumeTemplate $resumeTemplate): View
    {
        abort_unless($resumeTemplate->is_active, 404);
        $focusLabel = $this->presentationService->focusLabelByStyle((string) $resumeTemplate->style);
        $resumeTargets = $this->userDataService->resumeTargets($request);
        $isExternal = $resumeTemplate->isExternal();
        $externalUrl = $isExternal ? $resumeTemplate->external_url : null;

        $fromTemplateId = (int) $request->query('from', 0);
        $scene = trim((string) $request->query('scene', ''));
        if ($scene === 'related' && $fromTemplateId > 0) {
            Log::info('resume_template.related_click', [
                'user_id' => optional($request->user())->id,
                'from_template_id' => $fromTemplateId,
                'to_template_id' => $resumeTemplate->id,
                'path' => $request->path(),
            ]);
        }
        $this->trackTemplateAction($request, 'resume_template_detail_view', [
            'template_id' => (int) $resumeTemplate->id,
            'template_slug' => (string) $resumeTemplate->slug,
            'scene' => $scene,
            'from_template_id' => $fromTemplateId > 0 ? $fromTemplateId : null,
        ]);

        $relatedTemplates = $this->recommendationService->buildRelatedTemplates($resumeTemplate);

        $previewModules = $this->presentationService->buildPreviewModules(
            is_array($resumeTemplate->module_blueprint) ? $resumeTemplate->module_blueprint : [],
            (string) $resumeTemplate->position
        );
        $previewResume = (object) [
            'title' => (string) $resumeTemplate->name,
            'target_job' => (string) $resumeTemplate->position,
            'highlights' => [],
        ];
        $compareTarget = null;
        $comparePreviewModules = [];
        $comparePreviewResume = null;
        $compareTemplateId = (int) $request->query('compare', 0);
        if ($compareTemplateId > 0) {
            $candidate = ResumeTemplate::query()
                ->active()
                ->where('id', $compareTemplateId)
                ->where('id', '!=', $resumeTemplate->id)
                ->where('position', $resumeTemplate->position)
                ->first();
            if ($candidate instanceof ResumeTemplate) {
                $compareTarget = $candidate;
                $comparePreviewModules = $this->presentationService->buildPreviewModules(
                    is_array($candidate->module_blueprint) ? $candidate->module_blueprint : [],
                    (string) $candidate->position
                );
                $comparePreviewResume = (object) [
                    'title' => (string) $candidate->name,
                    'target_job' => (string) $candidate->position,
                    'highlights' => [],
                ];
                $this->trackTemplateAction($request, 'resume_template_compare_view', [
                    'template_id' => (int) $resumeTemplate->id,
                    'compare_template_id' => (int) $candidate->id,
                ]);
            }
        }
        $diffPoints = $this->presentationService->buildDifferentiationPoints($resumeTemplate, 5);
        $suitableScenes = $this->presentationService->buildSuitableScenes($resumeTemplate);

        return view('user.resume-templates.show', [
            'template' => $resumeTemplate,
            'previewModules' => $previewModules,
            'previewResume' => $previewResume,
            'diffPoints' => $diffPoints,
            'suitableScenes' => $suitableScenes,
            'focusLabel' => $focusLabel,
            'relatedTemplates' => $relatedTemplates,
            'resumeTargets' => $resumeTargets,
            'compareTarget' => $compareTarget,
            'comparePreviewModules' => $comparePreviewModules,
            'comparePreviewResume' => $comparePreviewResume,
            'isExternal' => $isExternal,
            'externalUrl' => $externalUrl,
        ]);
    }

    public function apply(Request $request, ResumeTemplate $resumeTemplate): RedirectResponse
    {
        if (! $resumeTemplate->is_active) {
            return redirect()->route('user.resume-templates.index')->with('warning', '该模板暂不可用，请选择其他模板。');
        }

        $source = trim((string) $request->input('source', ''));
        $fromTemplateId = (int) $request->input('from_template_id', 0);
        $resumeId = (int) $request->input('resume_id', 0);
        $idempotencyKey = trim((string) $request->input('idempotency_key', ''));
        $userId = (int) $request->user()->id;

        if ($idempotencyKey !== '' && ! preg_match('/^[A-Za-z0-9_-]{12,80}$/', $idempotencyKey)) {
            return redirect()->back()->with('warning', '请求标识无效，请重试。');
        }

        if ($idempotencyKey !== '') {
            $cachedResume = $this->applicationService->checkIdempotency($userId, $idempotencyKey);
            if ($cachedResume instanceof Resume) {
                return redirect()->route('user.resumes.editor', $cachedResume)
                    ->with('success', '请求已处理，已为你定位到已套用结果。');
            }
        }

        try {
            $result = $this->applicationService->applyTemplate($resumeTemplate, $userId, $resumeId);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }

        $resume = $result['resume'];
        $applyMode = $result['apply_mode'];

        Log::info('resume_template.apply', [
            'user_id' => $userId,
            'template_id' => $resumeTemplate->id,
            'source' => $source !== '' ? $source : 'direct',
            'from_template_id' => $fromTemplateId > 0 ? $fromTemplateId : null,
            'resume_id' => $resume->id,
            'apply_mode' => $applyMode,
            'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
        ]);

        if ($idempotencyKey !== '') {
            $this->applicationService->storeIdempotency($userId, $idempotencyKey, (int) $resume->id);
        }

        $this->trackTemplateAction($request, 'resume_template_apply', [
            'template_id' => (int) $resumeTemplate->id,
            'template_slug' => (string) $resumeTemplate->slug,
            'resume_id' => (int) $resume->id,
            'apply_mode' => $applyMode,
            'source' => $source !== '' ? $source : 'direct',
        ]);

        $successMessage = $applyMode === 'existing'
            ? '模板已套用到现有简历，内容已保留并切换到新模板样式。'
            : '模板已套用成功，已为你创建可编辑简历。';

        return redirect()->route('user.resumes.editor', $resume)
            ->with('success', $successMessage);
    }

    public function analytics(Request $request): View
    {
        $days = (int) $request->query('days', 30);
        $userId = (int) $request->user()->id;

        $result = $this->analyticsService->computeAnalytics($userId, $days);

        return view('user.resume-templates.analytics', [
            'days' => $days,
            'overview' => $result['overview'],
            'rows' => $result['rows'],
        ]);
    }

    public function undoApply(Request $request, Resume $resume): RedirectResponse
    {
        if ((int) $resume->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $rolledBack = $this->applicationService->undoApply($resume);

        if (! $rolledBack) {
            return redirect()->route('user.resumes.editor', $resume)
                ->with('warning', '未找到可撤销的模板套用记录。');
        }

        Log::info('resume_template.undo_apply', [
            'user_id' => optional($request->user())->id,
            'resume_id' => $resume->id,
        ]);

        return redirect()->route('user.resumes.editor', $resume)
            ->with('success', '已撤销上次模板套用，样式已恢复。');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function trackTemplateAction(Request $request, string $action, array $payload = []): void
    {
        try {
            UserActionLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => $action,
                'route_name' => (string) ($request->route()?->getName() ?? ''),
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('resume_template_action_track_failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
