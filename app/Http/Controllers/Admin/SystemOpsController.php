<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SystemOpsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * 系统运维控制器 — 数据采集委托 SystemOpsService
 */
final class SystemOpsController extends Controller
{
    public function __construct(
        private readonly SystemOpsService $opsService,
    ) {}

    public function index(): View
    {
        $systemInfo = $this->opsService->getSystemInfo();
        $queueStats = $this->opsService->getQueueStats();
        $cacheStats = $this->opsService->getCacheStats();
        $dbStats = $this->opsService->getDatabaseStats();
        $failedJobs = $this->opsService->getFailedJobs();

        return view('admin.system-ops.index', compact(
            'systemInfo',
            'queueStats',
            'cacheStats',
            'dbStats',
            'failedJobs'
        ));
    }

    public function clearCache(Request $request): JsonResponse
    {
        $type = $request->get('type', 'all');

        try {
            match ($type) {
                'application' => Artisan::call('cache:clear'),
                'config' => Artisan::call('config:clear'),
                'route' => Artisan::call('route:clear'),
                'view' => Artisan::call('view:clear'),
                'compiled' => Artisan::call('clear-compiled'),
                default => (function () {
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');
                    Artisan::call('clear-compiled');
                })(),
            };

            Log::info('Cache cleared by admin', ['type' => $type, 'user' => auth()->user()?->id]);

            return response()->json([
                'success' => true,
                'message' => '缓存清除成功',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function optimize(): JsonResponse
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            Log::info('System optimized by admin', ['user' => auth()->user()?->id]);

            return response()->json([
                'success' => true,
                'message' => '系统优化完成',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function queueAction(Request $request): JsonResponse
    {
        $action = $request->get('action');

        try {
            match ($action) {
                'restart' => Artisan::call('queue:restart'),
                'retry' => Artisan::call('queue:retry', ['id' => ['all']]),
                'flush' => Artisan::call('queue:flush'),
                default => throw new \InvalidArgumentException('Unknown action'),
            };

            return response()->json([
                'success' => true,
                'message' => '队列操作成功',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 重试单个失败任务
     */
    public function retryFailedJob(int $id): JsonResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => [(string) $id]]);

            return response()->json([
                'success' => true,
                'message' => "任务 #{$id} 已重新加入队列",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除单个失败任务
     */
    public function forgetFailedJob(int $id): JsonResponse
    {
        try {
            Artisan::call('queue:forget', ['id' => (string) $id]);

            return response()->json([
                'success' => true,
                'message' => "任务 #{$id} 已删除",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function queueStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->opsService->getQueueStats(),
        ]);
    }

    public function migrate(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            Log::info('Migration run by admin', ['user' => auth()->user()?->id]);

            return response()->json([
                'success' => true,
                'message' => '数据库迁移完成',
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function runCommand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => 'required|string',
            'args' => 'nullable|array',
            'args.*' => 'string|max:255',
        ]);

        $command = $validated['command'];
        $args = $validated['args'] ?? [];

        $allowedCommandsWithArgs = [
            'migrate:status' => [],
            'storage:link' => [],
            'route:list' => ['columns', 'sort', 'path', 'method'],
            'schedule:list' => [],
        ];

        if (! isset($allowedCommandsWithArgs[$command])) {
            return response()->json([
                'success' => false,
                'message' => '命令不在允许列表中',
            ], 403);
        }

        $allowedArgs = $allowedCommandsWithArgs[$command];
        $filteredArgs = [];
        foreach ($args as $key => $value) {
            if (in_array((string) $key, $allowedArgs, true)) {
                $filteredArgs[$key] = $value;
            }
        }

        try {
            Artisan::call($command, $filteredArgs);
            $output = Artisan::output();

            Log::info('Artisan command run by admin', [
                'command' => $command,
                'args' => $filteredArgs,
                'user' => auth()->user()?->id,
            ]);

            return response()->json([
                'success' => true,
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function logs(): View
    {
        $logs = $this->opsService->parseLogs();

        return view('admin.system-ops.logs', compact('logs'));
    }
}
