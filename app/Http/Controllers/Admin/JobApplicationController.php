<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

final class JobApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $query = JobApplication::query()->with('user');

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 搜索
        if ($request->filled('search')) {
            $search = escapeLike($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('company', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        // 渠道筛选
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // 日期范围
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applications = $query->latest('id')->paginate((int) config('ui.pagination.admin_list', 15))->withQueryString();

        // 统计卡片
        $stats = Cache::remember('admin:job-applications:stats', (int) config('cache_ttl.ttl.admin_stats', 120), function () {
            return JobApplication::selectRaw('
                count(*) as total,
                sum(case when date(created_at) = curdate() then 1 else 0 end) as today,
                sum(case when status = \''.JobApplication::STATUS_WISHLIST.'\' then 1 else 0 end) as wishlist,
                sum(case when status = \''.JobApplication::STATUS_APPLIED.'\' then 1 else 0 end) as applied,
                sum(case when status = \''.JobApplication::STATUS_INTERVIEW.'\' then 1 else 0 end) as interview,
                sum(case when status = \''.JobApplication::STATUS_OFFER.'\' then 1 else 0 end) as offer,
                sum(case when status = \''.JobApplication::STATUS_REJECTED.'\' then 1 else 0 end) as rejected
            ')->first()->toArray();
        });

        // 渠道列表（用于筛选下拉）
        $channels = JobApplication::query()
            ->whereNotNull('channel')
            ->where('channel', '!=', '')
            ->distinct()
            ->pluck('channel')
            ->sort()
            ->values();

        return view('admin.job-applications.index', compact('applications', 'stats', 'channels'));
    }

    public function show(JobApplication $job_application): View
    {
        return view('admin.job-applications.show', compact('job_application'));
    }

    public function edit(JobApplication $job_application): View
    {
        return view('admin.job-applications.edit', compact('job_application'));
    }

    public function update(Request $request, JobApplication $job_application): RedirectResponse
    {
        $validated = $request->validate([
            'company' => 'required|string|max:120',
            'position' => 'required|string|max:120',
            'status' => ['required', 'string', 'max:30', Rule::in(array_keys(JobApplication::statusLabels()))],
            'channel' => 'nullable|string|max:60',
            'deadline' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $job_application->update($validated);

        return redirect()->route('admin.job-applications.index')->with('success', '投递记录已更新。');
    }

    public function destroy(JobApplication $job_application): RedirectResponse
    {
        $job_application->delete();

        return redirect()->route('admin.job-applications.index')->with('success', '投递记录已删除。');
    }
}
