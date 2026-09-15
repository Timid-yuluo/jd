<?php

/**
 * OPcache 预加载脚本
 * 在 PHP-FPM 启动时将 Laravel 核心类加载到共享内存，减少首次请求延迟。
 *
 * 使用方式：在 php.ini 中配置
 *   opcache.preload=/www/wwwroot/124.221.19.20/preload.php
 *   opcache.preload_user=www
 */

$basePath = __DIR__;

// Laravel 核心框架类
$coreClasses = [
    // Illuminate 基础
    'Illuminate\Foundation\Application',
    'Illuminate\Foundation\Http\Kernel',
    'Illuminate\Foundation\Console\Kernel',
    'Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables',
    'Illuminate\Foundation\Bootstrap\LoadConfiguration',
    'Illuminate\Foundation\Bootstrap\HandleExceptions',
    'Illuminate\Foundation\Bootstrap\RegisterFacades',
    'Illuminate\Foundation\Bootstrap\RegisterProviders',
    'Illuminate\Foundation\Bootstrap\BootProviders',
    'Illuminate\Foundation\AliasLoader',

    // HTTP 核心
    'Illuminate\Http\Request',
    'Illuminate\Http\Response',
    'Illuminate\Http\JsonResponse',
    'Illuminate\Routing\Router',
    'Illuminate\Routing\Route',
    'Illuminate\Routing\RouteCollection',
    'Illuminate\Routing\UrlGenerator',
    'Illuminate\Routing\Middleware\ThrottleRequests',

    // 数据库核心
    'Illuminate\Database\Connection',
    'Illuminate\Database\ConnectionResolver',
    'Illuminate\Database\Eloquent\Model',
    'Illuminate\Database\Eloquent\Builder',
    'Illuminate\Database\Query\Builder',
    'Illuminate\Database\Query\Grammars\MySqlGrammar',
    'Illuminate\Database\Schema\Builder',

    // 缓存核心
    'Illuminate\Cache\CacheManager',
    'Illuminate\Cache\Repository',
    'Illuminate\Cache\FileStore',
    'Illuminate\Cache\RedisStore',
    'Illuminate\Cache\DatabaseStore',

    // Session
    'Illuminate\Session\SessionManager',
    'Illuminate\Session\Store',
    'Illuminate\Session\FileSessionHandler',

    // Auth
    'Illuminate\Auth\AuthManager',
    'Illuminate\Auth\SessionGuard',
    'Illuminate\Auth\RequestGuard',

    // View
    'Illuminate\View\Factory',
    'Illuminate\View\Engines\CompilerEngine',
    'Illuminate\View\Compilers\BladeCompiler',
    'Illuminate\View\FileViewFinder',

    // Events
    'Illuminate\Events\Dispatcher',

    // Container
    'Illuminate\Container\Container',

    // Pipeline
    'Illuminate\Pipeline\Pipeline',

    // Support
    'Illuminate\Support\Facades\Facade',
    'Illuminate\Support\Collection',
    'Illuminate\Support\Str',
    'Illuminate\Support\Arr',
    'Illuminate\Support\Carbon',

    // Filesystem
    'Illuminate\Filesystem\Filesystem',
    'Illuminate\Filesystem\FilesystemManager',
];

// 应用核心模型（高频访问）
$appModels = [
    'App\Models\User',
    'App\Models\Resume',
    'App\Models\SystemSetting',
    'App\Models\InterviewSession',
    'App\Models\InterviewQuestion',
    'App\Models\CareerTrack',
    'App\Models\ResumeVersion',
    'App\Models\Plan',
    'App\Models\UserNotification',
    'App\Models\AdminNotification',
    'App\Models\PageVisit',
];

// 应用核心服务
$appServices = [
    'App\Services\Admin\SystemSettingService',
    'App\Services\VisitTrackingService',
    'App\Services\UserAgentParser',
    'App\Services\IpLocationService',
];

// 应用核心中间件
$appMiddleware = [
    'App\Http\Middleware\SecurityHeaders',
    'App\Http\Middleware\TrackPageVisit',
    'App\Http\Middleware\CheckMaintenanceMode',
    'App\Http\Middleware\EnsureEmailVerificationRequired',
    'App\Http\Middleware\EnsureUserIsActive',
    'App\Http\Middleware\RedirectIfMobile',
    'App\Http\Middleware\LogUserActions',
    'App\Http\Middleware\ApplyDynamicSessionLifetime',
];

// 应用核心 Trait（必须在 Controller 之前加载）
$appTraits = [
    'App\Http\Concerns\ApiResponse',
];

// 应用基础控制器（必须在子控制器之前加载，且依赖上述 Trait）
$appBaseControllers = [
    'App\Http\Controllers\Controller',
    'App\Http\Controllers\User\DashboardController',
];

$allClasses = array_merge($coreClasses, $appModels, $appServices, $appMiddleware, $appTraits, $appBaseControllers);

$loaded = 0;
$skipped = 0;

foreach ($allClasses as $class) {
    // 已加载则跳过
    if (class_exists($class, false) || trait_exists($class, false)) {
        $skipped++;
        continue;
    }

    try {
        // 先尝试作为 trait 加载
        if (trait_exists($class)) {
            $loaded++;
            continue;
        }

        // 再尝试作为 class 加载
        if (! class_exists($class)) {
            $skipped++;
            continue;
        }

        // 通过反射预加载类及其依赖
        $reflection = new ReflectionClass($class);

        // 预加载父类
        if ($parent = $reflection->getParentClass()) {
            if (! $parent->isInternal()) {
                class_exists($parent->getName());
            }
        }

        $loaded++;
    } catch (\Throwable) {
        $skipped++;
    }
}

// 预加载 Composer autoload_classmap 中的高频控制器（使用 opcache_compile_file 避免触发副作用）
$autoloadFile = $basePath . '/vendor/composer/autoload_classmap.php';
if (file_exists($autoloadFile)) {
    $classMap = include $autoloadFile;
    // 先加载基础 Controller
    $controllerBase = 'App\\Http\\Controllers\\Controller';
    if (isset($classMap[$controllerBase])) {
        class_exists($controllerBase);
    }
    // 仅预编译高频使用的用户端和 API 控制器，避免预加载低频管理控制器浪费共享内存
    $highPriorityPatterns = [
        'App\\Http\\Controllers\\User\\',
        'App\\Http\\Controllers\\Api\\',
        'App\\Http\\Controllers\\Auth\\',
        'App\\Http\\Controllers\\Controller',
    ];
    foreach ($classMap as $class => $file) {
        if (! str_starts_with($class, 'App\\Http\\Controllers\\')) {
            continue;
        }
        $isHighPriority = false;
        foreach ($highPriorityPatterns as $pattern) {
            if (str_starts_with($class, $pattern)) {
                $isHighPriority = true;
                break;
            }
        }
        if (! $isHighPriority) {
            continue;
        }
        // Use opcache_compile_file to preload without triggering autoload side effects
        if (file_exists($file)) {
            @opcache_compile_file($file);
        }
    }
}
