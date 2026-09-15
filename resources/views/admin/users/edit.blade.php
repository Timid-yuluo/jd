@extends('layouts.admin')

@section('title', '编辑用户')
@section('page-pretitle', '用户中心')
@section('page-title', '编辑用户：' . $user->name)

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="card">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h3 class="card-title">用户信息</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">名称</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">邮箱</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">密码</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="留空则不修改">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">学校</label>
                        <input type="text" name="school" class="form-control @error('school') is-invalid @enderror" value="{{ old('school', $user->school) }}">
                        @error('school')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">专业</label>
                        <input type="text" name="major" class="form-control @error('major') is-invalid @enderror" value="{{ old('major', $user->major) }}">
                        @error('major')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">邮箱验证</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="email_verified" value="1" class="form-selectgroup-input" {{ old('email_verified', $user->email_verified_at ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label"><i class="ti ti-circle-check text-success me-1"></i>已验证</span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="email_verified" value="0" class="form-selectgroup-input" {{ old('email_verified', $user->email_verified_at ? '1' : '0') === '0' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label"><i class="ti ti-circle-x text-secondary me-1"></i>未验证</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-link">取消</a>
                    <button type="submit" class="btn btn-primary ms-auto">保存更改</button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">角色分配</h3>
            </div>
            <div class="card-body">
                @foreach($roles as $id => $name)
                    <label class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $name }}" {{ $user->hasRole($name) ? 'checked' : '' }}
                            @if($name === 'super-admin' && !auth()->user()?->hasRole('super-admin')) disabled @endif
                        >
                        <span class="form-check-label">
                            @if($name === 'super-admin')
                                <span class="badge bg-red text-red-fg">超级管理员</span>
                            @elseif($name === 'admin')
                                <span class="badge bg-orange text-orange-fg">管理员</span>
                            @elseif($name === 'editor')
                                <span class="badge bg-azure text-azure-fg">编辑</span>
                            @else
                                {{ $name }}
                            @endif
                        </span>
                    </label>
                @endforeach
                <div class="form-hint">角色变更即时生效，请谨慎操作</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">快捷操作</h3>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.users.show', $user) }}" class="list-group-item list-group-item-action"><i class="ti ti-eye me-2"></i>查看详情</a>
                @can('plans.manage')
                <a href="{{ route('admin.users.activate-plan', $user) }}" class="list-group-item list-group-item-action"><i class="ti ti-crown me-2"></i>赠送开通</a>
                <a href="{{ route('admin.users.grant-credits', $user) }}" class="list-group-item list-group-item-action"><i class="ti ti-credit-card me-2"></i>赠送次卡</a>
                @endcan
                @can('users.edit')
                    @if($user->id !== auth()->id())
                        @if($user->is_suspended)
                            <form action="{{ route('admin.users.unsuspend', $user) }}" method="POST" data-confirm-submit="确认解除封禁该用户吗？">
                                @csrf
                                <button type="submit" class="list-group-item list-group-item-action text-success w-100 text-start border-0"><i class="ti ti-circle-check me-2"></i>解除封禁</button>
                            </form>
                        @else
                            <form action="{{ route('admin.users.suspend', $user) }}" method="POST" data-confirm-submit="确认封禁该用户吗？">
                                @csrf
                                <button type="submit" class="list-group-item list-group-item-action text-warning w-100 text-start border-0"><i class="ti ti-ban me-2"></i>封禁用户</button>
                            </form>
                        @endif
                    @endif
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">账户信息</h3>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">ID</span>
                    <span>{{ $user->id }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">注册时间</span>
                    <span>{{ $user->created_at->format('Y-m-d') }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">最近登录</span>
                    <span>{{ $user->last_login_at?->format('Y-m-d') ?? '从未' }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">当前套餐</span>
                    <span>{{ $user->currentPlan()->name ?? '免费版' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
