@php
    // AppServiceProvider 已在 View Composer 中清洗过这些值，无需重复清洗
    $customHeadSnippet = $customHeadCode;
    $analyticsClaritySnippet = $analyticsClarity;
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="auto">
<head>
    @include('partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '用户中心') - {{ $siteName }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    @if(!empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif

    <link rel="stylesheet" href="{{ asset('fonts/outfit/outfit.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/work-sans/work-sans.css') }}">

    <link rel="preload" href="{{ asset('css/dark-mode.css') }}" as="style">
    <link rel="preload" href="{{ asset('css/pages/layout-user.css') }}" as="style">

    {{-- Custom Theme Color - 适配首页风格 --}}
    <link rel="stylesheet" href="{{ asset('css/pages/layout-user.css') }}">
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
    
    {{-- Custom Head Code --}}
    {!! $customHeadSnippet !!}
    
    {{-- Google Analytics --}}
    {!! $analyticsGoogle ?? '' !!}
    
    {{-- Baidu Analytics --}}
    {!! $analyticsBaidu ?? '' !!}
    
    {{-- Microsoft Clarity Analytics --}}
    {!! $analyticsClaritySnippet !!}

    @include('partials.ui-notify-styles')
    @stack('styles')
</head>
<body>
    {{-- Skip Navigation link for keyboard / screen reader users --}}
    <a href="#page-body-content" class="visually-hidden-focusable skip-link" style="position:absolute;top:0;left:0;z-index:9999;padding:8px 16px;background:#206bc4;color:#fff;text-decoration:none;">跳到主要内容</a>
    <header class="navbar navbar-expand-md navbar-light d-print-none">
        <div class="container-xl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                <a href="{{ route('user.dashboard') }}">
                    @if(!empty($siteLogoUrl))
                        <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="me-2" style="height: 26px;">
                    @else
                        <i class="ti ti-brain icon me-2"></i>
                    @endif
                    {{ $siteName }}
                </a>
            </h1>
            <div class="navbar-nav flex-row order-md-last gap-1">
                {{-- 主题切换 --}}
                <div class="nav-item d-flex align-items-center">
                    <button class="btn-action" data-theme-toggle type="button" title="切换主题">
                        <i class="ti ti-sun theme-icon-light"></i>
                        <i class="ti ti-moon theme-icon-dark" style="display:none;"></i>
                        <i class="ti ti-circle-half theme-icon-auto" style="display:none;"></i>
                    </button>
                </div>
                @auth
                {{-- 通知图标 --}}
                <div class="nav-item dropdown" id="notification-dropdown" data-unread-url="{{ route('user.notifications.unread-count') }}" data-recent-url="{{ route('user.notifications.recent') }}">
                    <a href="#" class="nav-link px-1" data-bs-toggle="dropdown" aria-label="通知">
                        <div class="position-relative">
                            <i class="ti ti-bell fs-4"></i>
                            <span id="notification-badge" class="badge bg-danger badge-pill position-absolute top-0 start-100 translate-middle" style="font-size: 10px; padding: 2px 5px; display: none;">
                                0
                            </span>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow dropdown-menu-card" style="width: 320px;">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">通知</h4>
                                <a href="{{ route('user.notifications.index') }}" class="btn btn-link p-0">查看全部</a>
                            </div>
                            <div class="list-group list-group-flush" id="notification-list">
                                <div class="list-group-item text-center py-3 text-secondary" id="notification-loading">
                                    <div class="spinner-border spinner-border-sm text-secondary me-1" role="status"></div>
                                    加载中...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown" aria-label="Open user menu">
                        <div class="user-avatar">
                            <img src="{{ \Illuminate\Support\Str::avatarSvg(Auth::user()->email, 36) }}" class="rounded-circle" width="36" height="36" alt="{{ Auth::user()->name }}的头像">
                        </div>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="small text-secondary">用户</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <a href="{{ route('user.profile') }}" class="dropdown-item">
                            <i class="ti ti-user me-2"></i>个人资料
                        </a>
                        <a href="{{ route('user.profile', ['tab' => 'membership']) }}" class="dropdown-item">
                            <i class="ti ti-crown me-2"></i>我的会员
                        </a>
                        <a href="{{ route('user.notifications.index') }}" class="dropdown-item">
                            <i class="ti ti-bell me-2"></i>我的通知
                        </a>
                        <a href="{{ route('user.notification-preferences.index') }}" class="dropdown-item">
                            <i class="ti ti-settings me-2"></i>通知设置
                        </a>
                        <a href="{{ route('feedback.index') }}" class="dropdown-item">
                            <i class="ti ti-message-circle me-2"></i>我的反馈
                        </a>
                        <a href="{{ route('public.help.index') }}" class="dropdown-item">
                            <i class="ti ti-help-hexagon me-2"></i>帮助中心
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('logout.confirm') }}" class="dropdown-item">
                            <i class="ti ti-logout me-2"></i>退出登录
                        </a>
                    </div>
                </div>
                @else
                <div class="nav-item">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-login me-1"></i>登录
                    </a>
                </div>
                @endauth
            </div>
            <div class="collapse navbar-collapse" id="navbar-menu">
                <ul class="navbar-nav">
                    <li class="nav-item {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('user.dashboard') }}" @if(request()->routeIs('user.dashboard')) aria-current="page" @endif>
                            <i class="ti ti-home me-1"></i>
                            <span>首页</span>
                        </a>
                    </li>

                    {{-- 我的简历：简历管理 + 模板库 --}}
                    <li class="nav-item dropdown {{ request()->routeIs('user.resumes.*') || request()->routeIs('user.resume-templates.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <i class="ti ti-file-text me-1"></i>
                            <span>我的简历</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('user.resumes.*') ? 'active' : '' }}" href="{{ route('user.resumes.index') }}">
                                <i class="ti ti-file-text me-2"></i>简历管理
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.resume-templates.*') ? 'active' : '' }}" href="{{ route('user.resume-templates.index') }}">
                                <i class="ti ti-template me-2"></i>模板库
                            </a>
                        </div>
                    </li>

                    {{-- 求职工具：看板 + 岗位匹配 + 智能推荐 --}}
                    <li class="nav-item dropdown {{ request()->routeIs('user.kanban.*') || request()->routeIs('user.jobs.*') || request()->routeIs('user.recommendations.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <i class="ti ti-briefcase me-1"></i>
                            <span>求职工具</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('user.kanban.*') ? 'active' : '' }}" href="{{ route('user.kanban.index') }}">
                                <i class="ti ti-layout-board me-2"></i>求职看板
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.jobs.*') ? 'active' : '' }}" href="{{ route('user.jobs.analyze') }}">
                                <i class="ti ti-target me-2"></i>岗位匹配
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.recommendations.*') ? 'active' : '' }}" href="{{ route('user.recommendations.index') }}">
                                <i class="ti ti-sparkles me-2"></i>智能推荐
                            </a>
                        </div>
                    </li>

                    {{-- AI 助手：模拟面试 + 薪资谈判 + 职业测评 + 技能评估 --}}
                    <li class="nav-item dropdown {{ request()->routeIs('user.interviews.*') || request()->routeIs('user.salary.*') || request()->routeIs('user.assessments.*') || request()->routeIs('user.skills.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <i class="ti ti-robot me-1"></i>
                            <span>AI 助手</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('user.interviews.*') ? 'active' : '' }}" href="{{ route('user.interviews.index') }}">
                                <i class="ti ti-message-chatbot me-2"></i>AI 模拟面试
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.salary.*') ? 'active' : '' }}" href="{{ route('user.salary.index') }}">
                                <i class="ti ti-cash me-2"></i>薪资助手
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item {{ request()->routeIs('user.assessments.*') ? 'active' : '' }}" href="{{ route('user.assessments.index') }}">
                                <i class="ti ti-clipboard-check me-2"></i>职业测评
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.skills.*') ? 'active' : '' }}" href="{{ route('user.skills.index') }}">
                                <i class="ti ti-skill me-2"></i>技能评估
                            </a>
                        </div>
                    </li>

                    {{-- 平台动态：动态 + 帮助 --}}
                    <li class="nav-item dropdown {{ request()->routeIs('public.events.*') || request()->routeIs('public.help.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <i class="ti ti-speakerphone me-1"></i>
                            <span>平台动态</span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('public.events.*') ? 'active' : '' }}" href="{{ route('public.events.index') }}">
                                <i class="ti ti-speakerphone me-2"></i>平台动态
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('public.help.*') ? 'active' : '' }}" href="{{ route('public.help.index') }}">
                                <i class="ti ti-help-hexagon me-2"></i>帮助中心
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        @if(!empty($maintenanceNotice))
            <div class="alert alert-warning rounded-0 mb-0">
                <div class="container-xl py-2">{{ $maintenanceNotice }}</div>
            </div>
        @endif

        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">@yield('page-pretitle', '用户中心')</div>
                        <h2 class="page-title">@yield('page-title', '控制台')</h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        @yield('page-actions')
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body" id="page-body-content">
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

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-triangle icon alert-icon"></i></div>
                            <div>{{ session('warning') }}</div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>

        <footer class="border-top mt-4 py-3">
            <div class="container-xl d-flex flex-column flex-md-row justify-content-between text-secondary small gap-2">
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
    </div>
    
    @include('partials.ui-notify')
    @include('partials.guide-bubbles')

    <script src="{{ asset('js/common/ui-utils.js') }}"></script>
    <script src="{{ asset('js/pages/quota-interceptor.js') }}?v={{ @filemtime(public_path('js/pages/quota-interceptor.js')) ?: time() }}"></script>
    @stack('scripts')
    @yield('scripts')
    <script src="{{ asset('js/pages/layout-user.js') }}"></script>
    <script src="{{ asset('js/theme-switcher.js') }}"></script>
    <script src="{{ asset('js/common/visit-tracker.js') }}"></script>
    
    {{-- Custom Body Code --}}
    {!! $customBodyCode !!}
</body>
</html>
