<?php

use App\Exceptions\AiServiceUnavailableException;
use App\Http\Middleware\ApplyDynamicSessionLifetime;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckQuota;
use App\Http\Middleware\TrackPageVisit;
use App\Http\Middleware\EnsureEmailVerificationRequired;
use App\Http\Middleware\AdminLoginThrottle;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\LimitRequestBody;
use App\Http\Middleware\LogAdminActions;
use App\Http\Middleware\LogUserActions;
use App\Http\Middleware\RedirectIfMobile;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web_public.php',
            __DIR__.'/../routes/web_user.php',
            __DIR__.'/../routes/web_admin.php',
        ],
        api: __DIR__.'/../routes/api_v1.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->append(LimitRequestBody::class);

        $middleware->prependToGroup('web', ApplyDynamicSessionLifetime::class);
        $middleware->appendToGroup('web', \Illuminate\Routing\Middleware\ThrottleRequests::class.':global');
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
        $middleware->appendToGroup('web', TrackPageVisit::class);

        $middleware->trustProxies(at: array_merge(
            ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            array_filter(explode(',', (string) env('TRUSTED_PROXIES', '')))
        ));

        // 支付回调路由排除 CSRF 验证
        $middleware->validateCsrfTokens(except: [
            'alipay-pay/notify',
            'csp-report',
            'api/track/*',
        ]);

        $middleware->alias([
            'mobile' => RedirectIfMobile::class,
            'force.json' => ForceJsonResponse::class,
            'request.id' => RequestIdMiddleware::class,
            'admin' => EnsureIsAdmin::class,
            'admin.throttle' => AdminLoginThrottle::class,
            'admin.log' => LogAdminActions::class,
            'user.log' => LogUserActions::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'maintenance' => CheckMaintenanceMode::class,
            'email.verification.required' => EnsureEmailVerificationRequired::class,
            'quota' => CheckQuota::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = [
                        'field' => (string) $field,
                        'message' => (string) $message,
                    ];
                }
            }

            return response()->json([
                'code' => 10001,
                'message' => '参数校验失败',
                'errors' => $errors,
                'trace_id' => $request->attributes->get('request_id'),
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => 10004,
                'message' => '资源不存在',
                'trace_id' => $request->attributes->get('request_id'),
            ], 404);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => 10003,
                'message' => '无权访问该资源',
                'trace_id' => $request->attributes->get('request_id'),
            ], 403);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if (! ($request->expectsJson() || $request->ajax() || $request->is('user/resumes/*'))) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => '操作过于频繁，请稍后再试。',
                'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            ], 429, $e->getHeaders());
        });

        $exceptions->render(function (AiServiceUnavailableException $e, Request $request) {
            \Illuminate\Support\Facades\Log::warning($e->event(), array_merge($e->context(), [
                'error' => $e->getPrevious()?->getMessage() ?? $e->getMessage(),
            ]));

            if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => 50000,
                'message' => '系统内部错误',
                'trace_id' => $request->attributes->get('request_id'),
            ], 500);
        });
    })->create();
