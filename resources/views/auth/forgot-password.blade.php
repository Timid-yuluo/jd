@extends('layouts.auth')

@section('title', '忘记密码')

@section('auth-nav-action')
    <a href="{{ route('login') }}" class="auth-btn-outline text-decoration-none">返回登录</a>
@endsection

@section('auth-content')
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-card-icon">
                        <i class="bi bi-key"></i>
                    </div>
                    <h2 class="auth-card-title">忘记密码</h2>
                    <p class="auth-card-subtitle">输入你的注册邮箱，我们将发送密码重置链接</p>
                </div>

                <div class="auth-card-body">
                    @if (session('status'))
                        <div class="auth-alert auth-alert-success" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="auth-btn-primary w-100 d-inline-block text-decoration-none">
                                <i class="bi bi-arrow-left me-2"></i>返回登录
                            </a>
                        </div>
                    @else
                        @if ($errors->any())
                            <div class="auth-alert auth-alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="auth-form-group">
                                <label class="auth-form-label">邮箱地址</label>
                                <input type="email" name="email" class="auth-form-control w-100" value="{{ old('email') }}" placeholder="请输入注册时使用的邮箱" required autofocus>
                            </div>

                            <button type="submit" class="auth-btn-primary w-100">
                                <i class="bi bi-envelope me-2"></i>发送重置链接
                            </button>
                        </form>
                    @endif
                </div>

                @if(!session('status'))
                    <div class="auth-card-footer">
                        <p class="auth-footer-text mb-0">
                            <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>返回登录</a>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
