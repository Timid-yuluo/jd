<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Services\Api\V1\ResumeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResumeController extends Controller
{
    public function __construct(
        private readonly ResumeService $resumeService
    ) {}

    public function index(Request $request): View
    {
        $query = Resume::query()->with('user');

        if ($search = $request->input('search')) {
            $safeSearch = escapeLike($search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('title', 'like', "%{$safeSearch}%")
                    ->orWhere('target_job', 'like', "%{$safeSearch}%")
                    ->orWhereHas('user', function ($uq) use ($safeSearch) {
                        $uq->where('name', 'like', "%{$safeSearch}%")
                            ->orWhere('email', 'like', "%{$safeSearch}%");
                    });
            });
        }

        if ($atsFilter = $request->input('ats')) {
            match ($atsFilter) {
                'scored' => $query->whereNotNull('ats_score')->where('ats_score', '>', 0),
                'unscored' => $query->where(function ($q) {
                    $q->whereNull('ats_score')->orWhere('ats_score', 0);
                }),
                'high' => $query->where('ats_score', '>=', (int) config('ui.ats_threshold.high', 80)),
                'medium' => $query->whereBetween('ats_score', [(int) config('ui.ats_threshold.medium_low', 50), ((int) config('ui.ats_threshold.high', 80)) - 1]),
                'low' => $query->where('ats_score', '<', (int) config('ui.ats_threshold.medium_low', 50))->where('ats_score', '>', 0),
                default => null,
            };
        }

        if ($optimized = $request->input('optimized')) {
            match ($optimized) {
                'yes' => $query->whereNotNull('optimized_text'),
                'no' => $query->whereNull('optimized_text'),
                default => null,
            };
        }

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'ats_high' => $query->orderByDesc('ats_score')->orderByDesc('id'),
            'ats_low' => $query->orderBy('ats_score')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $resumes = $query->paginate((int) config('ui.pagination.admin_list', 15))->appends($request->only([
            'search', 'sort',
        ]));

        $stats = Cache::remember('admin:resumes:stats', (int) config('cache_ttl.ttl.admin_stats', 120), function () {
            return Resume::selectRaw('
                count(*) as total,
                sum(case when date(created_at) = curdate() then 1 else 0 end) as today,
                sum(case when ats_score is not null and ats_score > 0 then 1 else 0 end) as scored,
                sum(case when optimized_text is not null then 1 else 0 end) as optimized
            ')->first()->toArray();
        });

        return view('admin.resumes.index', compact('resumes', 'stats'));
    }

    public function show(Resume $resume): View
    {
        $resume->load('user');

        return view('admin.resumes.show', compact('resume'));
    }

    public function optimize(Resume $resume): RedirectResponse
    {
        try {
            $this->resumeService->optimize($resume, ['target_job' => $resume->target_job ?? '']);

            return redirect()->route('admin.resumes.show', $resume)->with('success', '简历 AI 优化已完成。');
        } catch (\Exception $e) {
            Log::error('Admin resume optimize failed', [
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', '简历优化失败，请稍后重试。若持续失败请联系管理员。');
        }
    }

    public function atsScore(Resume $resume): RedirectResponse
    {
        try {
            $this->resumeService->atsScore($resume);

            return redirect()->route('admin.resumes.show', $resume)->with('success', 'ATS 评分已完成。');
        } catch (\Exception $e) {
            Log::error('Admin resume ATS score failed', [
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'ATS 评分失败，请稍后重试。若持续失败请联系管理员。');
        }
    }

    public function destroy(Resume $resume): RedirectResponse
    {
        $resume->delete();

        return redirect()->route('admin.resumes.index')->with('success', '简历已删除。');
    }

    /**
     * 导出简历列表为 CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Resume::query()->with('user');

        if ($search = $request->input('search')) {
            $safeSearch = escapeLike($search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('title', 'like', "%{$safeSearch}%")
                    ->orWhere('target_job', 'like', "%{$safeSearch}%")
                    ->orWhereHas('user', function ($uq) use ($safeSearch) {
                        $uq->where('name', 'like', "%{$safeSearch}%")
                            ->orWhere('email', 'like', "%{$safeSearch}%");
                    });
            });
        }

        $filename = 'resumes_export_'.now()->format('Y_m_d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['ID', '用户', '邮箱', '标题', '目标岗位', 'ATS评分', '已优化', '创建时间']);

            $query->chunk(200, function ($resumes) use ($handle): void {
                foreach ($resumes as $resume) {
                    fputcsv($handle, [
                        $resume->id,
                        $resume->user?->name ?? '',
                        $resume->user?->email ?? '',
                        $resume->title ?? '',
                        $resume->target_job ?? '',
                        $resume->ats_score ?? '-',
                        $resume->optimized_text ? '是' : '否',
                        $resume->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
