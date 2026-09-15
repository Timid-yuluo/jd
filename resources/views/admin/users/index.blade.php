@extends('layouts.admin')

@section('title', '用户管理')
@section('page-pretitle', '用户中心')
@section('page-title', '用户列表')

@section('content')
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-users"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['total']) }}</div><div class="text-secondary">总用户</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-green text-white avatar"><i class="ti ti-user-plus"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['today']) }}</div><div class="text-secondary">今日新增</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-azure text-white avatar"><i class="ti ti-activity"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['active_today']) }}</div><div class="text-secondary">今日活跃</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-orange text-white avatar"><i class="ti ti-crown"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['paid']) }}</div><div class="text-secondary">付费用户</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-red text-white avatar"><i class="ti ti-shield"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['admins']) }}</div><div class="text-secondary">管理员</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-yellow text-white avatar"><i class="ti ti-user-x"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['suspended']) }}</div><div class="text-secondary">已封禁</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">系统用户</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary d-none d-sm-inline-flex"><i class="ti ti-plus me-1"></i>新建</a>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary d-sm-none"><i class="ti ti-plus"></i></a>
                    <a href="{{ route('admin.users.export') }}?{{ http_build_query(request()->only(['search','role','account_status','plan'])) }}" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex"><i class="ti ti-download me-1"></i>导出</a>
                </div>
            </div>
            <div class="card-body border-bottom py-3">
                <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2">
                    <div class="col-md-3">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="搜索名称/邮箱/学校/专业..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="role" class="form-select form-select-sm">
                            <option value="">全部角色</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>管理员</option>
                            <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>普通用户</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="account_status" class="form-select form-select-sm">
                            <option value="">全部状态</option>
                            <option value="active" {{ request('account_status') === 'active' ? 'selected' : '' }}>正常</option>
                            <option value="suspended" {{ request('account_status') === 'suspended' ? 'selected' : '' }}>已封禁</option>
                            <option value="pending_deletion" {{ request('account_status') === 'pending_deletion' ? 'selected' : '' }}>待注销</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="plan" class="form-select form-select-sm">
                            <option value="">全部套餐</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->slug }}" {{ request('plan') === $plan->slug ? 'selected' : '' }}>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="sort" class="form-select form-select-sm">
                            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>最新注册</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>最早注册</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>按名称</option>
                            <option value="resumes" {{ request('sort') === 'resumes' ? 'selected' : '' }}>简历最多</option>
                            <option value="last_login" {{ request('sort') === 'last_login' ? 'selected' : '' }}>最近登录</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100">筛选</button>
                    </div>
                </form>
                @if(request()->hasAny(['search','role','account_status','sort','plan']))
                    <div class="mt-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-ghost-secondary"><i class="ti ti-x me-1"></i>清除筛选</a>
                    </div>
                @endif
            </div>
            <form id="batch-form" action="{{ route('admin.users.batch-delete') }}" method="POST" class="m-0" data-batch-suspend-url="{{ route('admin.users.batch-suspend') }}">
                @csrf
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th class="w-1"><input class="form-check-input" type="checkbox" id="check-all"></th>
                                <th>用户</th>
                                <th>角色</th>
                                <th>学校/专业</th>
                                <th>套餐</th>
                                <th>状态</th>
                                <th>简历</th>
                                <th>面试</th>
                                <th>注册时间</th>
                                <th>最近登录</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td><input class="form-check-input row-check" type="checkbox" name="ids[]" value="{{ $user->id }}" {{ $user->id === auth()->id() ? 'disabled' : '' }}></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ \Illuminate\Support\Str::avatarSvg($user->email, 32) }}" class="avatar avatar-xs me-2 rounded" alt="">
                                        <div>
                                            <div class="fw-medium">{{ $user->name }}</div>
                                            <div class="text-secondary small">{{ $user->email }}</div>
                                        </div>
                                        @if($user->email_verified_at)
                                            <i class="ti ti-circle-check text-success ms-1" title="邮箱已验证"></i>
                                        @else
                                            <i class="ti ti-circle-x text-secondary ms-1" title="邮箱未验证"></i>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        @if($role->name === 'super-admin')
                                            <span class="badge bg-red text-red-fg">超管</span>
                                        @elseif($role->name === 'admin')
                                            <span class="badge bg-orange text-orange-fg">管理员</span>
                                        @elseif($role->name === 'editor')
                                            <span class="badge bg-azure text-azure-fg">编辑</span>
                                        @else
                                            <span class="badge bg-secondary text-secondary-fg">{{ $role->name }}</span>
                                        @endif
                                    @endforeach
                                    @if($user->roles->isEmpty())
                                        <span class="badge bg-secondary-lt">用户</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->school || $user->major)
                                        <div class="small">{{ $user->school ?? '' }}</div>
                                        @if($user->major)<div class="text-secondary small">{{ $user->major }}</div>@endif
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $planSlug = $user->current_plan_slug ?? 'free';
                                        $planBadge = match($planSlug) {
                                            'pro', 'professional' => 'bg-azure-lt text-azure-fg',
                                            'premium', 'enterprise' => 'bg-purple-lt text-purple-fg',
                                            default => 'bg-secondary-lt',
                                        };
                                    @endphp
                                    <span class="badge {{ $planBadge }}">{{ $user->currentPlan()->name ?? '免费版' }}</span>
                                </td>
                                <td>
                                    @if($user->isPendingDeletion())
                                        <span class="badge bg-yellow-lt text-yellow-fg">待注销</span>
                                    @elseif($user->is_suspended)
                                        <span class="badge bg-red-lt text-red-fg">已封禁</span>
                                    @else
                                        <span class="badge bg-green-lt text-green-fg">正常</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-azure-lt">{{ $user->resumes_count }}</span></td>
                                <td><span class="badge bg-orange-lt">{{ $user->interview_sessions_count }}</span></td>
                                <td>
                                    <div class="small">{{ $user->created_at->format('Y-m-d') }}</div>
                                    <div class="text-secondary small">{{ $user->created_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    @if($user->last_login_at)
                                        <div class="small">{{ $user->last_login_at->diffForHumans() }}</div>
                                        <div class="text-secondary small">{{ $user->last_login_ip ?? '-' }}</div>
                                    @else
                                        <span class="text-secondary">从未</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group dropdown">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-info" title="详情"><i class="ti ti-eye"></i></a>
                                        @can('users.edit')
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="编辑"><i class="ti ti-edit"></i></a>
                                        @endcan
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">更多</span></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('users.edit')
                                            <li><a class="dropdown-item" href="{{ route('admin.users.roles.edit', $user) }}"><i class="ti ti-shield me-2"></i>角色管理</a></li>
                                            @endcan
                                            @can('plans.manage')
                                            <li><a class="dropdown-item" href="{{ route('admin.users.activate-plan', $user) }}"><i class="ti ti-crown me-2"></i>赠送开通</a></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.users.grant-credits', $user) }}"><i class="ti ti-credit-card me-2"></i>赠送次卡</a></li>
                                            @endcan
                                            @can('users.edit')
                                            @if($user->id !== auth()->id())
                                                <li><hr class="dropdown-divider"></li>
                                                @if($user->is_suspended)
                                                    <li>
                                                        <form action="{{ route('admin.users.unsuspend', $user) }}" method="POST" data-confirm-submit="确认解除封禁该用户吗？">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item text-success"><i class="ti ti-circle-check me-2"></i>解除封禁</button>
                                                        </form>
                                                    </li>
                                                @else
                                                    <li>
                                                        <form action="{{ route('admin.users.suspend', $user) }}" method="POST" data-confirm-submit="确认封禁该用户吗？">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item text-warning"><i class="ti ti-ban me-2"></i>封禁用户</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endif
                                            @endcan
                                            @can('users.delete')
                                            @if($user->id !== auth()->id())
                                                <li>
                                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" data-app-confirm='确定要永久删除此用户及所有关联数据吗？此操作不可恢复！'>
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"><i class="ti ti-trash me-2"></i>永久删除</button>
                                                    </form>
                                                </li>
                                            @endif
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-secondary py-4">
                                    <i class="ti ti-users text-muted" style="font-size: 2rem;"></i>
                                    <div class="mt-2">暂无匹配用户</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <div id="batch-bar" class="card-footer d-none d-flex align-items-center bg-yellow-lt">
                <i class="ti ti-selection me-2"></i>
                已选择 <strong class="mx-1 batch-count">0</strong> 个用户
                <div class="ms-auto d-flex gap-2">
                    @can('users.batch_delete')
                    <button type="button" class="btn btn-sm btn-danger" data-action="batch-delete"><i class="ti ti-trash me-1"></i>批量删除</button>
                    @endcan
                    @can('users.edit')
                    <button type="button" class="btn btn-sm btn-outline-warning" data-action="batch-suspend"><i class="ti ti-ban me-1"></i>批量封禁</button>
                    @endcan
                </div>
            </div>
            <div class="card-footer d-flex align-items-center">
                <div class="d-flex gap-2">
                    @can('users.batch_delete')
                    <button type="button" class="btn btn-sm btn-danger" data-action="batch-delete"><i class="ti ti-trash me-1"></i>批量删除</button>
                    @endcan
                    @can('users.edit')
                    <button type="button" class="btn btn-sm btn-outline-warning" data-action="batch-suspend"><i class="ti ti-ban me-1"></i>批量封禁</button>
                    @endcan
                </div>
                @if($users->hasPages())
                    <div class="ms-auto">{{ $users->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-users-index.js') }}"></script>

@endpush
