@extends('layouts.admin')

@section('title', '新建用户')
@section('page-pretitle', '用户中心')
@section('page-title', '新建用户')

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <form action="{{ route('admin.users.store') }}" method="POST" class="card">
            @csrf
            <div class="card-header">
                <h3 class="card-title">用户信息</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">名称</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">邮箱</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">密码</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">学校</label>
                        <input type="text" name="school" class="form-control @error('school') is-invalid @enderror" value="{{ old('school') }}">
                        @error('school')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">专业</label>
                        <input type="text" name="major" class="form-control @error('major') is-invalid @enderror" value="{{ old('major') }}">
                        @error('major')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">邮箱验证</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="email_verified" value="1" class="form-selectgroup-input" {{ old('email_verified', '1') === '1' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label"><i class="ti ti-circle-check text-success me-1"></i>已验证</span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="email_verified" value="0" class="form-selectgroup-input" {{ old('email_verified', '1') === '0' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label"><i class="ti ti-circle-x text-secondary me-1"></i>未验证</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-link">取消</a>
                    <button type="submit" class="btn btn-primary ms-auto">创建用户</button>
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
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $name }}" {{ in_array($name, old('roles', [])) ? 'checked' : '' }}
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
    </div>
</div>
@endsection
