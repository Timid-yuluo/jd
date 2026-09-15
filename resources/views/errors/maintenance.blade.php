@php
    $siteName = $siteName ?? config('app.name');
    $siteLogo = $siteLogoUrl ?? '';
    $favicon = $faviconUrl ?? '';
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>系统维护中 - {{ $siteName }}</title>
    @if(!empty($favicon))
        <link rel="icon" href="{{ $favicon }}">
    @endif
    <link rel="stylesheet" href="{{ asset('vendor/tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/tabler/icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/errors-maintenance.css') }}">
</head>
<body>
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i class="ti ti-tool"></i>
        </div>
        
        @if(!empty($siteLogo))
            <img src="{{ $siteLogo }}" alt="{{ $siteName }}" style="max-height: 40px; margin-bottom: 1.5rem;">
        @endif
        
        <h1 class="maintenance-title">系统维护中</h1>
        
        <div class="maintenance-message">
            {!! nl2br(e($message)) !!}
        </div>
        
        <a href="{{ route('login') }}" class="admin-link">
            <i class="ti ti-login me-2"></i>
            管理员登录
        </a>
    </div>
</body>
</html>
