@php
    $siteName = $siteName ?? config('app.name');
    $siteLogoUrl = $siteLogoUrl ?? '';
    $faviconUrl = $faviconUrl ?? '';
    $seoDescription = $seoDescription ?? config('app.name');
    $seoKeywords = $seoKeywords ?? '';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="auto">
<head>
    @include('partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '认证') - {{ $siteName }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    @if(!empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('fonts/outfit/outfit.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/work-sans/work-sans.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
    @stack('auth-head')
    {!! $analyticsGoogle ?? '' !!}
    {!! $analyticsBaidu ?? '' !!}
    {!! $analyticsClarity ?? '' !!}
</head>
<body>
    <nav class="auth-navbar navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                @if(!empty($siteLogoUrl))
                    <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" style="height: 28px;">
                @else
                    <i class="bi bi-file-earmark-text fs-4" style="color: var(--primary);"></i>
                @endif
                <span>{{ $siteName }}</span>
            </a>
            <div class="ms-auto d-flex gap-2 align-items-center">
                <button class="theme-toggle-btn" data-theme-toggle type="button" title="切换主题">
                    <i class="bi bi-sun-fill theme-icon-light"></i>
                    <i class="bi bi-moon-fill theme-icon-dark" style="display:none;"></i>
                    <i class="bi bi-circle-half theme-icon-auto" style="display:none;"></i>
                    <span class="theme-toggle-label">自动</span>
                </button>
                @yield('auth-nav-action')
            </div>
        </div>
    </nav>

    @yield('auth-content')

    <script src="{{ asset('js/theme-switcher.js') }}"></script>
    @stack('auth-scripts')
</body>
</html>
