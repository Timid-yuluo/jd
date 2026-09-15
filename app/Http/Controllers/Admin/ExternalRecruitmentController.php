<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncExternalRecruitments;
use App\Models\ExternalRecruitment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ExternalRecruitmentController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filterSessionKeys = [
            'admin.external_recruitments.filter.status',
            'admin.external_recruitments.filter.keyword',
            'admin.external_recruitments.filter.source',
            'admin.external_recruitments.filter.recruitment_type',
            'admin.external_recruitments.filter.today_only',
        ];
        if ($request->boolean('reset')) {
            $request->session()->forget($filterSessionKeys);

            return redirect()->route('admin.external-recruitments.index');
        }

        $status = $this->resolveFilterString(
            $request,
            'status',
            'admin.external_recruitments.filter.status',
            ExternalRecruitment::REVIEW_PENDING
        );
        $keyword = $this->resolveFilterString($request, 'keyword', 'admin.external_recruitments.filter.keyword', '');
        $source = $this->resolveFilterString($request, 'source', 'admin.external_recruitments.filter.source', '');
        $recruitmentType = $this->resolveFilterString(
            $request,
            'recruitment_type',
            'admin.external_recruitments.filter.recruitment_type',
            ''
        );
        $todayOnly = $this->resolveFilterBool($request, 'today_only', 'admin.external_recruitments.filter.today_only', false);

        $query = ExternalRecruitment::query()
            ->orderByDesc('imported_at')
            ->orderByDesc('id');
        if ($status !== '') {
            $query->where('review_status', $status);
        }
        if ($source !== '') {
            $query->where('source_name', $source);
        }
        if ($recruitmentType !== '') {
            $query->where('recruitment_type', $recruitmentType);
        }
        if ($keyword !== '') {
            $safeKeyword = escapeLike($keyword);
            $query->search($keyword)
                ->orWhere('industry', 'like', '%'.$safeKeyword.'%')
                ->orWhere('work_location', 'like', '%'.$safeKeyword.'%');
        }
        if ($todayOnly) {
            $query->whereDate('imported_at', today());
        }

        $recruitments = $query->paginate((int) config('ui.pagination.admin_table', 20))->appends($request->query());
        $stats = $this->getStats();

        return view('admin.external-recruitments.index', compact(
            'recruitments',
            'stats',
            'status',
            'keyword',
            'source',
            'recruitmentType',
            'todayOnly'
        ));
    }

    public function approve(Request $request, ExternalRecruitment $externalRecruitment): JsonResponse|RedirectResponse
    {
        if ($externalRecruitment->review_status === ExternalRecruitment::REVIEW_APPROVED) {
            return $this->respondReviewResult($request, '该记录已是通过状态', 422);
        }

        $externalRecruitment->update([
            'review_status' => ExternalRecruitment::REVIEW_APPROVED,
            'review_note' => trim((string) $request->input('review_note', '')),
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return $this->respondReviewResult($request, '招聘记录已审核通过');
    }

    public function reject(Request $request, ExternalRecruitment $externalRecruitment): JsonResponse|RedirectResponse
    {
        if ($externalRecruitment->review_status === ExternalRecruitment::REVIEW_REJECTED) {
            return $this->respondReviewResult($request, '该记录已是驳回状态', 422);
        }

        $externalRecruitment->update([
            'review_status' => ExternalRecruitment::REVIEW_REJECTED,
            'review_note' => trim((string) $request->input('review_note', '')),
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return $this->respondReviewResult($request, '招聘记录已驳回');
    }

    public function batchApprove(Request $request): JsonResponse|RedirectResponse
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->input('ids', [])
        ))));
        if (empty($ids)) {
            return $this->respondReviewResult($request, '请选择记录', 422);
        }

        $selectedCount = count($ids);
        $count = ExternalRecruitment::whereIn('id', $ids)
            ->where('review_status', ExternalRecruitment::REVIEW_PENDING)
            ->update([
                'review_status' => ExternalRecruitment::REVIEW_APPROVED,
                'review_note' => trim((string) $request->input('review_note', '')),
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);

        $skippedCount = max(0, $selectedCount - $count);
        $message = "已批量通过 {$count} 条记录";
        if ($skippedCount > 0) {
            $message .= "，跳过 {$skippedCount} 条非待审核记录";
        }

        return $this->respondReviewResult($request, $message, 200, ['count' => $count, 'skipped' => $skippedCount]);
    }

    public function batchReject(Request $request): JsonResponse|RedirectResponse
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->input('ids', [])
        ))));
        if (empty($ids)) {
            return $this->respondReviewResult($request, '请选择记录', 422);
        }

        $selectedCount = count($ids);
        $count = ExternalRecruitment::whereIn('id', $ids)
            ->where('review_status', ExternalRecruitment::REVIEW_PENDING)
            ->update([
                'review_status' => ExternalRecruitment::REVIEW_REJECTED,
                'review_note' => trim((string) $request->input('review_note', '')),
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);

        $skippedCount = max(0, $selectedCount - $count);
        $message = "已批量驳回 {$count} 条记录";
        if ($skippedCount > 0) {
            $message .= "，跳过 {$skippedCount} 条非待审核记录";
        }

        return $this->respondReviewResult($request, $message, 200, ['count' => $count, 'skipped' => $skippedCount]);
    }

    /**
     * 一键通过所有待审核记录
     */
    public function approveAll(Request $request): JsonResponse|RedirectResponse
    {
        $count = ExternalRecruitment::where('review_status', ExternalRecruitment::REVIEW_PENDING)
            ->update([
                'review_status' => ExternalRecruitment::REVIEW_APPROVED,
                'review_note' => '一键批量通过',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);

        $message = "已通过全部 {$count} 条待审核记录";

        return $this->respondReviewResult($request, $message, 200, ['count' => $count]);
    }

    /**
     * 智能自动审核 — 基于规则自动通过/驳回
     *
     * 通过规则：有公司名 + 有投递链接 + 非重复指纹
     * 驳回规则：无公司名 或 无投递链接且无公告链接
     */
    public function autoReview(Request $request): JsonResponse|RedirectResponse
    {
        $pending = ExternalRecruitment::where('review_status', ExternalRecruitment::REVIEW_PENDING)->get();

        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($pending as $item) {
            $hasCompany = !empty(trim((string) $item->company));
            $hasApplyUrl = !empty(trim((string) $item->apply_url));
            $hasAnnouncementUrl = !empty(trim((string) $item->announcement_url));
            $hasPositions = !empty(trim((string) $item->positions)) || !empty(trim((string) $item->title));

            // 驳回条件：无公司名 或 (无投递链接且无公告链接) 或 无岗位信息
            if (!$hasCompany || (!$hasApplyUrl && !$hasAnnouncementUrl) || !$hasPositions) {
                $reasons = [];
                if (!$hasCompany) {
                    $reasons[] = '无公司名';
                }
                if (!$hasApplyUrl && !$hasAnnouncementUrl) {
                    $reasons[] = '无有效链接';
                }
                if (!$hasPositions) {
                    $reasons[] = '无岗位信息';
                }

                $item->update([
                    'review_status' => ExternalRecruitment::REVIEW_REJECTED,
                    'review_note' => '自动驳回：' . implode('、', $reasons),
                    'reviewed_by' => $request->user()?->id,
                    'reviewed_at' => now(),
                ]);
                $rejectedCount++;
                continue;
            }

            // 重复检测：同指纹已有通过记录
            if ($item->fingerprint) {
                $duplicateApproved = ExternalRecruitment::where('fingerprint', $item->fingerprint)
                    ->where('id', '!=', $item->id)
                    ->where('review_status', ExternalRecruitment::REVIEW_APPROVED)
                    ->exists();
                if ($duplicateApproved) {
                    $item->update([
                        'review_status' => ExternalRecruitment::REVIEW_REJECTED,
                        'review_note' => '自动驳回：重复记录',
                        'reviewed_by' => $request->user()?->id,
                        'reviewed_at' => now(),
                    ]);
                    $rejectedCount++;
                    continue;
                }
            }

            // 通过条件：有公司名 + 有链接 + 有岗位 + 非重复
            $item->update([
                'review_status' => ExternalRecruitment::REVIEW_APPROVED,
                'review_note' => '自动审核通过',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);
            $approvedCount++;
        }

        $message = "智能审核完成：通过 {$approvedCount} 条，驳回 {$rejectedCount} 条";

        return $this->respondReviewResult($request, $message, 200, [
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
        ]);
    }

    public function sync(Request $request): RedirectResponse
    {
        $pages = max(1, $request->integer('pages', 2));
        $days = max(1, $request->integer('days', (int) config('ui.chart.default_days', 30)));
        $delay = max(0, $request->integer('delay', 1));
        $autoApprove = $request->boolean('auto_approve', false);
        $source = (string) $request->input('source', 'offerstar');

        try {
            SyncExternalRecruitments::dispatch(
                source: $source,
                pages: $pages,
                days: $days,
                scrapeDelay: $delay,
                autoApprove: $autoApprove,
            );

            Log::info('外部招聘同步任务已提交队列', [
                'source' => $source,
                'pages' => $pages,
                'days' => $days,
                'delay' => $delay,
                'auto_approve' => $autoApprove,
            ]);

            return back()->with('success', '同步任务已提交后台队列，稍后刷新页面查看结果。');
        } catch (\Throwable $e) {
            Log::error('提交外部招聘同步任务失败', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['sync' => '任务提交失败：' . $e->getMessage()]);
        }
    }

    private function getStats(): array
    {
        $grouped = ExternalRecruitment::query()
            ->selectRaw('review_status, COUNT(*) as cnt')
            ->groupBy('review_status')
            ->pluck('cnt', 'review_status')
            ->toArray();

        return [
            'total' => array_sum($grouped),
            'pending' => $grouped[ExternalRecruitment::REVIEW_PENDING] ?? 0,
            'approved' => $grouped[ExternalRecruitment::REVIEW_APPROVED] ?? 0,
            'rejected' => $grouped[ExternalRecruitment::REVIEW_REJECTED] ?? 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function respondReviewResult(Request $request, string $message, int $status = 200, array $extra = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'message' => $message,
                'stats' => $this->getStats(),
            ], $extra), $status);
        }

        if ($status >= 400) {
            return back()->withErrors(['review' => $message]);
        }

        $redirectTo = trim((string) $request->input('redirect_to', ''));
        if ($redirectTo !== '' && str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return redirect($redirectTo)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    private function resolveFilterString(Request $request, string $queryKey, string $sessionKey, string $default): string
    {
        if ($request->has($queryKey)) {
            $value = trim((string) $request->query($queryKey, ''));
            $request->session()->put($sessionKey, $value);

            return $value;
        }

        return (string) $request->session()->get($sessionKey, $default);
    }

    private function resolveFilterBool(Request $request, string $queryKey, string $sessionKey, bool $default): bool
    {
        if ($request->has($queryKey)) {
            $value = $request->boolean($queryKey, false);
            $request->session()->put($sessionKey, $value);

            return $value;
        }

        return (bool) $request->session()->get($sessionKey, $default);
    }
}
