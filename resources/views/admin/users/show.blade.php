@extends('layouts.admin')

@section('title', '用户详情')
@section('page-pretitle', '用户中心')
@section('page-title', '用户详情')

@section('content')
<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="{{ \Illuminate\Support\Str::avatarSvg($user->email, 96) }}" class="avatar avatar-xl mb-3 rounded" alt="">
                <h3 class="mb-1">{{ $user->name }}</h3>
                <div class="text-secondary mb-2">{{ $user->email }}</div>
                <div class="mb-3">
                    @if($user->isPendingDeletion())
                        <span class="badge bg-yellow-lt text-yellow-fg">待注销</span>
                    @elseif($user->is_suspended)
                        <span class="badge bg-red-lt text-red-fg">已封禁</span>
                    @else
                        <span class="badge bg-green-lt text-green-fg">正常</span>
                    @endif
                    @if($user->email_verified_at)
                        <span class="badge bg-success-lt text-success-fg"><i class="ti ti-circle-check me-1"></i>已验证</span>
                    @else
                        <span class="badge bg-secondary-lt"><i class="ti ti-circle-x me-1"></i>未验证</span>
                    @endif
                </div>
                <div class="mb-2">
                    @foreach($user->roles as $role)
                        @if($role->name === 'super-admin')
                            <span class="badge bg-red text-red-fg">超级管理员</span>
                        @elseif($role->name === 'admin')
                            <span class="badge bg-orange text-orange-fg">管理员</span>
                        @else
                            <span class="badge bg-azure text-azure-fg">{{ $role->name }}</span>
                        @endif
                    @endforeach
                    @if($user->roles->isEmpty())
                        <span class="badge bg-secondary-lt">普通用户</span>
                    @endif
                </div>
                <div class="mt-3 d-flex flex-wrap gap-1 justify-content-center">
                    @can('plans.manage')
                        <a href="{{ route('admin.users.activate-plan', $user) }}" class="btn btn-sm btn-success"><i class="ti ti-crown me-1"></i>赠送开通</a>
                        <a href="{{ route('admin.users.grant-credits', $user) }}" class="btn btn-sm btn-azure"><i class="ti ti-credit-card me-1"></i>赠送次卡</a>
                    @endcan
                    @can('users.edit')
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary"><i class="ti ti-edit me-1"></i>编辑</a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">基本信息</h3>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">ID</span>
                    <span>{{ $user->id }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">学校</span>
                    <span>{{ $user->school ?? '-' }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">专业</span>
                    <span>{{ $user->major ?? '-' }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">注册时间</span>
                    <span>{{ $user->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">最近登录</span>
                    <span>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '从未' }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">登录 IP</span>
                    <span>{{ $user->last_login_ip ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">OAuth 绑定</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($user->oauthAccounts as $oauth)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="ti {{ $oauth->provider === 'wechat' ? 'ti-brand-wechat text-green' : 'ti-brand-github' }} me-1"></i>
                            {{ $oauth->provider === 'wechat' ? '微信' : ucfirst($oauth->provider) }}
                        </span>
                        <span class="text-secondary small">{{ $oauth->bound_at?->format('Y-m-d') }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-secondary">未绑定第三方账号</div>
                @endforelse
            </div>
        </div>

        @if($user->is_suspended || $user->isPendingDeletion())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">风控信息</h3>
            </div>
            <div class="card-body">
                @if($user->is_suspended)
                    <div class="mb-2">
                        <span class="badge bg-red-lt text-red-fg">已封禁</span>
                        @if($user->suspended_at)<span class="text-secondary ms-1">{{ $user->suspended_at->format('Y-m-d H:i') }}</span>@endif
                    </div>
                    @if($user->suspended_reason)<div class="text-secondary">原因：{{ $user->suspended_reason }}</div>@endif
                    @if($user->suspended_by)<div class="text-secondary small">操作人 ID：{{ $user->suspended_by }}</div>@endif
                @endif
                @if($user->isPendingDeletion())
                    <div class="mb-2">
                        <span class="badge bg-yellow-lt text-yellow-fg">待注销</span>
                    </div>
                    <div class="text-secondary">申请时间：{{ $user->deletion_requested_at?->format('Y-m-d H:i') }}</div>
                    <div class="text-secondary">计划删除：{{ $user->deletion_scheduled_at?->format('Y-m-d H:i') }}</div>
                    @if($user->deletion_reason)<div class="text-secondary mt-1">原因：{{ $user->deletion_reason }}</div>@endif
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="row row-cards mb-3">
            <div class="col-4 col-sm-3">
                <div class="card card-sm">
                    <div class="card-body text-center">
                        <div class="h2 mb-0">{{ $stats['resumes'] }}</div>
                        <div class="text-secondary">简历</div>
                    </div>
                </div>
            </div>
            <div class="col-4 col-sm-3">
                <div class="card card-sm">
                    <div class="card-body text-center">
                        <div class="h2 mb-0">{{ $stats['interviews'] }}</div>
                        <div class="text-secondary">面试</div>
                    </div>
                </div>
            </div>
            <div class="col-4 col-sm-3">
                <div class="card card-sm">
                    <div class="card-body text-center">
                        <div class="h2 mb-0">{{ $stats['applications'] }}</div>
                        <div class="text-secondary">投递</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-3">
                <div class="card card-sm">
                    <div class="card-body text-center">
                        <div class="h2 mb-0">{{ $stats['usage_logs'] }}</div>
                        <div class="text-secondary">AI 调用</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item"><a href="#tab-resumes" class="nav-link active" data-bs-toggle="tab">简历 ({{ $stats['resumes'] }})</a></li>
                    <li class="nav-item"><a href="#tab-interviews" class="nav-link" data-bs-toggle="tab">面试 ({{ $stats['interviews'] }})</a></li>
                    <li class="nav-item"><a href="#tab-applications" class="nav-link" data-bs-toggle="tab">投递 ({{ $stats['applications'] }})</a></li>
                    <li class="nav-item"><a href="#tab-credits" class="nav-link" data-bs-toggle="tab">次卡</a></li>
                    <li class="nav-item"><a href="#tab-subscriptions" class="nav-link" data-bs-toggle="tab">订阅</a></li>
                    <li class="nav-item"><a href="#tab-logins" class="nav-link" data-bs-toggle="tab">登录记录</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active show" id="tab-resumes">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>标题</th><th>目标岗位</th><th>ATS 分数</th><th>创建时间</th><th></th></tr></thead>
                                <tbody>
                                    @forelse($user->resumes as $resume)
                                    <tr>
                                        <td>{{ $resume->title }}</td>
                                        <td>{{ $resume->target_job ?? '-' }}</td>
                                        <td>
                                            @if($resume->ats_score)
                                                <span class="badge {{ $resume->ats_score >= 80 ? 'bg-green-lt' : ($resume->ats_score >= 60 ? 'bg-yellow-lt' : 'bg-red-lt') }}">{{ $resume->ats_score }}</span>
                                            @else
                                                <span class="text-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $resume->created_at->format('Y-m-d H:i') }}</td>
                                        <td><a href="{{ route('admin.resumes.show', $resume) }}" class="btn btn-sm btn-outline-info">查看</a></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-secondary">暂无简历</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-interviews">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>岗位</th><th>公司</th><th>类型</th><th>状态</th><th>得分</th><th>创建时间</th></tr></thead>
                                <tbody>
                                    @forelse($user->interviewSessions as $session)
                                    <tr>
                                        <td>{{ $session->position ?? '-' }}</td>
                                        <td>{{ $session->company ?? '-' }}</td>
                                        <td>{{ $session->type ?? '-' }}</td>
                                        <td>
                                            @switch($session->status)
                                                @case('completed') <span class="badge bg-green-lt">已完成</span> @break
                                                @case('in_progress') <span class="badge bg-azure-lt">进行中</span> @break
                                                @default <span class="badge bg-secondary-lt">{{ $session->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $session->overall_score ?? '-' }}</td>
                                        <td>{{ $session->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-secondary">暂无面试</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-applications">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>公司</th><th>岗位</th><th>状态</th><th>渠道</th><th>截止时间</th><th>创建时间</th></tr></thead>
                                <tbody>
                                    @forelse($user->jobApplications as $app)
                                    <tr>
                                        <td>{{ $app->company }}</td>
                                        <td>{{ $app->position }}</td>
                                        <td>
                                            @switch($app->status)
                                                @case('applied') <span class="badge bg-azure-lt">已投递</span> @break
                                                @case('interview') <span class="badge bg-yellow-lt">面试中</span> @break
                                                @case('offer') <span class="badge bg-green-lt">已录用</span> @break
                                                @case('rejected') <span class="badge bg-red-lt">已拒绝</span> @break
                                                @default <span class="badge bg-secondary-lt">{{ $app->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $app->channel ?? '-' }}</td>
                                        <td>{{ $app->deadline?->format('Y-m-d') ?? '-' }}</td>
                                        <td>{{ $app->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-secondary">暂无投递</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-credits">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>类型</th><th>来源</th><th>剩余</th><th>过期时间</th><th>状态</th></tr></thead>
                                <tbody>
                                    @forelse($userCredits as $credit)
                                    <tr>
                                        <td>
                                            <span class="badge {{ $credit->quota_key ? 'bg-azure-lt' : 'bg-purple-lt' }}">
                                                {{ \App\Models\UserCredit::quotaLabel($credit->quota_key) }}
                                            </span>
                                        </td>
                                        <td>{{ $credit->packName() ?? '系统赠送' }}</td>
                                        <td><strong>{{ $credit->remaining }}</strong></td>
                                        <td>{{ $credit->expires_at?->format('Y-m-d H:i') ?? '永不过期' }}</td>
                                        <td>
                                            @if($credit->isExpired())
                                                <span class="badge bg-red-lt text-red-fg">已过期</span>
                                            @elseif($credit->remaining <= 0)
                                                <span class="badge bg-secondary-lt">已用完</span>
                                            @else
                                                <span class="badge bg-green-lt text-green-fg">可用</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-secondary">暂无次卡</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-subscriptions">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>套餐</th><th>周期</th><th>状态</th><th>开始时间</th><th>到期时间</th><th>取消时间</th></tr></thead>
                                <tbody>
                                    @forelse($user->subscriptions as $sub)
                                    <tr>
                                        <td>{{ $sub->plan->name ?? '-' }}</td>
                                        <td>{{ $sub->billing_cycle === 'yearly' ? '年付' : '月付' }}</td>
                                        <td>
                                            @switch($sub->status)
                                                @case('active') <span class="badge bg-green-lt">生效中</span> @break
                                                @case('cancelled') <span class="badge bg-yellow-lt">已取消</span> @break
                                                @case('expired') <span class="badge bg-secondary-lt">已过期</span> @break
                                                @default <span class="badge bg-secondary-lt">{{ $sub->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $sub->starts_at?->format('Y-m-d') ?? '-' }}</td>
                                        <td>{{ $sub->expires_at?->format('Y-m-d') ?? '-' }}</td>
                                        <td>{{ $sub->cancelled_at?->format('Y-m-d') ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-secondary">暂无订阅记录</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-logins">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>时间</th><th>IP</th><th>设备</th><th>浏览器</th><th>位置</th><th>结果</th></tr></thead>
                                <tbody>
                                    @forelse($loginHistories as $login)
                                    <tr>
                                        <td>{{ $login->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                        <td><code>{{ $login->ip_address }}</code></td>
                                        <td><i class="ti {{ $login->device_icon ?? 'ti-device-desktop' }} me-1"></i>{{ $login->device_type ?? '-' }}</td>
                                        <td>{{ $login->browser_name ?? '-' }}</td>
                                        <td>{{ $login->location ?? '-' }}</td>
                                        <td>
                                            @if($login->is_success)
                                                <span class="badge bg-green-lt">成功</span>
                                            @else
                                                <span class="badge bg-red-lt">失败</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-secondary">暂无登录记录</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">会员管理</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="text-secondary me-2">当前套餐：</span>
                            <span class="badge bg-azure-lt text-azure-fg">{{ $user->currentPlan()->name ?? '免费版' }}</span>
                            @if($activeSubscription?->expires_at)
                                <span class="text-secondary ms-1">到期：{{ $activeSubscription->expires_at->format('Y-m-d') }}</span>
                            @endif
                        </div>
                        @can('plans.manage')
                            <form action="{{ route('admin.users.update-membership', $user) }}" method="POST" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-5">
                                    <label class="form-label">套餐</label>
                                    <select name="plan_id" class="form-select" required>
                                        @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}" @selected($plan->slug === $user->current_plan_slug)>{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">计费周期</label>
                                    <select name="billing_cycle" class="form-select" required>
                                        <option value="monthly">月付</option>
                                        <option value="yearly">年付</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">调整会员</button>
                                </div>
                            </form>
                            <form action="{{ route('admin.users.cancel-membership', $user) }}" method="POST" class="mt-3" data-confirm-submit="确认取消该用户会员并降级为免费版吗？">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="ti ti-x me-1"></i>取消会员</button>
                            </form>
                        @else
                            <div class="text-secondary">无会员管理权限。</div>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">账户风控</h3>
                    </div>
                    <div class="card-body">
                        @can('users.edit')
                            @if($user->is_suspended)
                                <div class="mb-3">
                                    <span class="badge bg-red-lt text-red-fg">当前状态：已封禁</span>
                                    @if($user->suspended_reason)<div class="text-secondary mt-1">原因：{{ $user->suspended_reason }}</div>@endif
                                </div>
                                <form action="{{ route('admin.users.unsuspend', $user) }}" method="POST" data-confirm-submit="确认解除该用户封禁吗？">
                                    @csrf
                                    <button type="submit" class="btn btn-success"><i class="ti ti-circle-check me-1"></i>解除封禁</button>
                                </form>
                            @else
                                <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="row g-2 align-items-end" data-confirm-submit="确认封禁该用户吗？">
                                    @csrf
                                    <div class="col-md-8">
                                        <label class="form-label">封禁原因（可选）</label>
                                        <input type="text" name="suspended_reason" class="form-control" maxlength="255" placeholder="例如：违规使用、恶意请求">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-danger w-100"><i class="ti ti-ban me-1"></i>封禁用户</button>
                                    </div>
                                </form>
                            @endif
                        @else
                            <div class="text-secondary">无账户风控权限。</div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">AI 用量统计</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary">AI Token 消耗</span>
                            <span class="fw-medium">{{ number_format($stats['total_tokens']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">AI 费用 (微单位)</span>
                            <span class="fw-medium">{{ number_format($stats['total_cost']) }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary">总调用次数</span>
                            <span class="fw-medium">{{ number_format($stats['usage_logs']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">平均每次 Token</span>
                            <span class="fw-medium">{{ $stats['usage_logs'] > 0 ? number_format((int) ($stats['total_tokens'] / $stats['usage_logs'])) : 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>返回用户列表</a>
</div>
@endsection
