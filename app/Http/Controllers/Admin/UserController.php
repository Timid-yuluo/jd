<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Console\Commands\PurgeAccountDeletions;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\Admin\SystemSettingService;
use App\Services\Membership\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->withCount(['resumes', 'interviewSessions', 'jobApplications'])->with('roles');

        if ($search = $request->input('search')) {
            $safeSearch = escapeLike($search);
            $query->where(function ($q) use ($safeSearch): void {
                $q->where('name', 'like', "%{$safeSearch}%")
                    ->orWhere('email', 'like', "%{$safeSearch}%")
                    ->orWhere('school', 'like', "%{$safeSearch}%")
                    ->orWhere('major', 'like', "%{$safeSearch}%");
            });
        }

        if ($role = $request->input('role')) {
            match ($role) {
                'admin' => $query->where('is_admin', true),
                'user' => $query->where('is_admin', false),
                default => null,
            };
        }

        if ($accountStatus = $request->input('account_status')) {
            match ($accountStatus) {
                'active' => $query->where('is_suspended', false)->whereNull('deletion_scheduled_at'),
                'suspended' => $query->where('is_suspended', true),
                'pending_deletion' => $query->whereNotNull('deletion_scheduled_at'),
                default => null,
            };
        }

        if ($planSlug = $request->input('plan')) {
            $query->where('current_plan_slug', $planSlug);
        }

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'name' => $query->orderBy('name'),
            'resumes' => $query->orderByDesc('resumes_count'),
            'last_login' => $query->orderByDesc('last_login_at'),
            default => $query->latest('id'),
        };

        $users = $query->paginate((int) config('ui.pagination.admin_list', 15))->appends($request->only([
            'search', 'role', 'account_status', 'sort', 'plan', 'per_page',
        ]));

        $stats = Cache::remember('admin:users:stats', (int) config('cache_ttl.ttl.admin_stats', 120), function () {
            return User::selectRaw('
                count(*) as total,
                sum(case when date(created_at) = curdate() then 1 else 0 end) as today,
                sum(case when is_admin = 1 then 1 else 0 end) as admins,
                sum(case when date(last_login_at) = curdate() then 1 else 0 end) as active_today,
                sum(case when is_suspended = 1 then 1 else 0 end) as suspended,
                sum(case when current_plan_slug != \'free\' and current_plan_slug is not null then 1 else 0 end) as paid
            ')->first()->toArray();
        });

        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']);

        return view('admin.users.index', compact('users', 'stats', 'plans'));
    }

    public function create(): View
    {
        $roles = Role::query()->pluck('name', 'id');

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $editor = $request->user();
        $passwordMinLength = $this->resolvePasswordMinLength();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:'.$passwordMinLength,
            'school' => 'nullable|string|max:100',
            'major' => 'nullable|string|max:100',
            'email_verified' => 'nullable|in:0,1',
            'roles' => 'nullable|array',
        ]);

        if ($request->boolean('is_admin') && (! $editor || ! $editor->hasRole('super-admin'))) {
            unset($validated['is_admin']);
        }

        $roles = $request->input('roles', []);
        $emailVerified = $request->input('email_verified', '1');
        unset($validated['email_verified'], $validated['roles']);

        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = $emailVerified === '1' ? now() : null;

        $user = User::create($validated);

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        $isAdmin = $user->hasRole('super-admin');
        $user->forceFill(['is_admin' => $isAdmin])->save();

        Cache::forget('admin:users:stats');

        return redirect()->route('admin.users.index')->with('success', '用户已创建。');
    }

    public function passwordForm(): View
    {
        return view('admin.profile.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $passwordMinLength = $this->resolvePasswordMinLength();
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:'.$passwordMinLength.'|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => '当前密码不正确']);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', '密码已更新。');
    }

    public function show(User $user): View
    {
        $user->load([
            'resumes' => fn ($q) => $q->latest()->limit((int) config('ui.limit.related_items', 10)),
            'interviewSessions' => fn ($q) => $q->latest()->limit((int) config('ui.limit.related_items', 10)),
            'jobApplications' => fn ($q) => $q->latest()->limit((int) config('ui.limit.related_items', 10)),
            'roles',
            'activeSubscription.plan',
            'subscriptions.plan',
            'oauthAccounts',
            'loginHistories' => fn ($q) => $q->latest()->limit(20),
        ])->loadCount(['resumes', 'interviewSessions', 'jobApplications', 'usageLogs']);

        $tokenStats = $user->usageLogs()
            ->selectRaw('
                COALESCE(SUM(prompt_tokens), 0) + COALESCE(SUM(completion_tokens), 0) as total_tokens,
                COALESCE(SUM(cost_micros), 0) as total_cost
            ')
            ->first();

        $stats = [
            'resumes' => $user->resumes_count,
            'interviews' => $user->interview_sessions_count,
            'applications' => $user->job_applications_count,
            'usage_logs' => $user->usage_logs_count,
            'total_tokens' => (int) ($tokenStats?->total_tokens ?? 0),
            'total_cost' => (int) ($tokenStats?->total_cost ?? 0),
        ];

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);
        $activeSubscription = $user->activeSubscription;

        $userCredits = UserCredit::where('user_id', $user->id)
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END, expires_at ASC')
            ->limit(50)
            ->get();

        $loginHistories = $user->loginHistories;

        return view('admin.users.show', compact('user', 'stats', 'plans', 'activeSubscription', 'userCredits', 'loginHistories'));
    }

    public function edit(User $user): View
    {
        $roles = Role::query()->pluck('name', 'id');

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $editor = $request->user();
        $passwordMinLength = $this->resolvePasswordMinLength();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:'.$passwordMinLength,
            'school' => 'nullable|string|max:100',
            'major' => 'nullable|string|max:100',
            'email_verified' => 'nullable|in:0,1',
            'roles' => 'nullable|array',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $emailVerified = $request->input('email_verified');
        unset($validated['email_verified']);

        if ($emailVerified === '1' && ! $user->email_verified_at) {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '0') {
            $validated['email_verified_at'] = null;
        }

        $roles = $request->input('roles', []);
        unset($validated['roles']);

        $user->update($validated);

        if (! empty($roles) || $request->has('roles')) {
            if (in_array('super-admin', $roles, true) && (! $editor || ! $editor->hasRole('super-admin'))) {
                $roles = array_diff($roles, ['super-admin']);
            }

            $user->syncRoles($roles);
            $user->forceFill(['is_admin' => $user->hasRole('super-admin')])->save();
        }

        Cache::forget('admin:users:stats');

        return redirect()->route('admin.users.show', $user)->with('success', '用户信息已更新。');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', '不能删除当前登录账户。');
        }

        DB::transaction(function () use ($user): void {
            app(PurgeAccountDeletions::class)->purgeUserData($user);
        });

        Cache::forget('admin:users:stats');

        return redirect()->route('admin.users.index')->with('success', '用户及关联数据已永久删除。');
    }

    public function batchDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:users,id',
        ]);

        $ids = $validated['ids'];

        $adminIds = User::where('is_admin', true)->pluck('id')->toArray();
        $ids = array_diff($ids, $adminIds);
        $ids = array_diff($ids, [auth()->id()]);

        if (empty($ids)) {
            return redirect()->route('admin.users.index')->with('warning', '没有可删除的用户。');
        }

        $purger = app(PurgeAccountDeletions::class);
        $count = 0;

        foreach ($ids as $id) {
            $user = User::find($id);
            if ($user === null) {
                continue;
            }

            DB::transaction(function () use ($purger, $user): void {
                $purger->purgeUserData($user);
            });
            $count++;
        }

        Cache::forget('admin:users:stats');

        return redirect()->route('admin.users.index')->with('success', sprintf('已永久删除 %d 个用户及关联数据。', $count));
    }

    public function batchSuspend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:users,id',
        ]);

        $ids = $validated['ids'];
        $adminIds = User::where('is_admin', true)->pluck('id')->toArray();
        $ids = array_diff($ids, $adminIds, [auth()->id()]);

        if (empty($ids)) {
            return redirect()->route('admin.users.index')->with('warning', '没有可封禁的用户。');
        }

        $count = 0;
        foreach ($ids as $id) {
            $user = User::find($id);
            if ($user && ! $user->is_suspended) {
                $user->suspend(auth()->user(), '管理员批量封禁');
                $count++;
            }
        }

        Cache::forget('admin:users:stats');

        return redirect()->route('admin.users.index')->with('success', sprintf('已封禁 %d 个用户。', $count));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = User::query()->with('roles');

        if ($search = $request->input('search')) {
            $safeSearch = escapeLike($search);
            $query->where(function ($q) use ($safeSearch): void {
                $q->where('name', 'like', "%{$safeSearch}%")
                    ->orWhere('email', 'like', "%{$safeSearch}%")
                    ->orWhere('school', 'like', "%{$safeSearch}%")
                    ->orWhere('major', 'like', "%{$safeSearch}%");
            });
        }

        if ($role = $request->input('role')) {
            match ($role) {
                'admin' => $query->where('is_admin', true),
                'user' => $query->where('is_admin', false),
                default => null,
            };
        }

        if ($accountStatus = $request->input('account_status')) {
            match ($accountStatus) {
                'active' => $query->where('is_suspended', false)->whereNull('deletion_scheduled_at'),
                'suspended' => $query->where('is_suspended', true),
                'pending_deletion' => $query->whereNotNull('deletion_scheduled_at'),
                default => null,
            };
        }

        if ($planSlug = $request->input('plan')) {
            $query->where('current_plan_slug', $planSlug);
        }

        $filename = 'users_export_'.now()->format('Y_m_d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['ID', '名称', '邮箱', '邮箱验证', '角色', '学校', '专业', '套餐', '状态', '注册时间', '最近登录', '最近登录IP']);

            $query->chunk(200, function ($users) use ($handle): void {
                foreach ($users as $user) {
                    $roleNames = $user->roles->pluck('name')->implode(',');
                    $status = $user->is_suspended ? '已封禁' : ($user->isPendingDeletion() ? '待注销' : '正常');
                    fputcsv($handle, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->email_verified_at ? '是' : '否',
                        $roleNames ?: '用户',
                        $user->school ?? '',
                        $user->major ?? '',
                        $user->currentPlan()->name ?? '免费版',
                        $status,
                        $user->created_at->format('Y-m-d H:i:s'),
                        $user->last_login_at?->format('Y-m-d H:i:s') ?? '',
                        $user->last_login_ip ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, $headers);
    }

    public function editRoles(User $user): View
    {
        $user->load('roles');
        $roles = Role::query()->pluck('name', 'id');

        return view('admin.users.roles', compact('user', 'roles'));
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'roles' => 'nullable|array',
        ]);

        $roles = $request->input('roles', []);
        $editor = $request->user();

        if (in_array('super-admin', $roles, true) && (! $editor || ! $editor->hasRole('super-admin'))) {
            $roles = array_diff($roles, ['super-admin']);
            Log::warning('Non-super-admin attempted to assign super-admin role', [
                'editor_id' => $editor?->id,
                'target_id' => $user->id,
            ]);
        }

        if (! in_array('super-admin', $roles, true) && $user->hasRole('super-admin') && (! $editor || ! $editor->hasRole('super-admin'))) {
            $roles[] = 'super-admin';
            Log::warning('Non-super-admin attempted to remove super-admin role', [
                'editor_id' => $editor?->id,
                'target_id' => $user->id,
            ]);
        }

        $user->syncRoles($roles);

        $user->update(['is_admin' => $user->hasRole('super-admin')]);

        return redirect()->route('admin.users.show', $user)->with('success', '用户角色已更新。');
    }

    public function updateMembership(Request $request, User $user, SubscriptionService $subscriptionService): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
        $subscriptionService->activate($user, $plan, (string) $validated['billing_cycle']);

        return redirect()->route('admin.users.show', $user)->with('success', "已调整会员套餐为：{$plan->name}");
    }

    public function cancelMembership(User $user): RedirectResponse
    {
        DB::transaction(function () use ($user): void {
            $user->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'expires_at' => now(),
                ]);

            $user->setPlan('free');
        });

        return redirect()->route('admin.users.show', $user)->with('success', '会员已取消，用户已降级为免费版。');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'suspended_reason' => 'nullable|string|max:255',
        ]);

        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->route('admin.users.show', $user)->with('error', '不能封禁当前登录账户。');
        }

        $reason = trim((string) ($validated['suspended_reason'] ?? ''));
        $user->suspend(auth()->user(), $reason);

        Cache::forget('admin:users:stats');

        return redirect()->route('admin.users.show', $user)->with('success', '用户已封禁。');
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->unsuspend();

        Cache::forget('admin:users:stats');

        return redirect()->route('admin.users.show', $user)->with('success', '用户已解除封禁。');
    }

    private function resolvePasswordMinLength(): int
    {
        $raw = app(SystemSettingService::class)->get('password_min_length', '8');
        $value = (int) $raw;

        if ($value < 6) {
            return 8;
        }

        return min($value, 32);
    }
}
