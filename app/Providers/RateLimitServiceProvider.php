<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * 限流策略定义 — 从 AppServiceProvider 抽离
 */
final class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('resume-ai-section', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            $limit = $userKey !== null
                ? max(1, (int) config('resume.ai_throttle.section_per_minute', 12))
                : max(1, (int) config('resume.ai_throttle.by_ip_fallback_per_minute', 20));

            return Limit::perMinute($limit)->by('resume-ai-section:'.$signature);
        });

        RateLimiter::for('resume-ai-heavy', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            $limit = $userKey !== null
                ? max(1, (int) config('resume.ai_throttle.heavy_per_minute', 4))
                : max(1, (int) config('resume.ai_throttle.by_ip_fallback_per_minute', 20));

            return Limit::perMinute($limit)->by('resume-ai-heavy:'.$signature);
        });

        RateLimiter::for('resume-write', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            $limit = $userKey !== null ? 30 : 20;

            return Limit::perMinute($limit)->by('resume-write:'.$signature);
        });

        RateLimiter::for('export-task-create', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            $limit = $userKey !== null ? 8 : 5;

            return Limit::perMinute($limit)->by('export-task-create:'.$signature);
        });

        RateLimiter::for('profile-write', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            return Limit::perMinute(10)->by('profile-write:'.$signature);
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by('login:'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request): Limit {
            return Limit::perHour(20)->by('register:'.$request->ip());
        });

        RateLimiter::for('feedback-submit', function (Request $request): Limit {
            return Limit::perMinute(3)->by('feedback:'.$request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('feedback-admin-write', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            return Limit::perMinute(20)->by('feedback-admin-write:'.$signature);
        });

        RateLimiter::for('feedback-admin-reward', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            return Limit::perMinute(6)->by('feedback-admin-reward:'.$signature);
        });

        RateLimiter::for('interview-ai', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            return Limit::perMinute(15)->by('interview-ai:'.$signature);
        });

        RateLimiter::for('job-match-analyze', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            $limit = max(1, (int) config('job-matching.analyze_throttle_per_minute', 6));

            return Limit::perMinute($limit)->by('job-match-analyze:'.$signature);
        });

        RateLimiter::for('global', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            return Limit::perMinute(120)->by('global:'.$signature);
        });

        RateLimiter::for('account-delete', function (Request $request): Limit {
            return Limit::perHour(3)->by('account-delete:'.$request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            return Limit::perMinute(60)->by('api:'.$signature);
        });

        // 智能岗位推荐生成限流：每用户每小时 3 次（生成较耗时）
        RateLimiter::for('recommend-generate', function (Request $request): Limit {
            $userKey = $request->user()?->id;
            $signature = $userKey !== null
                ? 'user:'.$userKey
                : 'ip:'.$request->ip();

            return Limit::perHour(3)->by('recommend-generate:'.$signature);
        });
    }
}
