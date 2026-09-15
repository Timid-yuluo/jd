<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\ExternalRecruitment;
use App\Models\InterviewSession;
use App\Models\JobMatchAnalysis;
use App\Models\Resume;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $cacheTtl = (int) config('cache_ttl.ttl.user_dashboard', 180);

        $stats = Cache::remember("user:dashboard:{$user->id}:stats", $cacheTtl, function () use ($user) {
            return [
                'resume_count' => $this->safeCount(fn () => Resume::where('user_id', $user->id)->count(), $user->id, 'resume_count'),
                'interview_count' => $this->safeCount(fn () => InterviewSession::where('user_id', $user->id)->count(), $user->id, 'interview_count'),
                'today_new_jobs' => $this->safeCountExternalJobsToday(),
            ];
        });

        $recentResumes = Cache::remember("user:dashboard:{$user->id}:recent_resumes", $cacheTtl, function () use ($user) {
            return $this->safeGetArray(
                fn () => Resume::where('user_id', $user->id)->select(['id', 'user_id', 'title', 'ats_score', 'target_job', 'created_at'])->withCount('modules')->orderByDesc('created_at')->limit((int) config('ui.limit.dashboard_recent', 5))->get()->toArray(),
                $user->id,
                'recent_resumes'
            );
        });

        $recentInterviews = Cache::remember("user:dashboard:{$user->id}:recent_interviews", $cacheTtl, function () use ($user) {
            return $this->safeGetArray(
                fn () => InterviewSession::where('user_id', $user->id)->with('resume:id,title')->orderByDesc('created_at')->limit((int) config('ui.limit.dashboard_recent', 5))->get()->toArray(),
                $user->id,
                'recent_interviews'
            );
        });

        $recentMatchAnalyses = Cache::remember("user:dashboard:{$user->id}:recent_matches", $cacheTtl, function () use ($user) {
            return $this->safeGetArray(
                fn () => JobMatchAnalysis::where('user_id', $user->id)->with('resume:id,title')->select(['id', 'user_id', 'resume_id', 'match_score', 'level', 'created_at'])->orderByDesc('created_at')->limit((int) config('ui.limit.dashboard_recent', 5))->get()->toArray(),
                $user->id,
                'recent_match_analyses'
            );
        });

        // 待办提醒数据（缓存60秒，实时性要求较低）
        $todoItems = Cache::remember("user:dashboard:{$user->id}:todos", 60, fn () => $this->buildTodoItems($user));

        // 新用户引导：注册7天内且无简历无面试
        $showOnboarding = $user->created_at->diffInDays(now()) <= 7
            && $stats['resume_count'] === 0
            && $stats['interview_count'] === 0
            && ! $request->session()->has('onboarding_dismissed');

        // 最新公告（已发送的公告类型通知，最多3条）
        // 缓存 key 预留分组能力：未来按用户角色/套餐过滤时可扩展为 user:dashboard:announcements:{group}
        $announcements = Cache::remember('user:dashboard:announcements:all', 300, function () {
            return AdminNotification::where('type', 'announcement')
                ->whereNotNull('sent_at')
                ->where('sent_at', '<=', now())
                ->orderByDesc('sent_at')
                ->limit(3)
                ->get(['id', 'title', 'content', 'type', 'sent_at'])
                ->toArray();
        });

        return view('user.dashboard', [
            'stats' => $stats,
            'recentResumes' => $recentResumes,
            'recentInterviews' => $recentInterviews,
            'recentMatchAnalyses' => $recentMatchAnalyses,
            'todoItems' => $todoItems,
            'showOnboarding' => $showOnboarding,
            'announcements' => $announcements,
        ]);
    }

    /**
     * 关闭新用户引导
     */
    public function dismissOnboarding(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->session()->put('onboarding_dismissed', true);

        return response()->json(['ok' => true]);
    }

    private function safeCount(callable $callback, int $userId, string $field): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable $e) {
            Log::error('Load dashboard count failed', [
                'user_id' => $userId,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    private function safeGetCollection(callable $callback, int $userId, string $field)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error('Load dashboard list failed', [
                'user_id' => $userId,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * 安全获取数组结果（用于缓存序列化），失败时返回空数组
     */
    private function safeGetArray(callable $callback, int $userId, string $field): array
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error('Load dashboard list failed', [
                'user_id' => $userId,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function safeCountExternalJobsToday(): int
    {
        try {
            return (int) ExternalRecruitment::where('review_status', ExternalRecruitment::REVIEW_APPROVED)
                ->whereDate('imported_at', now()->toDateString())
                ->count();
        } catch (\Throwable $e) {
            Log::warning('Count today external jobs failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * 构建用户待办提醒列表
     *
     * @return array<int, array{icon: string, text: string, url: string, badge: string, badge_class: string}>
     */
    private function buildTodoItems($user): array
    {
        $items = [];

        try {
            // 合并 4 个独立 COUNT 查询为 1 次批量查询
            $userId = $user->id;
            $unreadCount = UserNotification::getUnreadCount($userId);
            $activeInterviews = InterviewSession::where('user_id', $userId)
                ->whereIn('status', [InterviewSession::STATUS_PENDING, InterviewSession::STATUS_IN_PROGRESS, InterviewSession::STATUS_PAUSED])
                ->count();
            $unScoredResumes = Resume::where('user_id', $userId)->whereNull('ats_score')->count();

            if ($unreadCount > 0) {
                $items[] = [
                    'icon' => 'ti-bell',
                    'text' => "你有 {$unreadCount} 条未读通知",
                    'url' => route('user.notifications.index'),
                    'badge' => (string) $unreadCount,
                    'badge_class' => 'bg-danger',
                ];
            }

            if ($activeInterviews > 0) {
                $items[] = [
                    'icon' => 'ti-player-play',
                    'text' => "你有 {$activeInterviews} 场面试未完成",
                    'url' => route('user.interviews.index'),
                    'badge' => (string) $activeInterviews,
                    'badge_class' => 'bg-warning',
                ];
            }

            if ($unScoredResumes > 0) {
                $items[] = [
                    'icon' => 'ti-robot',
                    'text' => "你有 {$unScoredResumes} 份简历未进行 ATS 评分",
                    'url' => route('user.resumes.index'),
                    'badge' => (string) $unScoredResumes,
                    'badge_class' => 'bg-info',
                ];
            }

            // 订阅即将到期（7天内）
            $activeSub = $user->activeSubscription;
            if ($activeSub && $activeSub->expires_at && $activeSub->expires_at->diffInDays(now()) <= 7) {
                $daysLeft = max(0, (int) $activeSub->expires_at->diffInDays(now()));
                $items[] = [
                    'icon' => 'ti-alert-triangle',
                    'text' => "你的订阅将在 {$daysLeft} 天后到期",
                    'url' => route('user.membership.pricing'),
                    'badge' => (string) $daysLeft . '天',
                    'badge_class' => 'bg-warning',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Build todo items failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return $items;
    }

    /**
     * 清除指定用户的 Dashboard 缓存
     * 在简历创建/删除、面试创建等操作后调用
     */
    public static function clearUserCache(int $userId): void
    {
        $keys = [
            "user:dashboard:{$userId}:stats",
            "user:dashboard:{$userId}:recent_resumes",
            "user:dashboard:{$userId}:recent_interviews",
            "user:dashboard:{$userId}:recent_matches",
            "user:dashboard:{$userId}:todos",
            "user:dashboard:{$userId}:kanban_stats",
        ];

        Cache::deleteMultiple($keys);
    }
}
