@extends('layouts.auth')

@section('title', '退出登录')

@section('auth-content')
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-card-icon">
                        <i class="bi bi-box-arrow-right"></i>
                    </div>
                    <h2 class="auth-card-title">确认退出</h2>
                    <p class="auth-card-subtitle">您确定要退出当前账号吗？</p>
                </div>

                <div class="auth-card-body">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="auth-btn-primary w-100">
                            <i class="bi bi-box-arrow-right me-2"></i>确认退出
                        </button>
                    </form>

                    <a href="{{ route('user.dashboard') }}" class="auth-btn-outline w-100 mt-3">
                        <i class="bi bi-arrow-left me-2"></i>返回控制台
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
