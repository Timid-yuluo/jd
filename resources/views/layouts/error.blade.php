@php
    $siteName = $siteName ?? config('app.name');
    $siteLogo = $siteLogoUrl ?? '';
    $favicon = $faviconUrl ?? '';
    $errorCode = $errorCode ?? '500';
    $errorTitle = $errorTitle ?? '服务器错误';
    $errorMessage = $errorMessage ?? '服务器遇到了内部错误，请稍后重试。';
    $errorIcon = $errorIcon ?? 'ti ti-alert-triangle';
    $errorCssFile = $errorCssFile ?? 'errors-500';
    $errorAction = $errorAction ?? '<a href="/" class="error-link"><i class="ti ti-home me-2"></i>返回首页</a>';
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $errorTitle }} - {{ $siteName }}</title>
    @if(!empty($favicon))<link rel="icon" href="{{ $favicon }}">@endif
    <link rel="stylesheet" href="{{ asset('vendor/tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/tabler/icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/' . $errorCssFile . '.css') }}">
    {!! $analyticsGoogle ?? '' !!}
    {!! $analyticsBaidu ?? '' !!}
    {!! $analyticsClarity ?? '' !!}
</head>
<body>
    <div class="error-card">
        <div class="error-icon"><i class="{{ $errorIcon }}"></i></div>
        @if(!empty($siteLogo))<img src="{{ $siteLogo }}" alt="{{ $siteName }}" style="max-height: 40px; margin-bottom: 1.5rem;">@endif
        <div class="error-code">{{ $errorCode }}</div>
        <h1 class="error-title">{{ $errorTitle }}</h1>
        <div class="error-message">{{ $errorMessage }}</div>
        {!! $errorAction !!}
    </div>
</body>
</html>
