<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\HandlesResumeModuleDisplay;
use App\Models\Resume;
use App\Services\Resume\ResumeCreateQuotaService;
use App\Services\Resume\ResumeModuleDisplayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ResumeController extends Controller
{
    use HandlesResumeModuleDisplay;

    public function __construct(
        private readonly ResumeModuleDisplayService $resumeModuleDisplayService,
        private readonly ResumeCreateQuotaService $resumeCreateQuotaService,
    ) {}

    public function index(Request $request): View
    {
        $currentPlan = $request->user()->currentPlan();
        $canExportDocx = ($currentPlan?->slug ?? 'free') !== 'free';
        $resumeQuota = $this->resumeCreateQuotaService->resolve($request->user());

        $query = Resume::query()
            ->where('user_id', $request->user()->id)
            ->with('careerTrack')
            ->withCount('modules');

        $keyword = trim((string) $request->string('q'));
        if ($keyword !== '') {
            $safeKeyword = escapeLike($keyword);
            $query->where(function ($builder) use ($safeKeyword): void {
                $builder
                    ->where('title', 'like', "%{$safeKeyword}%")
                    ->orWhere('target_job', 'like', "%{$safeKeyword}%");
            });
        }

        $atsFilter = (string) $request->input('ats', '');
        if ($atsFilter === 'scored') {
            $query->whereNotNull('ats_score');
        } elseif ($atsFilter === 'unscored') {
            $query->whereNull('ats_score');
        }

        $optimizeFilter = (string) $request->input('optimized', '');
        if ($optimizeFilter === 'yes') {
            $query->whereNotNull('optimized_text');
        } elseif ($optimizeFilter === 'no') {
            $query->whereNull('optimized_text');
        }

        $sort = (string) $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'ats_high' => $query->orderByDesc('ats_score')->orderByDesc('updated_at'),
            'ats_low' => $query->orderBy('ats_score')->orderByDesc('updated_at'),
            default => $query->orderByDesc('created_at'),
        };

        $resumes = $query->paginate((int) config('ui.pagination.user_grid', 9))->withQueryString();

        $userId = $request->user()->id;
        $stats = \Illuminate\Support\Facades\Cache::remember("resume_stats:{$userId}", 300, function () use ($userId) {
            $row = Resume::query()
                ->where('user_id', $userId)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN ats_score IS NOT NULL THEN 1 ELSE 0 END) as scored')
                ->selectRaw('SUM(CASE WHEN optimized_text IS NOT NULL THEN 1 ELSE 0 END) as optimized')
                ->selectRaw('ROUND(AVG(CASE WHEN ats_score IS NOT NULL THEN ats_score END)) as average_ats')
                ->first();
            return [
                'total' => (int) ($row->total ?? 0),
                'scored' => (int) ($row->scored ?? 0),
                'optimized' => (int) ($row->optimized ?? 0),
                'average_ats' => (int) ($row->average_ats ?? 0),
            ];
        });

        return view('user.resumes.index', compact('resumes', 'stats', 'keyword', 'atsFilter', 'optimizeFilter', 'sort', 'canExportDocx', 'resumeQuota'));
    }

    public function show(Request $request, Resume $resume): View
    {
        $this->authorize('view', $resume);

        $resume->load(['modules', 'careerTrack']);
        $currentPlan = $request->user()->currentPlan();
        $canExportDocx = ($currentPlan?->slug ?? 'free') !== 'free';

        $defaultModules = $this->buildShowModules($resume);

        // ATS 评分历史趋势
        $atsScoreHistory = $resume->atsScoreLogs()
            ->select(['id', 'score', 'level', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return view('user.resumes.show', compact('resume', 'defaultModules', 'canExportDocx', 'atsScoreHistory'));
    }
}
