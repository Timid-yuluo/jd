@extends('layouts.user')

@section('title', '个人资料')

@section('page-pretitle', '用户中心')
@section('page-title', '个人资料')

@section('content')
<div data-route-user-profile-update="{{ route('user.profile.update') }}" data-route-user-profile-password-update="{{ route('user.profile.password.update') }}" data-json-activitystats-login="{{ urlencode(json_encode($activityStats['login'])) }}" data-json-activitystats-resume="{{ urlencode(json_encode($activityStats['resume'])) }}" data-json-activitystats-interview="{{ urlencode(json_encode($activityStats['interview'])) }}" data-json-activitystats-labels="{{ urlencode(json_encode($activityStats['labels'])) }}">
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                        <img src="{{ \Illuminate\Support\Str::avatarSvg($user->email, 80) }}" class="rounded-circle" width="80" height="80" alt="" style="box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    </div>
                <h3>{{ $user->name }}</h3>
                <p class="text-secondary">{{ $user->email }}</p>

                {{-- 邮箱验证状态 --}}
                <div class="mb-3">
                    @if($user->email_verified_at)
                        <span class="badge bg-success-lt text-success">
                            <i class="ti ti-check me-1"></i>邮箱已验证
                        </span>
                        <div class="small text-secondary mt-1">
                            验证时间：{{ $user->email_verified_at->format('Y-m-d H:i') }}
                        </div>
                    @else
                        <span class="badge bg-warning-lt text-warning">
                            <i class="ti ti-alert-triangle me-1"></i>邮箱未验证
                        </span>
                        <form action="{{ route('user.verification.send') }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-mail me-1"></i>发送验证邮件
                            </button>
                        </form>
                        <div class="small text-secondary mt-1">
                            验证后可接收系统通知
                        </div>
                    @endif
                </div>

                <p class="text-secondary small">
                    @if($user->school)
                        {{ $user->school }}
                    @endif
                    @if($user->major)
                        / {{ $user->major }}
                    @endif
                </p>

                {{-- 数据概览 --}}
                <div class="border-top pt-3 mt-3">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="fw-bold text-primary fs-5">{{ $user->resumes()->count() }}</div>
                            <div class="small text-secondary">简历</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-success fs-5">{{ $user->interviewSessions()->count() }}</div>
                            <div class="small text-secondary">面试</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-info fs-5">{{ $user->jobApplications()->count() }}</div>
                            <div class="small text-secondary">申请</div>
                        </div>
                    </div>
                </div>

                {{-- 账号信息 --}}
                <div class="border-top pt-3 mt-3">
                    <div class="small text-secondary mb-1">
                        <i class="ti ti-calendar me-1"></i>注册时间：{{ $user->created_at->format('Y-m-d') }}
                    </div>
                    <div class="small text-secondary">
                        <i class="ti ti-clock me-1"></i>最后登录：{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '暂无记录' }}
                    </div>
                </div>

                {{-- 快捷入口 --}}
                <div class="d-grid gap-2 mt-4">
                    <a href="{{ route('user.notification-preferences.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-bell me-1"></i>通知偏好设置
                    </a>
                </div>
            </div>
        </div>

        {{-- 近30天活跃度 --}}
        <div class="card mt-3">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="ti ti-chart-bar me-2 text-primary"></i>近30天活跃度
                </h4>
            </div>
            <div class="card-body">
                <div id="activityChart" style="height: 150px;"></div>
                <div class="row text-center mt-3">
                    <div class="col-4">
                        <div class="text-success fw-bold">{{ array_sum($activityStats['login']) }}</div>
                        <div class="small text-secondary">登录</div>
                    </div>
                    <div class="col-4">
                        <div class="text-primary fw-bold">{{ array_sum($activityStats['resume']) }}</div>
                        <div class="small text-secondary">简历更新</div>
                    </div>
                    <div class="col-4">
                        <div class="text-warning fw-bold">{{ array_sum($activityStats['interview']) }}</div>
                        <div class="small text-secondary">面试</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @php
            $activeProfileTab = 'profile';
            $requestedTab = request()->query('tab');
            if (in_array($requestedTab, ['profile', 'membership', 'security'], true)) {
                $activeProfileTab = $requestedTab;
            }
            if ($errors->passwordUpdate->any()) {
                $activeProfileTab = 'security';
            }
            $bindProviders = [
                [
                    'key' => 'alipay',
                    'enabled' => (bool) ($authProviderAlipayEnabled ?? false),
                    'bound' => (bool) ($isAlipayBound ?? false),
                    'name' => '支付宝',
                    'desc' => '绑定后可使用支付宝登录',
                    'icon' => 'ti ti-brand-alipay text-primary fs-4',
                    'iconWrap' => 'bg-primary-lt',
                    'route' => 'user.bindings.alipay.redirect',
                ],
                [
                    'key' => 'github',
                    'enabled' => (bool) ($authProviderGithubEnabled ?? false),
                    'bound' => (bool) ($isGithubBound ?? false),
                    'name' => 'GitHub',
                    'desc' => '绑定后可使用 GitHub 登录',
                    'icon' => 'ti ti-brand-github fs-4',
                    'iconWrap' => 'bg-dark-lt',
                    'route' => 'user.bindings.github.redirect',
                ],
            ];
            $activeBindProviders = array_values(array_filter($bindProviders, static fn (array $provider): bool => $provider['enabled'] === true && \Illuminate\Support\Facades\Route::has($provider['route'])));
            $membership = $membershipSummary ?? [];
            $membershipPlan = $membership['plan'] ?? null;
            $membershipPeriod = $membership['period'] ?? now()->format('Y-m');
            $membershipUsages = is_array($membership['usages'] ?? null) ? $membership['usages'] : [];
            $membershipSub = $membership['active_subscription'] ?? null;
        @endphp

        <div class="card">
            <div class="card-header border-0 pb-0">
                <ul class="nav nav-pills card-header-pills" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeProfileTab === 'profile' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#profile-tab-basic" type="button" role="tab">
                            <i class="ti ti-user me-1"></i>基本资料
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeProfileTab === 'membership' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#profile-tab-membership" type="button" role="tab">
                            <i class="ti ti-crown me-1"></i>我的会员
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeProfileTab === 'security' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#profile-tab-security" type="button" role="tab">
                            <i class="ti ti-shield-check me-1"></i>账号安全
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body pt-3">
                <div class="tab-content">
                    <div class="tab-pane {{ $activeProfileTab === 'profile' ? 'active show' : '' }}" id="profile-tab-basic" role="tabpanel">
                        <form method="POST" action="{{ route('user.profile.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">姓名</label>
                                <input type="text" name="name" class="form-control @error('name', 'profileUpdate') is-invalid @enderror" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                                @error('name', 'profileUpdate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">邮箱</label>
                                <input type="email" name="email" class="form-control @error('email', 'profileUpdate') is-invalid @enderror" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                                @error('email', 'profileUpdate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">修改邮箱后将重置验证状态，需要重新验证。</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">学校</label>
                                        <input type="text" name="school" class="form-control @error('school', 'profileUpdate') is-invalid @enderror" value="{{ old('school', $user->school) }}" maxlength="255" autocomplete="organization">
                                        @error('school', 'profileUpdate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">专业</label>
                                        <input type="text" name="major" class="form-control @error('major', 'profileUpdate') is-invalid @enderror" value="{{ old('major', $user->major) }}" maxlength="255">
                                        @error('major', 'profileUpdate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i>保存修改
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane {{ $activeProfileTab === 'membership' ? 'active show' : '' }}" id="profile-tab-membership" role="tabpanel">
                        @if(empty($membershipUsages))
                            <div class="alert alert-warning mb-3">
                                <i class="ti ti-alert-circle me-1"></i>会员数据暂不可用，请稍后刷新重试。
                            </div>
                        @endif
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="text-secondary small">当前套餐</div>
                                        <div class="h3 mb-0">{{ $membershipPlan?->name ?? '免费版' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="text-secondary small">计费状态</div>
                                        <div class="h3 mb-0">{{ $membershipSub?->status === 'active' ? '生效中' : '免费使用中' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="text-secondary small">统计周期</div>
                                        <div class="h3 mb-0">{{ $membershipPeriod }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <a href="{{ route('user.membership.credits') }}" class="text-decoration-none">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="text-secondary small">通用次卡余额 <i class="ti ti-chevron-right ms-1"></i></div>
                                            <div class="h2 mb-0">{{ (int) ($membership['universal_credits'] ?? 0) }} 次</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('user.membership.credits') }}" class="text-decoration-none">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="text-secondary small">专用次卡余额 <i class="ti ti-chevron-right ms-1"></i></div>
                                            <div class="h2 mb-0">{{ (int) ($membership['special_credits'] ?? 0) }} 次</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 mb-3">
                            <div class="small text-secondary">
                                通用次卡可用于 AI 岗位定向优化、ATS 评分、关键词提取、AI 面试场次、面试评估额度、岗位匹配等功能；
                                专用次卡仅可用于对应功能。AI 面试会先消耗场次额度，提交回答时再消耗面试评估额度。
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>功能项</th>
                                        <th style="width: 120px;">已用</th>
                                        <th style="width: 120px;">限额</th>
                                        <th style="width: 140px;">剩余</th>
                                        <th>进度</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($membershipUsages as $usage)
                                        <tr>
                                            <td class="fw-medium">{{ $usage['label'] }}</td>
                                            <td>{{ $usage['used'] }}</td>
                                            <td>{{ $usage['unlimited'] ? '不限' : $usage['limit'] }}</td>
                                            <td>{{ $usage['unlimited'] ? '不限' : $usage['remaining'] }}</td>
                                            <td>
                                                @if($usage['unlimited'])
                                                    <span class="badge bg-success-lt">不限额度</span>
                                                @else
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar {{ $usage['percent'] >= 90 ? 'bg-danger' : ($usage['percent'] >= 70 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $usage['percent'] }}%"></div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <a href="{{ route('user.membership.pricing') }}" class="btn btn-outline-primary btn-sm">
                                <i class="ti ti-crown me-1"></i>升级套餐
                            </a>
                            <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-ticket me-1"></i>购买次卡
                            </a>
                            <a href="{{ route('user.membership.credits') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-ticket me-1"></i>我的次卡
                            </a>
                            <a href="{{ route('user.membership.orders') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-receipt me-1"></i>我的订单
                            </a>
                        </div>
                    </div>

                    <div class="tab-pane {{ $activeProfileTab === 'security' ? 'active show' : '' }}" id="profile-tab-security" role="tabpanel">
                        @if(($authAccountBindingEnabled ?? false) && $activeBindProviders !== [])
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="ti ti-link me-2 text-info"></i>账号绑定
                                    </h3>
                                </div>
                                <div class="card-body">
                                    @foreach($activeBindProviders as $provider)
                                        <div class="d-flex align-items-center justify-content-between py-2">
                                            <div class="d-flex align-items-center">
                                                <div class="{{ $provider['iconWrap'] }} rounded p-2 me-3">
                                                    <i class="{{ $provider['icon'] }}"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $provider['name'] }}</div>
                                                    <div class="small text-secondary">{{ $provider['bound'] ? '已绑定' : '未绑定' }} · {{ $provider['desc'] }}</div>
                                                </div>
                                            </div>
                                            @if($provider['bound'])
                                                @if($authBindingAllowUnbind ?? false)
                                                    @if($authBindingRequirePasswordConfirm ?? false)
                                                    <form method="POST" action="{{ route('user.bindings.disconnect', ['provider' => $provider['key']]) }}" class="unbind-form" data-provider="{{ $provider['name'] }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="password" value="">
                                                        <button type="button" class="btn btn-outline-danger btn-sm unbind-btn">解绑</button>
                                                    </form>
                                                    @else
                                                    <form method="POST" action="{{ route('user.bindings.disconnect', ['provider' => $provider['key']]) }}" data-app-confirm="确定解绑{{ $provider['name'] }}吗？">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">解绑</button>
                                                    </form>
                                                    @endif
                                                @else
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled>不可解绑</button>
                                                @endif
                                            @else
                                                <a href="{{ route($provider['route']) }}" class="btn btn-outline-primary btn-sm">去绑定</a>
                                            @endif
                                        </div>
                                        @if(!$loop->last)
                                            <hr class="my-3">
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-shield-check me-2 text-success"></i>账号安全
                                </h3>
                            </div>
                            <div class="card-body">
                                @if($hasNewDevice)
                                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                                        <i class="ti ti-alert-triangle fs-4 me-2"></i>
                                        <div>
                                            <strong>检测到新设备登录</strong><br>
                                            <small>IP: {{ request()->ip() }} · 时间: {{ now()->format('Y-m-d H:i') }}</small><br>
                                            <small>如果不是您本人操作，请立即修改密码。</small>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                                        <i class="ti ti-check fs-4 me-2"></i>
                                        <div>
                                            <strong>账号安全</strong><br>
                                            <small>近期无异常登录活动</small>
                                        </div>
                                    </div>
                                @endif

                                <h5 class="card-title mt-4 mb-3">最近登录</h5>
                                @if($loginHistories->isEmpty())
                                    <div class="text-secondary small">暂无登录记录</div>
                                @else
                                    <div class="list-group list-group-flush">
                                        @foreach($loginHistories as $history)
                                            <div class="list-group-item px-0 py-2">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-light rounded p-2 me-3">
                                                            <i class="ti {{ $history->device_icon }}"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium">{{ $history->browser_name }} · {{ $history->os }}</div>
                                                            <div class="small text-secondary">
                                                                {{ $history->ip_address }}
                                                                @if($loop->first)
                                                                    <span class="badge bg-success ms-1">当前</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="small text-secondary">{{ $history->created_at?->diffForHumans() ?? '时间未知' }}</div>
                                                        <div class="small text-secondary">{{ $history->created_at?->format('m-d H:i') ?? '--' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">修改密码</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('user.profile.password.update') }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="username" value="{{ $user->email }}" autocomplete="username" class="d-none" tabindex="-1" aria-hidden="true" readonly>

                                    <div class="mb-3">
                                        <label class="form-label">当前密码</label>
                                        <input type="password" name="current_password" class="form-control @error('current_password', 'passwordUpdate') is-invalid @enderror" autocomplete="current-password" required>
                                        @error('current_password', 'passwordUpdate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">新密码</label>
                                        <div class="input-group input-group-flat">
                                            <input type="password" name="password" id="newPassword" class="form-control @error('password', 'passwordUpdate') is-invalid @enderror" autocomplete="new-password" required>
                                            <span class="input-group-text">
                                                <button type="button" class="btn btn-link link-secondary p-0 border-0 profile-password-toggle" data-password-target="newPassword" title="显示/隐藏密码" aria-label="显示或隐藏新密码">
                                                    <i class="ti ti-eye" id="newPasswordIcon"></i>
                                                </button>
                                            </span>
                                        </div>
                                        @error('password', 'passwordUpdate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="mt-2" id="passwordStrength">
                                            <div class="progress progress-sm" style="height: 4px;">
                                                <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <small class="text-muted" id="passwordStrengthText">密码强度：未输入</small>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">确认新密码</label>
                                        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                                    </div>

                                    <div class="form-footer">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="ti ti-lock-check me-1"></i>更新密码
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card border-danger">
                            <div class="card-header bg-danger-lt">
                                <h3 class="card-title text-danger">
                                    <i class="ti ti-alert-triangle me-2"></i>危险操作
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="d-flex flex-column h-100">
                                            <div class="fw-medium mb-1">
                                                <i class="ti ti-logout me-1"></i>退出所有设备
                                            </div>
                                            <p class="small text-secondary mb-2 flex-grow-1">强制退出所有已登录的设备，包括当前设备。</p>
                                            <form action="{{ route('user.profile.logout-all') }}" method="POST" data-app-confirm="确定要退出所有设备吗？您需要重新登录。">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                                    退出所有设备
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="d-flex flex-column h-100">
                                            <div class="fw-medium mb-1">
                                                <i class="ti ti-download me-1"></i>导出个人数据
                                            </div>
                                            <p class="small text-secondary mb-2 flex-grow-1">下载您的所有个人数据（简历、面试记录等）。</p>
                                            <form action="{{ route('user.profile.export-data') }}" method="POST" class="d-inline w-100" id="profileExportForm">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info btn-sm w-100">
                                                    导出数据
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="d-flex flex-column h-100">
                                            <div class="fw-medium mb-1">
                                                <i class="ti ti-user-off me-1"></i>注销账号
                                            </div>
                                            <p class="small text-secondary mb-2 flex-grow-1">永久删除您的账号和所有相关数据，此操作不可恢复。</p>
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                                注销账号
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="d-flex flex-column h-100">
                                            <div class="fw-medium mb-1">
                                                <i class="ti ti-list-details me-1"></i>操作日志
                                            </div>
                                            <p class="small text-secondary mb-2 flex-grow-1">查看您账号的所有操作记录，保障账户安全。</p>
                                            <a href="{{ route('user.profile.activity-log') }}" class="btn btn-outline-secondary btn-sm w-100">
                                                查看日志
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 注销账号确认弹窗 --}}
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger-lt">
                <h5 class="modal-title text-danger">
                    <i class="ti ti-alert-triangle me-2"></i>申请注销账号
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- 数据概览 --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="ti ti-database me-2"></i>您的数据资产</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-3">
                                <div class="fw-bold text-primary fs-4">{{ $user->resumes()->count() }}</div>
                                <div class="small text-secondary">简历</div>
                            </div>
                            <div class="col-3">
                                <div class="fw-bold text-success fs-4">{{ $user->interviewSessions()->count() }}</div>
                                <div class="small text-secondary">面试</div>
                            </div>
                            <div class="col-3">
                                <div class="fw-bold text-info fs-4">{{ $user->jobApplications()->count() }}</div>
                                <div class="small text-secondary">申请</div>
                            </div>
                            <div class="col-3">
                                <div class="fw-bold text-warning fs-4">{{ $user->loginHistories()->count() }}</div>
                                <div class="small text-secondary">登录记录</div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0 small">
                            <i class="ti ti-info-circle me-1"></i>
                            <strong>建议：</strong>注销前请先<button type="button" class="btn btn-link alert-link p-0 border-0 align-baseline" id="triggerProfileExport">导出您的数据</button>，以便日后使用。
                        </div>
                    </div>
                </div>

                {{-- 注销说明 --}}
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading"><i class="ti ti-clock me-1"></i>冷静期机制</h6>
                    <p class="mb-0 small">注销申请提交后，您的账号将进入<strong>7天冷静期</strong>。在此期间您可以随时取消注销。冷静期结束后，所有数据将被永久删除，无法恢复。</p>
                </div>

                <form action="{{ route('user.profile.destroy-account') }}" method="POST" id="deleteAccountForm">
                    @csrf
                    @method('DELETE')
                    <input type="text" name="username" value="{{ $user->email }}" autocomplete="username" class="d-none" tabindex="-1" aria-hidden="true" readonly>

                    {{-- 注销原因 --}}
                    <div class="mb-3">
                        <label class="form-label">注销原因（可选）</label>
                        <select name="deletion_reason" class="form-select" id="deletionReason">
                            <option value="">请选择...</option>
                    <option value="not_needed">不再需要此服务</option>
                            <option value="found_alternative">找到了更好的替代产品</option>
                    <option value="too_expensive">价格太贵</option>
                            <option value="not_satisfied">对产品不满意</option>
                            <option value="privacy">隐私/安全顾虑</option>
                            <option value="other">其他原因</option>
                        </select>
                    </div>

                    {{-- 详细反馈 --}}
                    <div class="mb-3" id="feedbackContainer" style="display: none;">
                        <label class="form-label">详细说明（可选）</label>
                        <textarea name="deletion_feedback" class="form-control" rows="3" maxlength="1000" placeholder="您的反馈将帮助我们改进产品..."></textarea>
                    </div>

                    {{-- 确认勾选 --}}
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="confirm_understand" id="confirmUnderstand" required>
                            <label class="form-check-label" for="confirmUnderstand">
                                我了解注销后 <strong>7天内可以恢复账号</strong>，之后数据将永久删除
                            </label>
                </div>
            </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="confirm_data_loss" id="confirmDataLoss" required>
                            <label class="form-check-label" for="confirmDataLoss">
                                我已知晓所有数据（简历、面试记录等）将被 <strong>永久删除且无法恢复</strong>
                            </label>
            </div>
        </div>

                    {{-- 密码确认 --}}
                    <div class="mb-0">
                        <label class="form-label">请输入密码确认身份</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required placeholder="输入当前密码" autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                    <i class="ti ti-user-off me-1"></i>确认申请注销
                </button>
        </div>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/user-profile.js') }}"></script>
@endpush

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function(){
    document.querySelectorAll('.unbind-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var form = btn.closest('form');
            var providerName = form.dataset.provider || '';
            if (typeof window.appPrompt !== 'function') {
                if (confirm('确定解绑' + providerName + '吗？')) form.submit();
                return;
            }
            window.appPrompt('请输入登录密码以解绑' + providerName, '', {
                title: '解绑确认',
                inputType: 'password',
                placeholder: '请输入登录密码',
                required: true,
            }).then(function(password){
                if (password !== null && password !== '') {
                    form.querySelector('input[name="password"]').value = password;
                    form.submit();
                }
            });
        });
    });
})();
</script>
@endpush
