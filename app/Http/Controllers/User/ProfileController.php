<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Http\Requests\User\ProfilePasswordUpdateRequest;
use App\Http\Requests\User\ProfileUpdateRequest;
use App\Mail\AccountDeletionRequested;
use App\Models\AccountRecoveryToken;
use App\Models\InterviewSession;
use App\Models\PasswordHistory;
use App\Models\QuotaUsage;
use App\Models\ResumeExportTask;
use App\Models\ResumeOptimizeSession;
use App\Models\UserActionLog;
use App\Models\UserCredit;
use App\Services\Admin\SystemSettingService;
use App\Services\Membership\CreditService;
use App\Services\Membership\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProfileController extends Controller
{
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly SystemSettingService $systemSettingService,
        private readonly QuotaService $quotaService,
        private readonly CreditService $creditService,
    ) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        $settings = $this->systemSettingService->all();
        $boundProviders = $user->oauthAccounts()->pluck('provider')->all();

        return view('user.profile', [
            'user' => $user,
            'loginHistories' => $user->loginHistories()->limit((int) config('ui.limit.dashboard_recent', 5))->get(),
            'hasNewDevice' => $this->checkNewDevice($user, $request),
            'activityStats' => $this->getActivityStats($user),
            'membershipSummary' => $this->getMembershipSummary($user),
            'authAccountBindingEnabled' => (int) ($settings['auth_account_binding_enabled'] ?? 0) === 1,
            'authBindingAllowUnbind' => (int) ($settings['auth_binding_allow_unbind'] ?? 0) === 1,
            'authBindingRequirePasswordConfirm' => (int) ($settings['auth_binding_require_password_confirm'] ?? 0) === 1,
            'authProviderGithubEnabled' => (int) ($settings['auth_provider_github_enabled'] ?? 0) === 1,
            'authProviderAlipayEnabled' => (int) ($settings['auth_provider_alipay_enabled'] ?? 0) === 1,
            'isGithubBound' => in_array('github', $boundProviders, true),
            'isAlipayBound' => in_array('alipay', $boundProviders, true),
        ]);
    }

    private function getActivityStats($user): array
    {
        return \Illuminate\Support\Facades\Cache::remember("user:activity_stats:{$user->id}", 300, function () use ($user) {
            $days = 30;
            $startDate = now()->subDays($days - 1)->startOfDay();

            // 一次性聚合查询替代90次循环查询
            $loginStats = $user->loginHistories()
                ->reorder()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $startDate)
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->toArray();

            $resumeStats = $user->resumes()
                ->selectRaw('DATE(updated_at) as date, COUNT(*) as count')
                ->where('updated_at', '>=', $startDate)
                ->groupByRaw('DATE(updated_at)')
                ->pluck('count', 'date')
                ->toArray();

            $interviewStats = $user->interviewSessions()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $startDate)
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->toArray();

            $stats = ['labels' => [], 'login' => [], 'resume' => [], 'interview' => []];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $stats['labels'][] = $date->format('m-d');
            $stats['login'][] = $loginStats[$dateKey] ?? 0;
            $stats['resume'][] = $resumeStats[$dateKey] ?? 0;
            $stats['interview'][] = $interviewStats[$dateKey] ?? 0;
        }

        return $stats;
        });
    }

    private function getMembershipSummary($user): array
    {
        $plan = $user->currentPlan();
        $activeSubscription = $user->activeSubscription;
        $period = QuotaUsage::currentPeriod();

        $quotaKeys = [
            'optimize_full',
            'optimize_section',
            'ats_score',
            'keywords_extract',
            'export_pdf',
            'import_document',
            'interview_sessions',
            'interview_evaluation',
            'job_match',
            'match_analysis',
            'resume_job_compare',
        ];

        $bulkUsage = $this->quotaService->getBulkMonthlyUsage((int) $user->id, $quotaKeys, $period);

        $usages = [];
        foreach ($quotaKeys as $key) {
            $limit = $plan ? $plan->getQuotaLimit($key) : 0;
            $used = $bulkUsage[$key] ?? 0;
            $remaining = $limit === -1 ? null : max(0, $limit - $used);

            if ($key === 'ats_score') {
                $usages[] = [
                    'key' => $key,
                    'label' => 'ATS 评分',
                    'used' => '每份简历',
                    'limit' => '3 次/天/简历',
                    'remaining' => '3 次/天',
                    'unlimited' => false,
                    'percent' => 0,
                ];
                continue;
            }

            $usages[] = [
                'key' => $key,
                'label' => UserCredit::quotaLabel($key),
                'used' => $used,
                'limit' => $limit === -1 ? null : $limit,
                'remaining' => $remaining,
                'unlimited' => $limit === -1,
                'percent' => ($limit > 0 && $limit !== -1)
                    ? min(100, (int) round($used / max($limit, 1) * 100))
                    : 0,
            ];
        }

        $creditsSummary = $this->creditService->getUserCreditsSummary($user);
        $universalCredits = (int) ($creditsSummary['universal']['total'] ?? 0);
        $specialCredits = collect($creditsSummary)
            ->except('universal')
            ->sum(static fn (array $item): int => (int) ($item['total'] ?? 0));

        return [
            'plan' => $plan,
            'active_subscription' => $activeSubscription,
            'period' => $period,
            'usages' => $usages,
            'universal_credits' => $universalCredits,
            'special_credits' => (int) $specialCredits,
        ];
    }

    private function checkNewDevice($user, Request $request): bool
    {
        // 检查是否为新设备（简化：检查最近7天是否有相同IP和User-Agent的登录）
        // 使用 exists() 替代 count()，O(1) 而非 O(n)
        $hasRecentLogin = $user->loginHistories()
            ->where('created_at', '>=', now()->subDays((int) config('ui.chart.recent_days', 7)))
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->header('User-Agent'))
            ->exists();

        return ! $hasRecentLogin; // 无历史记录说明是新设备
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

        $user->fill($validated);
        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->verification_token = null;
            $user->verification_token_expires_at = null;
        }
        $user->save();

        if ($request->expectsJson()) {
            return $this->respondSuccessPayload([
                'message' => $emailChanged ? '邮箱已变更，请重新验证新邮箱。' : '个人资料已更新',
                'email_changed' => $emailChanged,
            ]);
        }

        if ($emailChanged) {
            return redirect()->route('user.profile')
                ->with('success', '个人资料已更新')
                ->with('warning', '邮箱已变更，请重新验证新邮箱。');
        }

        return redirect()->route('user.profile')->with('success', '个人资料已更新');
    }

    public function updatePassword(ProfilePasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'password' => $validated['password'],
        ]);

        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => $user->password,
        ]);

        $keepIds = PasswordHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('id');

        PasswordHistory::query()
            ->where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        return redirect()->route('user.profile')->with('success', '密码已更新');
    }

    /**
     * 退出所有设备（清除所有session）
     */
    public function logoutAllDevices(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 删除该用户的所有session记录
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        // 清除当前session并重新生成
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', '已成功退出所有设备，请重新登录');
    }

    /**
     * 查看操作日志
     */
    public function activityLog(Request $request): View
    {
        $user = $request->user();
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));

        $logs = UserActionLog::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate($perPage)
            ->appends($request->query());

        return view('user.activity-log', compact('logs'));
    }

    /**
     * 导出个人数据
     */

    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user();

        // 加载关联数据
        $user->load(['resumes', 'interviewSessions', 'jobApplications']);

        $data = [
            'user_info' => [
                'name' => $user->name,
                'email' => $user->email,
                'school' => $user->school,
                'major' => $user->major,
                'created_at' => $user->created_at->toDateTimeString(),
            ],
            'resumes' => $user->resumes->map(fn ($r) => [
                'title' => $r->title,
                'content' => $r->content_raw,
                'ats_score' => $r->ats_score,
                'created_at' => $r->created_at->toDateTimeString(),
            ]),
            'interviews' => $user->interviewSessions->map(fn ($i) => [
                'position' => $i->position,
                'status' => $i->status,
                'score' => $i->overall_score,
                'report' => $i->report,
                'created_at' => $i->created_at->toDateTimeString(),
            ]),
            'job_applications' => $user->jobApplications->map(fn ($j) => [
                'company' => $j->company,
                'position' => $j->position,
                'status' => $j->status,
                'applied_at' => $j->applied_at?->toDateString(),
            ]),
            'export_time' => now()->toDateTimeString(),
        ];

        $filename = 'user_data_'.$user->id.'_'.now()->format('Ymd_His').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * 注销账号（冷静期机制）
     */
    public function destroyAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string',
            'deletion_reason' => 'nullable|string|max:255',
            'deletion_feedback' => 'nullable|string|max:1000',
            'confirm_understand' => 'required|accepted',
            'confirm_data_loss' => 'required|accepted',
        ], [
            'confirm_understand.required' => '请确认您了解注销后果',
            'confirm_data_loss.required' => '请确认您了解数据将永久丢失',
        ]);

        $user = $request->user();

        // 验证密码
        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => '密码验证失败'])->withInput();
        }

        $blockingTaskSummary = $this->buildDestroyAccountBlockingTaskSummary((int) $user->id);
        if ($blockingTaskSummary !== null) {
            return back()
                ->withInput()
                ->with('warning', $blockingTaskSummary);
        }

        // 生成恢复令牌
        $recoveryToken = AccountRecoveryToken::generateForUser($user->id);

        // 设置冷静期（7天后删除）
        $deletionScheduledAt = now()->addDays(7);

        // 更新用户注销状态
        $user->update([
            'deletion_requested_at' => now(),
            'deletion_scheduled_at' => $deletionScheduledAt,
            'deletion_reason' => $request->deletion_reason,
            'deletion_feedback' => $request->deletion_feedback,
        ]);

        // 发送确认邮件
        try {
            Mail::to($user->email)->queue(new AccountDeletionRequested(
                $user,
                $recoveryToken,
                $deletionScheduledAt->format('Y年m月d日 H:i')
            ));
        } catch (\Exception $e) {
            // 邮件发送失败不影响注销流程
            report($e);
        }

        Auth::logout();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('warning', '您的账号注销申请已提交，账号将在7天后永久删除。请查收邮件了解详情。');
    }

    private function buildDestroyAccountBlockingTaskSummary(int $userId): ?string
    {
        $processingExportTaskCount = ResumeExportTask::query()
            ->where('user_id', $userId)
            ->where('status', 'processing')
            ->count();

        $runningOptimizeSessionCount = ResumeOptimizeSession::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                ResumeOptimizeSession::STATUS_QUEUED,
                ResumeOptimizeSession::STATUS_RUNNING,
                ResumeOptimizeSession::STATUS_APPLYING,
            ])
            ->count();

        $activeInterviewSessionCount = InterviewSession::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        if ($processingExportTaskCount === 0 && $runningOptimizeSessionCount === 0 && $activeInterviewSessionCount === 0) {
            return null;
        }

        $segments = [];
        if ($processingExportTaskCount > 0) {
            $segments[] = "PDF/DOCX 导出任务 {$processingExportTaskCount} 个";
        }
        if ($runningOptimizeSessionCount > 0) {
            $segments[] = "简历优化任务 {$runningOptimizeSessionCount} 个";
        }
        if ($activeInterviewSessionCount > 0) {
            $segments[] = "面试会话 {$activeInterviewSessionCount} 个";
        }

        return '检测到进行中的任务（'.implode('，', $segments).'），请先等待完成或手动结束后再注销账号。';
    }

    /**
     * 恢复账号（取消注销）
     */
    public function recoverAccount(Request $request, string $token): RedirectResponse
    {
        $ipKey = 'account-recover:'.$request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return redirect()->route('login')
                ->with('error', "尝试次数过多，请 {$seconds} 秒后再试。");
        }
        RateLimiter::hit($ipKey, 300);

        $recoveryToken = AccountRecoveryToken::findByRawToken($token);

        if (! $recoveryToken || ! $recoveryToken->isValid()) {
            return redirect()->route('login')
                ->with('error', '恢复链接已过期或无效');
        }

        $user = $recoveryToken->user;

        // Require re-authentication for recovery: user must be logged in as the token owner
        if (! $request->user() || $request->user()->id !== $user->id) {
            if ($request->user()) {
                // Wrong user is logged in
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // Redirect to login, then back to recovery after auth
            return redirect()->guest(route('account.recover', ['token' => $token]));
        }

        $user->update([
            'deletion_requested_at' => null,
            'deletion_scheduled_at' => null,
            'deletion_reason' => null,
            'deletion_feedback' => null,
        ]);

        $recoveryToken->markAsUsed();

        RateLimiter::clear($ipKey);

        return redirect()->route('login')
            ->with('success', '账号恢复成功，请重新登录');
    }
}
