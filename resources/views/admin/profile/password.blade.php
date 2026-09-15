@extends('layouts.admin')

@section('title', '修改密码')
@section('page-pretitle', '个人中心')
@section('page-title', '修改密码')

@section('page-actions')
<a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-arrow-left me-1"></i>返回
</a>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-md-6">
        <form action="{{ route('admin.profile.password.update') }}" method="POST" class="card">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h3 class="card-title">修改登录密码</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">当前密码</label>
                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required">新密码</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required">确认新密码</label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">更新密码</button>
            </div>
        </form>
    </div>
</div>
@endsection
