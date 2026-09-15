<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Resume\ResumeOptimizeStateMachine;
use App\Infrastructure\AI\AiFallbackExecutor;
use App\Infrastructure\AI\AiManager;
use App\Infrastructure\AI\Contracts\AiProvider;
use App\Services\Admin\AiConfigService;
use Illuminate\Support\ServiceProvider;

/**
 * AI 相关服务绑定与运行时配置
 */
final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiManager::class, static function ($app) {
            return new AiManager($app);
        });

        $this->app->singleton(AiFallbackExecutor::class, static function ($app) {
            return new AiFallbackExecutor($app->make(AiManager::class));
        });

        $this->app->singleton(AiProvider::class, static function ($app) {
            return $app->make(AiManager::class)->provider();
        });

        // 简历优化状态机：无状态依赖，直接绑定为 singleton
        $this->app->singleton(ResumeOptimizeStateMachine::class);
    }

    public function boot(): void
    {
        try {
            app(AiConfigService::class)->applyRuntimeConfig();
        } catch (\Throwable) {
            // 首次部署或系统设置表不可用时，回退到静态配置
        }
    }
}
