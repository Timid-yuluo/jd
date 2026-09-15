@extends('layouts.admin')

@section('title', '分配角色')
@section('page-pretitle', '用户中心')
@section('page-title', '分配角色：' . $user->name)

@section('content')
<div class="row row-cards">
    <div class="col-md-6">
        <form action="{{ route('admin.users.roles.update', $user) }}" method="POST" class="card">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h3 class="card-title">角色设置</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">当前用户</label>
                    <div class="d-flex align-items-center">
                        <img src="{{ \Illuminate\Support\Str::avatarSvg($user->email, 28) }}" class="avatar avatar-sm me-2 rounded" alt="">
                        <div>
                            <div class="fw-medium">{{ $user->name }}</div>
                            <div class="text-secondary small">{{ $user->email }}</div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">当前角色</label>
                    <div>
                        @forelse($user->roles as $role)
                            @if($role->name === 'super-admin')
                                <span class="badge bg-red text-red-fg">超级管理员</span>
                            @elseif($role->name === 'admin')
                                <span class="badge bg-orange text-orange-fg">管理员</span>
                            @else
                                <span class="badge bg-azure text-azure-fg">{{ $role->name }}</span>
                            @endif
                        @empty
                            <span class="text-secondary">无角色</span>
                        @endforelse
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">分配角色</label>
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
                    <div class="form-hint">取消所有选项可清空角色</div>
                </div>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-link">返回</a>
                    <button type="submit" class="btn btn-primary ms-auto">保存角色</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
