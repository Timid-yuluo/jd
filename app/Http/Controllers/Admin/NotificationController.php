<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\AdminNotification;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 管理员通知中心控制器
 */
final class NotificationController extends Controller
{
    /**
     * 通知列表
     */
    public function index(): View
    {
        $notifications = AdminNotification::query()
            ->with('sender')
            ->withCount('emailLogs')
            ->orderBy('created_at', 'desc')
            ->paginate((int) config('ui.pagination.admin_list', 15));

        // 统计数据
        $stats = Cache::remember('admin:notifications:stats', (int) config('cache_ttl.ttl.admin_stats', 120), function () {
            return AdminNotification::selectRaw('
                count(*) as total,
                sum(case when sent_at is not null then 1 else 0 end) as sent,
                sum(case when sent_at is null then 1 else 0 end) as draft,
                sum(read_count) as total_read
            ')->first()->toArray();
        });

        return view('admin.notifications.index', compact('notifications', 'stats'));
    }

    /**
     * 创建通知页面
     */
    public function create(): View
    {
        $roles = [
            'admin' => '管理员',
            'user' => '普通用户',
        ];

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get();

        $emailTemplates = EmailTemplate::active()->get();

        return view('admin.notifications.create', compact('roles', 'users', 'emailTemplates'));
    }

    /**
     * 保存通知
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'content' => 'required|string|max:5000',
            'type' => 'required|string|in:announcement,maintenance,feature,warning',
            'channel' => 'required|string|in:site,email,both',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'target_type' => 'required|string|in:all,roles,users',
            'target_roles' => 'nullable|array',
            'target_users' => 'nullable|array',
            'target_users.*' => 'exists:users,id',
            'send_type' => 'required|string|in:draft,now,later',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // 判断是否立即发送
        $sendNow = $validated['send_type'] === 'now';
        $sendLater = $validated['send_type'] === 'later';

        $notification = AdminNotification::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'email_template_id' => $validated['email_template_id'] ?? null,
            'target_type' => $validated['target_type'],
            'target_roles' => $validated['target_roles'] ?? [],
            'target_users' => $validated['target_users'] ?? [],
            'sender_id' => auth()->id(),
            'sent_at' => null, // 队列作业成功后才标记为已发送
            'scheduled_at' => $sendLater ? $validated['scheduled_at'] : null,
            'read_count' => 0,
        ]);

        // 如果立即发送，加入队列
        if ($sendNow) {
            SendNotificationJob::dispatch($notification);
            $message = '通知已加入发送队列。';
        } elseif ($sendLater) {
            $message = '定时通知已设置，将在指定时间自动发送。';
        } else {
            $message = '通知草稿已保存。';
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', $message);
    }

    /**
     * 查看通知详情
     */
    public function show(AdminNotification $notification): View
    {
        // 加载关联
        $notification->load(['emailTemplate', 'sender']);

        // 获取邮件发送统计
        $emailStats = [
            'total' => $notification->emailLogs()->count(),
            'sent' => $notification->emailLogs()->sent()->count(),
            'failed' => $notification->emailLogs()->failed()->count(),
        ];

        // 获取邮件发送记录
        $emailLogs = $notification->emailLogs()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit((int) config('ui.limit.notification_preview', 20))
            ->get();

        // 获取用户通知接收统计
        $userNotificationCount = UserNotification::where('admin_notification_id', $notification->id)->count();

        return view('admin.notifications.show', compact('notification', 'emailStats', 'emailLogs', 'userNotificationCount'));
    }

    /**
     * 编辑通知
     */
    public function edit(AdminNotification $notification): View
    {
        if ($notification->isSent()) {
            abort(403, '已发送的通知不能编辑。');
        }

        $roles = [
            'admin' => '管理员',
            'user' => '普通用户',
        ];

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get();

        $emailTemplates = EmailTemplate::active()->get();

        return view('admin.notifications.edit', compact('notification', 'roles', 'users', 'emailTemplates'));
    }

    /**
     * 更新通知
     */
    public function update(Request $request, AdminNotification $notification): RedirectResponse
    {
        if ($notification->isSent()) {
            abort(403, '已发送的通知不能编辑。');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'content' => 'required|string|max:5000',
            'type' => 'required|string|in:announcement,maintenance,feature,warning',
            'channel' => 'required|string|in:site,email,both',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'target_type' => 'required|string|in:all,roles,users',
            'target_roles' => 'nullable|array',
            'target_users' => 'nullable|array',
            'target_users.*' => 'exists:users,id',
            'send_type' => 'required|string|in:draft,now,later',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // 判断是否立即发送
        $sendNow = $validated['send_type'] === 'now';
        $sendLater = $validated['send_type'] === 'later';

        $notification->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'email_template_id' => $validated['email_template_id'] ?? null,
            'target_type' => $validated['target_type'],
            'target_roles' => $validated['target_roles'] ?? [],
            'target_users' => $validated['target_users'] ?? [],
            'sent_at' => null, // 队列作业成功后才标记为已发送
            'scheduled_at' => $sendLater ? $validated['scheduled_at'] : null,
        ]);

        // 如果立即发送，加入队列
        if ($sendNow) {
            SendNotificationJob::dispatch($notification);
            $message = '通知已加入发送队列。';
        } elseif ($sendLater) {
            $message = '定时通知已更新，将在指定时间自动发送。';
        } else {
            $message = '通知草稿已更新。';
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', $message);
    }

    /**
     * 删除通知
     */
    public function destroy(AdminNotification $notification): RedirectResponse
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', '通知已删除。');
    }

    /**
     * 发送草稿通知
     */
    public function send(AdminNotification $notification): RedirectResponse
    {
        if ($notification->isSent()) {
            return redirect()->back()->with('error', '该通知已经发送过了。');
        }

        // 如果是定时通知，取消定时改为立即发送
        if ($notification->scheduled_at) {
            $notification->update([
                'scheduled_at' => null,
            ]);
        }

        // 加入发送队列（队列成功后才会标记 sent_at）
        SendNotificationJob::dispatch($notification);

        return redirect()->route('admin.notifications.index')
            ->with('success', '通知已加入发送队列。');
    }
}
