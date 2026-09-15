@extends('layouts.auth')

@section('title', '安全确认')

@section('auth-content')
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-card-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h2 class="auth-card-title">安全确认</h2>
                    <p class="auth-card-subtitle">此操作需要验证您的身份，请输入当前密码以继续。</p>
                </div>

                <div class="auth-card-body">
                    @if ($errors->any())
                        <div class="auth-alert auth-alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.confirm') }}" autocomplete="off">
                        @csrf

                        <div class="auth-form-group">
                            <label class="auth-form-label">当前密码</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--color-border-primary); color: var(--color-text-muted);">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" name="password"
                                       class="auth-form-control border-start-0"
                                       style="border-top-left-radius: 0; border-bottom-left-radius: 0;"
                                       required autofocus
                                       autocomplete="current-password"
                                       placeholder="请输入当前密码">
                            </div>
                        </div>

                        <button type="submit" class="auth-btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>确认
                        </button>
                    </form>
                </div>

                <div class="auth-card-footer">
                    <a href="{{ route('user.dashboard') }}" class="auth-link">
                        <i class="bi bi-arrow-left me-1"></i>返回控制台
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
