@php
    // AppServiceProvider 已在 View Composer 中清洗过这些值，无需重复清洗
    $customHeadSnippet = $customHeadCode;
    $analyticsClaritySnippet = $analyticsClarity;
    $sidebarCollapsed = $sidebarCollapsed ? 'navbar-vertical-collapsed' : '';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="auto">
<head>
    @include('partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- SEO Meta Tags --}}
    <meta name="description" content="{{ $siteSettings['seo_description'] ?? config('app.name') }}">
    <meta name="keywords" content="{{ $siteSettings['seo_keywords'] ?? '' }}">
    
    {{-- Favicon --}}
    @if (!empty($siteSettings['favicon_url']))
        <link rel="icon" href="{{ $siteSettings['favicon_url'] }}" type="image/x-icon">
    @endif

    <title>@yield('title', '后台管理') - {{ $siteSettings['site_name'] ?? config('app.name') }}</title>

    <link rel="stylesheet" href="{{ asset('fonts/instrument-sans/instrument-sans.css') }}" />

    <link rel="preload" href="{{ asset('css/dark-mode.css') }}" as="style">
    <link rel="preload" href="{{ asset('css/pages/layout-admin.css') }}" as="style">

    {{-- Custom Theme Color --}}
    <link rel="stylesheet" href="{{ asset('css/pages/layout-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/quota-modal.css') }}">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/admin.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('vendor/tabler/css/tabler.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/tabler/icons/tabler-icons.min.css') }}">
        <script defer src="{{ asset('vendor/tabler/js/tabler.min.js') }}"></script>
    @endif
    
    @include('partials.ui-notify-styles')
    @stack('styles')
</head>
<body class="layout-boxed {{ $sidebarCollapsed }}">
    <div class="page">
        @include('admin.partials.navbar')

        <div class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">@yield('page-pretitle', '管理中心')</div>
                            <h2 class="page-title">@yield('page-title', '后台管理')</h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">@yield('page-actions')</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-check icon alert-icon"></i></div>
                                <div>{{ session('success') }}</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-alert-circle icon alert-icon"></i></div>
                                <div>{{ session('error') }}</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    {{-- Maintenance Notice --}}
            @if (!empty($siteSettings['maintenance_mode']) && !empty($siteSettings['maintenance_notice']))
                <div class="alert alert-warning alert-important mb-3">
                    <div class="d-flex">
                        <div><i class="ti ti-alert-triangle icon alert-icon"></i></div>
                        <div>
                            <h4 class="alert-title">维护模式已开启</h4>
                            <div class="text-secondary">{{ $siteSettings['maintenance_notice'] }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
                </div>
            </div>
        </div>
    </div>

    <footer class="border-top mt-4 py-3">
        <div class="container-xl d-flex flex-column flex-md-row justify-content-between text-secondary small gap-2">
            <div>
                @if(!empty($copyrightText))
                    {{ $copyrightText }}
                @else
                    &copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? config('app.name') }}
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

    @include('partials.ui-notify')

    @stack('scripts')
    <script src="{{ asset('js/common/ui-utils.js') }}"></script>
    <script src="{{ asset('js/pages/layout-admin.js') }}"></script>
    <script src="{{ asset('js/theme-switcher.js') }}"></script>
    <script nonce="{{ request()->attributes->get('csp_nonce', '') }}">window.__deviceFpEndpoint='{{ route("device.fingerprint.store") }}';</script>
    <script src="{{ asset('js/common/device-fingerprint.js') }}"></script>
    <script src="{{ asset('js/common/visit-tracker.js') }}"></script>

</body>
</html>
