<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('admin.dashboard') }}">
                <span class="navbar-brand-image me-2 rounded bg-white text-dark d-inline-flex align-items-center justify-content-center fw-bold">AI</span>
                <span class="fw-semibold">{{ $adminSiteName ?? config('app.name') }} 管理</span>
            </a>
        </h1>
        <div class="navbar-nav flex-row d-lg-none">
            <div class="nav-item d-none d-md-flex me-3">
                <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode">
                    <i class="ti ti-moon"></i>
                </a>
                <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode">
                    <i class="ti ti-sun"></i>
                </a>
            </div>
            <div class="nav-item dropdown d-none d-md-flex me-2">
                <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" aria-label="Show notifications">
                    <i class="ti ti-bell"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="#" class="dropdown-item">通知中心</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                    <img src="{{ \Illuminate\Support\Str::avatarSvg(auth()->user()?->email ?? 'admin', 32) }}" class="avatar avatar-sm" alt="">
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <div class="dropdown-header">{{ auth()->user()?->email ?? 'admin@example.com' }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">退出登录</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @foreach (config('admin.menu', []) as $item)
                    @php
                        $user = auth()->user();
                        $itemPermission = $item['permission'] ?? null;
                        $itemVisible = ! is_string($itemPermission) || $itemPermission === '' || ($user && $user->can($itemPermission));
                        if (! $itemVisible) {
                            continue;
                        }

                        $routeName = $item['route'] ?? null;
                        $hasChildren = ! empty($item['children']) && is_array($item['children']);
                        $children = $item['children'] ?? [];
                        if ($hasChildren) {
                            $children = array_values(array_filter($children, function (array $child) use ($user): bool {
                                $permission = $child['permission'] ?? null;

                                return ! is_string($permission) || $permission === '' || ($user && $user->can($permission));
                            }));
                            $hasChildren = $children !== [];
                        }
                        $itemUrl = isset($item['url']) ? $item['url'] : ($routeName ? route($routeName) : '#');
                        $childActive = false;
                        if ($hasChildren) {
                            foreach ($children as $childItem) {
                                $childRouteName = $childItem['route'] ?? null;
                                if ($childRouteName && request()->routeIs($childRouteName)) {
                                    $childActive = true;
                                    break;
                                }
                            }
                        }
                        $isActive = $routeName ? request()->routeIs($routeName) : $childActive;
                    @endphp

                    @if ($hasChildren)
                        <li class="nav-item dropdown {{ $isActive ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle {{ $isActive ? 'show' : '' }}" href="#navbar-{{ $loop->index }}" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="{{ $item['icon'] ?? 'ti ti-folder' }}"></i>
                                </span>
                                <span class="nav-link-title">{{ $item['title'] }}</span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        @foreach ($children as $child)
                                            @php
                                                $childRoute = $child['route'] ?? null;
                                                $childUrl = isset($child['url']) ? $child['url'] : ($childRoute ? route($childRoute) : '#');
                                                $isChildActive = $childRoute ? request()->routeIs($childRoute) : false;
                                            @endphp
                                            <a class="dropdown-item {{ $isChildActive ? 'active' : '' }}" href="{{ $childUrl }}">{{ $child['title'] }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </li>
                    @else
                        <li class="nav-item {{ $isActive ? 'active' : '' }}">
                            <a class="nav-link" href="{{ $itemUrl }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="{{ $item['icon'] ?? 'ti ti-circle' }}"></i>
                                </span>
                                <span class="nav-link-title">{{ $item['title'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</aside>
