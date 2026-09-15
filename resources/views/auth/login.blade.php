@extends('layouts.auth')

@section('title', '登录')

@section('auth-nav-action')
    <a href="{{ route('register') }}" class="auth-btn-outline text-decoration-none">免费注册</a>
@endsection

@section('auth-content')
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-card-icon">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </div>
                    <h2 class="auth-card-title">欢迎回来</h2>
                    <p class="auth-card-subtitle">登录你的 {{ $siteName }} 账号</p>
                </div>

                <div class="auth-card-body">
                    @if ($errors->any())
                        <div class="auth-alert auth-alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" autocomplete="off" novalidate>
                        @csrf

                        <div class="auth-form-group" style="position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;overflow:hidden;" aria-hidden="true" tabindex="-1">
                            <label class="auth-form-label">请勿填写此字段</label>
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <input type="hidden" name="_form_token" value="{{ md5(request()->ip() . now()->format('YmdH')) }}">

                        <div class="auth-form-group">
                            <label class="auth-form-label">邮箱地址</label>
                            <input type="email" name="email" class="auth-form-control w-100" value="{{ old('email') }}" placeholder="请输入邮箱地址" required autofocus>
                        </div>

                        <div class="auth-form-group">
                            <div class="auth-password-header">
                                <label class="auth-form-label">密码</label>
                                <a href="{{ route('password.request') }}" class="auth-link" style="font-size: 0.8rem;">忘记密码？</a>
                            </div>
                            <input type="password" name="password" class="auth-form-control w-100" placeholder="请输入密码" required>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-checkbox">
                                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                                <span>记住我</span>
                            </label>
                        </div>

                        <button type="submit" class="auth-btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>登录
                        </button>
                    </form>

                    @php
                        $quickLoginEnabled = (bool) ($siteSettings['auth_quick_login_enabled'] ?? false);
                        $providers = [
                            [
                                'enabled' => (bool) ($siteSettings['auth_provider_github_enabled'] ?? false),
                                'label' => 'GitHub',
                                'icon' => 'bi bi-github',
                                'route' => 'auth.oauth.github.redirect',
                            ],
                            [
                                'enabled' => (bool) ($siteSettings['auth_provider_alipay_enabled'] ?? false),
                                'label' => '支付宝',
                                'icon' => 'bi bi-alipay',
                                'route' => 'auth.oauth.alipay.redirect',
                            ],
                        ];
                        $activeProviders = array_values(array_filter($providers, static fn (array $provider): bool => $provider['enabled'] === true && \Illuminate\Support\Facades\Route::has($provider['route'])));
                    @endphp

                    @if($quickLoginEnabled && $activeProviders !== [])
                        <div class="auth-divider">
                            <span>快捷登录</span>
                        </div>

                        <div class="d-grid gap-2">
                            @foreach($activeProviders as $provider)
                                <a href="{{ route($provider['route']) }}" class="auth-social-btn">
                                    <i class="{{ $provider['icon'] }}"></i>
                                    <span>{{ $provider['label'] }} 快捷登录</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if(app()->environment(['local', 'testing']) && config('app.debug') && config('app.show_test_credentials'))
                        <div class="text-center mt-3">
                            <span class="auth-test-badge">
                                <i class="bi bi-info-circle me-1"></i>测试账号：test@example.com / password
                            </span>
                        </div>
                    @endif
                </div>

                <div class="auth-card-footer">
                    <p class="auth-footer-text mb-0">
                        还没有账号？<a href="{{ route('register') }}">立即注册</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
