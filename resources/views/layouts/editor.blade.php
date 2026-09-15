@php
    // AppServiceProvider 已在 View Composer 中清洗过这些值，无需重复清洗
    $customHeadSnippet = $customHeadCode;
    $analyticsClaritySnippet = $analyticsClarity;
    $editorCssVersion = @filemtime(public_path('css/editor.css')) ?: time();
    $editorScrollMode = (string) ($siteSettings['editor_scroll_mode'] ?? config('resume.editor.scroll_mode', 'natural'));
    $editorScrollModeOverride = trim((string) $__env->yieldContent('editor-scroll-mode'));
    if (in_array($editorScrollModeOverride, ['natural', 'split'], true)) {
        $editorScrollMode = $editorScrollModeOverride;
    }
    $editorScrollMode = in_array($editorScrollMode, ['natural', 'split'], true) ? $editorScrollMode : 'natural';
    $disableLegacyEditorCss = trim((string) $__env->yieldContent('disable-legacy-editor-css')) === '1';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="auto"
      data-editor-scroll-mode="{{ $editorScrollMode }}">
<head>
    @include('partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '编辑器') - {{ $siteName }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    @if(!empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif

    <link rel="stylesheet" href="{{ asset('fonts/outfit/outfit.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/work-sans/work-sans.css') }}">

    <link rel="preload" href="{{ asset('css/dark-mode.css') }}" as="style">
    <link rel="preload" href="{{ asset('css/pages/layout-editor.css') }}" as="style">

    {{-- Custom Theme Color --}}
    <link rel="stylesheet" href="{{ asset('css/pages/layout-editor.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/quota-modal.css') }}">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('vendor/tabler/css/tabler.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/tabler/icons/tabler-icons.min.css') }}">
        <script defer src="{{ asset('js/alpine.min.js') }}"></script>
        <script defer src="{{ asset('vendor/tabler/js/tabler.min.js') }}"></script>
    @endif
    @unless($disableLegacyEditorCss)
        <link rel="stylesheet" href="{{ asset('css/editor.css') }}?v={{ $editorCssVersion }}">
    @endunless
    
    {{-- Custom Head Code --}}
    {!! $customHeadSnippet !!}
    
    {{-- Third-party Analytics --}}
    {!! $analyticsGoogle ?? '' !!}
    {!! $analyticsBaidu ?? '' !!}
    {!! $analyticsClaritySnippet !!}

    @include('partials.ui-notify-styles')
    @stack('styles')
</head>
<body>
    {{-- Minimal editor top bar --}}
    <header class="navbar navbar-light d-print-none editor-topbar" style="height: 48px; min-height: 48px; z-index: 1030;">
        <div class="container-fluid h-100 px-3">
            <div class="d-flex align-items-center h-100 w-100">
                {{-- Left: back + logo --}}
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('user.resumes.index') }}" class="btn btn-sm btn-ghost-secondary px-1" title="返回简历列表">
                        <i class="ti ti-arrow-left fs-4"></i>
                    </a>
                    <h1 class="navbar-brand navbar-brand-autodark m-0 p-0" style="font-size: 15px; font-weight: 600;">
                        <a href="{{ route('user.dashboard') }}" class="text-decoration-none d-flex align-items-center gap-1">
                            @if(!empty($siteLogoUrl))
                                <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" style="height: 22px;">
                            @else
                                <i class="ti ti-brain icon"></i>
                            @endif
                            <span class="d-none d-md-inline">{{ $siteName }}</span>
                        </a>
                    </h1>
                    <span class="text-secondary mx-1 d-none d-md-inline">/</span>
                    <span class="text-truncate" style="max-width: 200px; font-size: 14px;" title="@yield('editor-title', '编辑简历')" data-bs-toggle="tooltip" data-bs-placement="bottom">@yield('editor-title', '编辑简历')</span>
                </div>

                {{-- Center: save status (driven by Alpine) --}}
                <div class="flex-fill text-center d-none d-md-block">
                    @yield('editor-status')
                </div>
                {{-- Compact save status for mobile --}}
                <div class="flex-fill text-center d-md-none">
                    @yield('editor-status-mobile')
                </div>

                {{-- Right: actions --}}
                <div class="d-flex align-items-center gap-2">
                    <button class="theme-toggle-btn" data-theme-toggle type="button" title="切换主题">
                        <i class="ti ti-sun theme-icon-light"></i>
                        <i class="ti ti-moon theme-icon-dark" style="display:none;"></i>
                        <i class="ti ti-circle-half theme-icon-auto" style="display:none;"></i>
                        <span class="theme-toggle-label">自动</span>
                    </button>
                    @yield('editor-actions')
                </div>
            </div>
        </div>
    </header>

    {{-- Full-height content area --}}
    <div class="page-wrapper" style="height: calc(100vh - 48px); overflow: auto; margin: 0 !important; position: relative;">
        {{-- Mobile hint banner --}}
        <div class="d-lg-none alert alert-info alert-dismissible m-2 mb-0 py-2 small fade show" role="alert">
            <i class="ti ti-device-desktop me-1"></i>
            建议使用电脑浏览器编辑简历，获得最佳体验。
            <a href="{{ route('user.resumes.index') }}" class="alert-link ms-1">返回简历列表查看预览</a>
            <button type="button" class="btn-close p-1" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.6rem;"></button>
        </div>
        @yield('content')
    </div>

    @include('partials.ui-notify')

    <footer class="border-top py-2 px-3 d-print-none" style="background: var(--tblr-body-bg, #fff);">
        <div class="d-flex justify-content-between align-items-center text-secondary small">
            <div>
                @if(!empty($copyrightText))
                    {{ $copyrightText }}
                @else
                    &copy; {{ date('Y') }} {{ $siteName }}
                @endif
            </div>
            <div>
                @if(!empty($icpNumber))
                    <span>{{ $icpNumber }}</span>
                @endif
                @if(!empty($policeRecordNumber))
                    <span class="ms-2">
                        @if(!empty($policeRecordUrl))
                            <a href="{{ $policeRecordUrl }}" target="_blank" rel="noopener noreferrer">{{ $policeRecordNumber }}</a>
                        @else
                            {{ $policeRecordNumber }}
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </footer>

    <script src="{{ asset('js/common/ui-utils.js') }}"></script>
    <script src="{{ asset('js/pages/quota-interceptor.js') }}?v={{ @filemtime(public_path('js/pages/quota-interceptor.js')) ?: time() }}"></script>
    @stack('scripts')
    @yield('scripts')
    <script src="{{ asset('js/theme-switcher.js') }}"></script>
    
    {{-- Custom Body Code --}}
    {!! $customBodyCode !!}
</body>
</html>
