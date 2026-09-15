@extends('layouts.auth')

@section('title', '重置密码')

@section('auth-nav-action')
    <a href="{{ route('login') }}" class="auth-btn-outline text-decoration-none">返回登录</a>
@endsection

@section('auth-content')
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-card-body">
                    <div class="text-center mb-4">
                        <div class="auth-icon-wrapper">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <h2 class="auth-title">重置密码</h2>
                        <p class="auth-subtitle">请设置您的新密码</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label class="form-label fw-medium">邮箱地址</label>
                            <input type="email" name="email" class="auth-form-control form-control"
                                   value="{{ $email ?? old('email') }}" required autofocus
                                   placeholder="请输入您的邮箱地址">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">新密码</label>
                            <input type="password" name="password" class="auth-form-control form-control" required
                                   placeholder="至少8位字符">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">确认密码</label>
                            <input type="password" name="password_confirmation" class="auth-form-control form-control" required
                                   placeholder="再次输入新密码">
                        </div>

                        <button type="submit" class="auth-btn-primary w-100">
                            <i class="bi bi-lock-unlock-fill me-2"></i>重置密码
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
