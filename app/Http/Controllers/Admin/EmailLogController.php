<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ResendEmailJob;
use App\Models\EmailLog;
use App\Services\Notification\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

final class EmailLogController extends Controller
{
    /**
     * 邮件日志列表页面
     */
    public function index(Request $request): View
    {
        $query = EmailLog::with(['user', 'adminNotification'])
            ->orderBy('created_at', 'desc');

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 搜索
        if ($request->has('search')) {
            $search = escapeLike($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // 日期范围
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate((int) config('ui.pagination.admin_table', 20))->withQueryString();

        // 统计信息
        $stats = Cache::remember('admin:email-logs:stats', (int) config('cache_ttl.ttl.admin_stats', 120), function () {
            return EmailLog::selectRaw('
                count(*) as total,
                sum(case when status = \'sent\' then 1 else 0 end) as sent,
                sum(case when status = \'failed\' then 1 else 0 end) as failed,
                sum(case when status = \'pending\' then 1 else 0 end) as pending,
                sum(case when date(created_at) = curdate() then 1 else 0 end) as today
            ')->first()->toArray();
        });

        return view('admin.email-logs.index', compact('logs', 'stats'));
    }

    /**
     * 邮件日志详情
     */
    public function show(EmailLog $emailLog): View
    {
        $emailLog->load(['user', 'adminNotification']);

        return view('admin.email-logs.show', compact('emailLog'));
    }

    /**
     * 重发邮件
     */
    public function resend(EmailLog $emailLog): JsonResponse
    {
        try {
            if ($emailLog->status === 'sent') {
                return $this->fail('该邮件已成功发送，无需重发', 422);
            }

            // 重发原始邮件内容
            if (! empty($emailLog->content)) {
                Mail::html((string) $emailLog->content, function ($message) use ($emailLog) {
                    $message->to($emailLog->recipient_email)
                        ->subject($emailLog->subject ?? '重发邮件');
                });
            } else {
                // 无原始内容时回退到测试邮件
                $emailService = app(EmailNotificationService::class);
                $emailService->sendTestEmail($emailLog->recipient_email);
            }

            $emailLog->markAsSent();

            return response()->json([
                'success' => true,
                'message' => '邮件重发成功',
            ]);
        } catch (\Exception $e) {
            $emailLog->markAsFailed($e->getMessage());

            return $this->fail('邮件重发失败: '.$e->getMessage(), 500);
        }
    }

    /**
     * 批量重发失败邮件
     */
    public function resendFailed(): JsonResponse
    {
        try {
            $failedLogs = EmailLog::failed()
                ->whereDate('created_at', '>=', now()->subDays((int) config('ui.chart.recent_days', 7)))
                ->get();

            $count = $failedLogs->count();

            if ($count === 0) {
                return response()->json([
                    'success' => true,
                    'message' => '没有需要重发的失败邮件',
                ]);
            }

            // 将每封失败邮件派发到队列异步发送
            foreach ($failedLogs as $log) {
                ResendEmailJob::dispatch($log);
            }

            return response()->json([
                'success' => true,
                'message' => "已将 {$count} 封失败邮件加入重发队列",
                'data' => [
                    'queued' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->fail('批量重发失败: '.$e->getMessage(), 500);
        }
    }

    /**
     * 删除邮件日志
     */
    public function destroy(EmailLog $emailLog): JsonResponse
    {
        try {
            $emailLog->delete();

            return response()->json([
                'success' => true,
                'message' => '邮件日志已删除',
            ]);
        } catch (\Exception $e) {
            return $this->fail('删除失败: '.$e->getMessage(), 500);
        }
    }

    /**
     * 清空日志（分批删除避免长事务锁表）
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', (int) config('ui.chart.default_days', 30));
            $totalDeleted = 0;
            $batchSize = 1000;

            do {
                $deleted = EmailLog::whereDate('created_at', '<', now()->subDays($days))
                    ->limit($batchSize)
                    ->delete();

                $totalDeleted += $deleted;
            } while ($deleted >= $batchSize);

            return response()->json([
                'success' => true,
                'message' => "已清理 {$totalDeleted} 条 {$days} 天前的邮件日志",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '清理失败: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取统计信息
     */
    public function statistics(): JsonResponse
    {
        try {
            $cacheKey = 'admin:email_logs:statistics';
            $stats = Cache::remember($cacheKey, now()->addSeconds((int) config('cache_ttl.ttl.standard', 300)), function () {
                $row = EmailLog::selectRaw('
                    count(*) as total,
                    sum(case when status = ? then 1 else 0 end) as sent,
                    sum(case when status = ? then 1 else 0 end) as failed,
                    sum(case when status = ? then 1 else 0 end) as pending,
                    sum(case when date(created_at) = ? then 1 else 0 end) as today,
                    sum(case when created_at between ? and ? then 1 else 0 end) as this_week,
                    sum(case when month(created_at) = ? and year(created_at) = ? then 1 else 0 end) as this_month
                ', [
                    'sent',
                    'failed',
                    'pending',
                    today()->toDateString(),
                    now()->startOfWeek()->toDateTimeString(),
                    now()->endOfWeek()->toDateTimeString(),
                    now()->month,
                    now()->year,
                ])->first();

                $data = $row ? $row->toArray() : [];
                $completed = ($data['sent'] ?? 0) + ($data['failed'] ?? 0);
                $data['success_rate'] = $completed > 0
                    ? round((($data['sent'] ?? 0) / $completed) * 100, 2)
                    : 0;

                return $data;
            });

            $dailyStats = Cache::remember('admin:email_logs:daily_stats', now()->addSeconds((int) config('cache_ttl.ttl.daily_stats', 600)), function () {
                return EmailLog::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count')
                    ->whereDate('created_at', '>=', now()->subDays((int) config('ui.chart.default_days', 30)))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => $stats,
                    'daily' => $dailyStats,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('EmailLog statistics failed', ['error' => $e->getMessage()]);

            return $this->fail('获取统计失败，请稍后重试。', 500);
        }
    }
}
