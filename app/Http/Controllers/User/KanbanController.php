<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ExternalRecruitment;
use App\Models\JobApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class KanbanController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $industry = trim((string) $request->query('industry', ''));
        $location = trim((string) $request->query('location', ''));
        $allowedTypes = ['all', ExternalRecruitment::TYPE_CAMPUS, ExternalRecruitment::TYPE_SOCIAL];
        $recruitmentType = (string) $request->query(
            'recruitment_type',
            (string) $request->session()->get('user.kanban.recruitment_type', 'all')
        );
        if (! in_array($recruitmentType, $allowedTypes, true)) {
            $recruitmentType = 'all';
        }
        $request->session()->put('user.kanban.recruitment_type', $recruitmentType);
        $sort = (string) $request->query('sort', 'latest');
        $allowedPerPage = [(int) config('ui.pagination.kanban', 20), 40, 60];
        $perPage = (int) $request->integer('per_page', (int) config('ui.pagination.kanban', 20));
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = (int) config('ui.pagination.kanban', 20);
        }

        $baseQuery = ExternalRecruitment::query()
            ->where('review_status', ExternalRecruitment::REVIEW_APPROVED);

        if ($keyword !== '') {
            $baseQuery->search($keyword);
        }
        if ($industry !== '') {
            $baseQuery->where('industry', 'like', '%'.escapeLike($industry).'%');
        }
        if ($location !== '') {
            $baseQuery->where('work_location', 'like', '%'.escapeLike($location).'%');
        }

        $query = clone $baseQuery;
        $this->applyRecruitmentTypeFilter($query, $recruitmentType);

        if ($sort === 'oldest') {
            $query->orderBy('imported_at')->orderBy('id');
        } elseif ($sort === 'company_asc') {
            $query->orderBy('company')->latest('imported_at')->latest('id');
        } else {
            $sort = 'latest';
            $query->latest('imported_at')->latest('id');
        }

        $recruitments = $query
            ->paginate($perPage)
            ->appends($request->query());

        $stats = Cache::remember('kanban:stats', 300, function () {
            $approved = ExternalRecruitment::where('review_status', ExternalRecruitment::REVIEW_APPROVED);

            return [
                'total' => (clone $approved)->count(),
                'today' => (clone $approved)->whereDate('imported_at', today())->count(),
                'industry_count' => (clone $approved)
                    ->whereNotNull('industry')->where('industry', '!=', '')
                    ->distinct('industry')->count('industry'),
                'location_count' => (clone $approved)
                    ->whereNotNull('work_location')->where('work_location', '!=', '')
                    ->distinct('work_location')->count('work_location'),
            ];
        });

        $hotIndustries = Cache::remember('kanban:hot_industries', 300, fn () =>
            ExternalRecruitment::query()
                ->where('review_status', ExternalRecruitment::REVIEW_APPROVED)
                ->whereNotNull('industry')->where('industry', '!=', '')
                ->selectRaw('industry, COUNT(*) as total')
                ->groupBy('industry')
                ->orderByDesc('total')
                ->limit((int) config('ui.limit.sidebar_hot', 8))
                ->pluck('industry')
        );

        $hotLocations = Cache::remember('kanban:hot_locations', 300, fn () =>
            ExternalRecruitment::query()
                ->where('review_status', ExternalRecruitment::REVIEW_APPROVED)
                ->whereNotNull('work_location')->where('work_location', '!=', '')
                ->selectRaw('work_location, COUNT(*) as total')
                ->groupBy('work_location')
                ->orderByDesc('total')
                ->limit((int) config('ui.limit.kanban_sidebar', 8))
                ->pluck('work_location')
        );

        $sortOptions = [
            'latest' => '最新优先',
            'oldest' => '最早优先',
            'company_asc' => '公司名 A-Z',
        ];

        $typeTabs = Cache::remember('kanban:type_tabs', 300, function () use ($baseQuery) {
            return [
                'all' => [
                    'label' => '全部',
                    'count' => (clone $baseQuery)->count(),
                ],
                ExternalRecruitment::TYPE_CAMPUS => [
                    'label' => '校招',
                    'count' => $this->applyRecruitmentTypeFilter(clone $baseQuery, ExternalRecruitment::TYPE_CAMPUS)->count(),
                ],
                ExternalRecruitment::TYPE_SOCIAL => [
                    'label' => '社招',
                    'count' => $this->applyRecruitmentTypeFilter(clone $baseQuery, ExternalRecruitment::TYPE_SOCIAL)->count(),
                ],
            ];
        });

        return view('user.kanban.index', compact(
            'recruitments',
            'stats',
            'keyword',
            'industry',
            'location',
            'recruitmentType',
            'typeTabs',
            'sort',
            'sortOptions',
            'perPage',
            'allowedPerPage',
            'hotIndustries',
            'hotLocations'
        ));
    }

    private function applyRecruitmentTypeFilter(Builder $query, string $type): Builder
    {
        if ($type === 'all') {
            return $query;
        }

        if ($type === ExternalRecruitment::TYPE_CAMPUS) {
            return $query->where(function (Builder $builder): void {
                $builder->where('recruitment_type', ExternalRecruitment::TYPE_CAMPUS)
                    ->orWhere('channel', 'like', '%校招%')
                    ->orWhere('title', 'like', '%应届%')
                    ->orWhere('positions', 'like', '%应届%');
            });
        }

        // 社招：排除校招（非校招即社招）
        return $query->where(function (Builder $builder): void {
            $builder->where('recruitment_type', ExternalRecruitment::TYPE_SOCIAL)
                ->orWhere(function (Builder $sub): void {
                    $sub->whereNull('recruitment_type')
                        ->where('channel', 'not like', '%校招%')
                        ->where('title', 'not like', '%应届%')
                        ->where('positions', 'not like', '%应届%');
                })
                ->orWhere('channel', 'like', '%社招%')
                ->orWhere('channel', 'like', '%社会招聘%');
        });
    }

    /**
     * Offer 对比页面
     */
    public function offersCompare(Request $request): View
    {
        $user = $request->user();
        $offerIds = array_filter(array_map('intval', (array) $request->input('ids', [])));

        $offers = JobApplication::where('user_id', $user->id)
            ->where('status', 'offer')
            ->when(!empty($offerIds), fn ($q) => $q->whereIn('id', $offerIds))
            ->orderByDesc('salary_max')
            ->orderByDesc('salary_min')
            ->get();

        return view('user.offers-compare', compact('offers'));
    }
}
