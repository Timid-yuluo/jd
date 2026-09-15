@php
    $notifications = config('admin.notifications', []);
    $notificationCount = count($notifications);
    $siteName = $adminSiteName ?? $siteName ?? config('app.name');
    $siteLogo = $siteLogoUrl ?? '';
@endphp

<header class="navbar navbar-expand-md navbar-light d-print-none topbar-main">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <a href="{{ route('admin.dashboard') }}" class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            @if ($siteLogo)
                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="navbar-brand-image me-2" style="max-height: 32px;">
            @else
                <span class="navbar-brand-image me-2 rounded bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold">
                    {{ mb_substr($siteName, 0, 1) }}
                </span>
            @endif
            <span class="fw-semibold">{{ $siteName }} 管理后台</span>
        </a>

        <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item d-flex align-items-center me-2">
                <button class="theme-toggle-btn" data-theme-toggle type="button" title="切换主题">
                    <i class="ti ti-sun theme-icon-light"></i>
                    <i class="ti ti-moon theme-icon-dark" style="display:none;"></i>
                    <i class="ti ti-circle-half theme-icon-auto" style="display:none;"></i>
                    <span class="theme-toggle-label">自动</span>
                </button>
            </div>
            <div class="nav-item dropdown d-none d-md-flex me-3">
                <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="通知">
                    <i class="ti ti-bell"></i>
                    @if ($notificationCount > 0)
                        <span class="badge bg-red"></span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">通知 ({{ $notificationCount }})</h3>
                        </div>
                        <div class="list-group list-group-flush list-group-hoverable">
                            @forelse ($notifications as $notification)
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="status-dot status-dot-animated bg-red d-block"></span>
                                        </div>
                                        <div class="col text-truncate">
                                            <div class="text-body d-block">{{ $notification['title'] }}</div>
                                            <div class="d-block text-secondary text-truncate mt-n1">{{ $notification['time'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-secondary">暂无通知</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="用户菜单">
                    <img src="{{ \Illuminate\Support\Str::avatarSvg(auth()->user()?->email ?? 'admin', 32) }}" class="avatar avatar-sm" alt="">
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ auth()->user()?->name ?? '管理员' }}</div>
                        <div class="mt-1 small text-secondary">{{ auth()->user()?->email ?? '' }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="@if(auth()->user()){{ route('admin.users.show', auth()->user()) }}@else#@endif" class="dropdown-item">个人资料</a>
                    <a href="{{ route('admin.profile.password') }}" class="dropdown-item">修改密码</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">退出登录</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<header class="navbar navbar-expand-md navbar-light d-print-none admin-nav-bar">
    <div class="container-xl">
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                <ul class="navbar-nav flex-wrap admin-nav-menu">
                    @foreach (config('admin.menu', []) as $item)
                        @php
                            $perm = $item['permission'] ?? null;
                            $routeName = $item['route'] ?? null;
                            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
                            $hasChildren = count($children) > 0;
                            $firstChildRoute = $hasChildren ? ($children[0]['route'] ?? null) : null;
                            $itemUrl = isset($item['url'])
                                ? $item['url']
                                : ($routeName
                                    ? route($routeName)
                                    : ($firstChildRoute ? route($firstChildRoute) : '#'));
                            $isActive = $routeName ? request()->routeIs($routeName) : false;
                            if ($hasChildren) {
                                foreach ($children as $child) {
                                    if (!empty($child['route']) && request()->routeIs((string) $child['route'])) {
                                        $isActive = true;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        @if(! $perm || auth()->user()?->can($perm))
                            @if ($hasChildren)
                                <li class="nav-item dropdown flex-shrink-0 {{ $isActive ? 'active' : '' }}">
                                    <a class="nav-link dropdown-toggle admin-nav-link {{ $isActive ? 'active' : '' }}" href="{{ $itemUrl }}" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <i class="{{ $item['icon'] ?? 'ti ti-circle' }}"></i>
                                        </span>
                                        <span class="nav-link-title text-nowrap">{{ $item['title'] }}</span>
                                    </a>
                                    <div class="dropdown-menu admin-dropdown-menu">
                                        @foreach ($children as $child)
                                            @php
                                                $childRoute = $child['route'] ?? null;
                                                $childUrl = $childRoute ? route($childRoute) : ($child['url'] ?? '#');
                                                $childIcon = $child['icon'] ?? '';
                                                $isChildActive = $childRoute && request()->routeIs($childRoute);
                                            @endphp
                                            <a class="dropdown-item admin-dropdown-item {{ $isChildActive ? 'active' : '' }}" href="{{ $childUrl }}">
                                                @if ($childIcon)
                                                    <span class="dropdown-item-icon"><i class="{{ $childIcon }}"></i></span>
                                                @endif
                                                <span class="dropdown-item-text">{{ $child['title'] ?? '未命名菜单' }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </li>
                            @else
                                <li class="nav-item flex-shrink-0 {{ $isActive ? 'active' : '' }}">
                                    <a class="nav-link admin-nav-link {{ $isActive ? 'active' : '' }}" href="{{ $itemUrl }}">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <i class="{{ $item['icon'] ?? 'ti ti-circle' }}"></i>
                                        </span>
                                        <span class="nav-link-title text-nowrap">{{ $item['title'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</header>
