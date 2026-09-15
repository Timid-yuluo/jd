<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 用户通知控制器
 */
final class NotificationController extends Controller
{
    use RespondsWithJsonSuccess;

    /**
     * 通知列表
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 筛选条件
        $filter = $request->input('filter', 'all');
        $type = $request->input('type');

        $query = UserNotification::where('user_id', $user->id);

        // 阅读状态筛选
        switch ($filter) {
            case 'unread':
                $query->unread();
                break;
            case 'read':
                $query->read();
                break;
        }

        // 类型筛选
        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->orderByDesc('created_at')->paginate((int) config('ui.pagination.user_list', 15));

        // 添加筛选参数到分页链接
        $notifications->appends($request->query());

        $statsRow = UserNotification::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread')
            ->selectRaw('SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as `read`')
            ->first();
        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'unread' => (int) ($statsRow->unread ?? 0),
            'read' => (int) ($statsRow->read ?? 0),
        ];

        // 通知类型列表
        $types = [
            'system' => '系统通知',
            'announcement' => '系统公告',
            'maintenance' => '维护通知',
            'feature' => '功能更新',
            'warning' => '重要提醒',
        ];

        return view('user.notifications.index', compact('notifications', 'stats', 'filter', 'type', 'types'));
    }

    /**
     * 通知详情
     */
    public function show(Request $request, UserNotification $notification): View
    {
        $this->authorize('view', $notification);

        // 自动标记为已读
        if (! $notification->isRead()) {
            $notification->markAsRead();
        }

        return view('user.notifications.show', compact('notification'));
    }

    /**
     * 标记通知为已读
     */
    public function markAsRead(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return redirect()->back()->with('success', '已标记为已读。');
    }

    /**
     * 标记所有通知为已读
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        UserNotification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        UserNotification::clearUnreadCache($user->id);

        return redirect()->back()->with('success', '所有通知已标记为已读。');
    }

    /**
     * 删除通知
     */
    public function destroy(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return redirect()->route('user.notifications.index')
            ->with('success', '通知已删除。');
    }

    /**
     * 获取未读通知数量（API）
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = UserNotification::getUnreadCount($request->user()->id);

        return $this->respondSuccessPayload([
            'count' => $count,
        ]);
    }

    /**
     * 获取最近通知（API）
     */
    public function recent(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = (int) config('ui.limit.notification_dropdown', 5);

        $notifications = \Illuminate\Support\Facades\Cache::remember("notifications:recent:{$userId}", 30, function () use ($userId, $limit) {
            return UserNotification::where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'type' => $notification->type,
                        'type_label' => $notification->type_label,
                        'type_color' => $notification->type_color,
                        'is_read' => $notification->isRead(),
                        'created_at' => $notification->created_at->diffForHumans(),
                        'url' => route('user.notifications.show', $notification),
                    ];
                });
        });

        return $this->respondSuccessPayload([
            'notifications' => $notifications,
        ]);
    }
}
