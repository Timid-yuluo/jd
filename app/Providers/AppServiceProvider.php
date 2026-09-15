<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Events\DomainEventBus;
use App\Domain\Events\DomainEventBusInterface;
use App\Events\AccessBansExpired;
use App\Infrastructure\System\Contracts\SystemResourceMonitor;
use App\Infrastructure\System\LinuxSystemResourceMonitor;
use App\Listeners\ClearExpiredBanCache;
use App\Listeners\SendExceptionEmailListener;
use App\Models\Feedback;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\ScheduleLog;
use App\Observers\InterviewSessionObserver;
use App\Observers\JobApplicationObserver;
use App\Observers\ResumeObserver;
use App\Observers\UserObserver;
use App\Policies\FeedbackPolicy;
use App\Policies\InterviewPolicy;
use App\Policies\JobApplicationPolicy;
use App\Policies\ResumePolicy;
use App\Policies\UserNotificationPolicy;
use App\Services\Admin\MailRuntimeConfigService;
use App\Services\Admin\SystemSettingService;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 领域事件总线：基于 Laravel Event Dispatcher 实现
        $this->app->singleton(DomainEventBusInterface::class, static function ($app) {
            return new DomainEventBus($app->make(\Illuminate\Contracts\Events\Dispatcher::class));
        });

        $this->app->singleton(SystemResourceMonitor::class, LinuxSystemResourceMonitor::class);
    }

    public function boot(): void
    {
        $this->configureRuntimeFilesystemFallbacks();
        $this->registerExceptionEmailListener();
        $this->registerSecurityEventListeners();
        $this->configureGuestRedirect();
        Paginator::useBootstrapFive();
        $this->configureUrlScheme();
        $this->configureMailFromSettings();
        $this->configureSlowQueryLogging();
        $this->configureScheduleLogging();

        // 非生产环境启用严格模式，自动检测 N+1 查询和静默丢弃属性
        Model::preventLazyLoading(!$this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());

        // Admin bypass：仅对查看/列表操作放行，写操作仍需走 Policy 检查
        // 安全边界：admin 可查看任意资源，但不能绕过 ownership 检查进行修改/删除
        Gate::before(function ($user, $ability, $arguments) {
            if (! $user instanceof User || ! $user->is_admin) {
                return null;
            }

            // 仅 view/viewAny 放行，其余能力（update/delete/forceDelete 等）必须通过 Policy
            return in_array($ability, ['view', 'viewAny'], true) ? true : null;
        });

        Gate::policy(Resume::class, ResumePolicy::class);
        Gate::policy(InterviewSession::class, InterviewPolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
        Gate::policy(UserNotification::class, UserNotificationPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);

        Password::defaults(function (): Password {
            $minLength = max(8, (int) app(SystemSettingService::class)->get('password_min_length', '8'));

            return Password::min($minLength)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // 注册模型观察者
        Resume::observe(ResumeObserver::class);
        InterviewSession::observe(InterviewSessionObserver::class);
        JobApplication::observe(JobApplicationObserver::class);
        User::observe(UserObserver::class);
    }

    private function configureMailFromSettings(): void
    {
        try {
            app(MailRuntimeConfigService::class)->applyFromSystemSettings();
        } catch (\Throwable $e) {
            // 首次迁移前表不存在，静默忽略
        }
    }

    private function configureSlowQueryLogging(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $slowQueryThreshold = (int) env('SLOW_QUERY_THRESHOLD_MS', 200);

        DB::listen(function ($query) use ($slowQueryThreshold): void {
            $time = $query->time;

            if ($time >= $slowQueryThreshold) {
                try {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings_count' => count($query->bindings),
                        'time_ms' => $time,
                        'url' => request()->fullUrl(),
                    ]);
                } catch (\Throwable) {
                    // 日志路径不可写时，避免放大为 500
                }
            }
        });
    }

    private function configureRuntimeFilesystemFallbacks(): void
    {
        $tmpBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'zhilutong-runtime';
        $fallbackViewPath = $tmpBase.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views';

        $compiledPath = storage_path('framework/views');
        if (! is_dir($compiledPath)) {
            @mkdir($compiledPath, 0775, true);
        }
        if (! is_writable($compiledPath)) {
            @mkdir($fallbackViewPath, 0775, true);
            if (is_writable($fallbackViewPath)) {
                config(['view.compiled' => $fallbackViewPath]);
            }
        }

        $logsPath = storage_path('logs');
        if (! is_dir($logsPath)) {
            @mkdir($logsPath, 0775, true);
        }
        if (! is_writable($logsPath)) {
            config([
                'logging.default' => 'errorlog',
                'logging.channels.stack.channels' => ['errorlog'],
            ]);
        }
    }

    private function configureUrlScheme(): void
    {
        $appUrl = trim((string) config('app.url', ''));
        if ($appUrl !== '' && str_starts_with(strtolower($appUrl), 'https://')) {
            URL::forceScheme('https');
            URL::forceRootUrl($appUrl);

            return;
        }

        /** @var Request $request */
        $request = request();
        $forwardedProto = strtolower((string) $request->headers->get('x-forwarded-proto', ''));
        $isHttpsRequest = $request->isSecure() || str_contains($forwardedProto, 'https');

        if (! $isHttpsRequest) {
            return;
        }

        URL::forceScheme('https');
        URL::forceRootUrl('https://'.$request->getHttpHost());
    }

    private function registerExceptionEmailListener(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (empty(config('exception-email.recipients'))) {
            return;
        }

        Event::listen(
            \Illuminate\Log\Events\MessageLogged::class,
            SendExceptionEmailListener::class,
        );
    }

    private function registerSecurityEventListeners(): void
    {
        Event::listen(
            AccessBansExpired::class,
            ClearExpiredBanCache::class,
        );
    }

    private function configureGuestRedirect(): void
    {
        \Illuminate\Auth\Middleware\RedirectIfAuthenticated::redirectUsing(function ($request) {
            if (Route::has('user.dashboard')) {
                return route('user.dashboard');
            }

            return '/';
        });
    }

    private function configureScheduleLogging(): void
    {
        Event::listen(ScheduledTaskStarting::class, function (ScheduledTaskStarting $event): void {
            $taskName = $event->task->command ?? $event->task->description ?? 'unknown';
            if (preg_match('/artisan\s+(\S+)/', $taskName, $m)) {
                $taskName = $m[1];
            }
            $event->task->storeLog = ScheduleLog::create([
                'task_name' => mb_substr($taskName, 0, 100),
                'task_description' => $event->task->description ?? '',
                'status' => 'running',
            ]);
        });

        Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event): void {
            $log = $event->task->storeLog ?? null;
            if (! $log instanceof ScheduleLog) {
                return;
            }

            try {
                $exitCode = property_exists($event, 'exitCode') ? $event->exitCode : ($event->task->exitCode ?? 0);
                $duration = $log->created_at ? max(0, min((int) now()->diffInMilliseconds($log->created_at), 2147483647)) : null;
                $log->update([
                    'status' => $exitCode === 0 ? 'success' : 'failed',
                    'output' => $exitCode !== 0 ? "Exit code: {$exitCode}" : null,
                    'duration_ms' => $duration,
                ]);
            } catch (\Throwable $e) {
                // Silent fail for schedule logging
            }
        });
    }
}
