<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

final class ScheduleController extends Controller
{
    /**
     * 定时任务列表
     */
    public function index(): View
    {
        $tasks = $this->getScheduledTasks();
        $stats = [
            'total_tasks' => count($tasks),
            'enabled_tasks' => count(array_filter($tasks, fn ($t) => $t['enabled'])),
            'last_run' => ScheduleLog::where('status', 'success')->latest()->first()?->created_at,
            'today_runs' => ScheduleLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.schedule.index', compact('tasks', 'stats'));
    }

    /**
     * 获取定时任务详情
     */
    public function show(string $task): View
    {
        $tasks = $this->getScheduledTasks();
        $taskData = $tasks[$task] ?? abort(404);

        $logs = ScheduleLog::where('task_name', $task)
            ->latest()
            ->paginate((int) config('ui.pagination.admin_table', 20));

        $stats = [
            'total_runs' => ScheduleLog::where('task_name', $task)->count(),
            'success_runs' => ScheduleLog::where('task_name', $task)->where('status', 'success')->count(),
            'failed_runs' => ScheduleLog::where('task_name', $task)->where('status', 'failed')->count(),
            'avg_duration' => ScheduleLog::where('task_name', $task)
                ->whereNotNull('duration_ms')
                ->avg('duration_ms'),
        ];

        return view('admin.schedule.show', compact('task', 'taskData', 'logs', 'stats'));
    }

    /**
     * 手动运行任务
     */
    public function run(Request $request, string $task): JsonResponse
    {
        $tasks = $this->getScheduledTasks();
        if (! isset($tasks[$task])) {
            return $this->fail('任务不存在', 404);
        }

        $taskData = $tasks[$task];
        $log = new ScheduleLog([
            'task_name' => $task,
            'task_description' => $taskData['description'],
            'status' => 'running',
            'triggered_by' => auth()->id(),
        ]);
        $log->save();

        try {
            $startTime = microtime(true);

            if ($taskData['type'] === 'command') {
                Artisan::call($taskData['command'], $taskData['parameters'] ?? []);
                $output = Artisan::output();
            } else {
                // 对于闭包任务，记录提示信息
                $output = 'Closure tasks cannot be run manually. Please use the schedule:run command.';
            }

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $log->update([
                'status' => 'success',
                'output' => $output,
                'duration_ms' => $duration,
            ]);

            return response()->json([
                'success' => true,
                'message' => '任务执行成功',
                'output' => $output,
                'duration' => $duration,
            ]);
        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $log->update([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            Log::error("Scheduled task {$task} failed: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '任务执行失败: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 任务执行历史
     */
    public function logs(Request $request): View
    {
        $query = ScheduleLog::query();

        if ($task = $request->input('task')) {
            $query->where('task_name', $task);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $logs = $query->latest()->paginate((int) config('ui.pagination.admin_table', 20))->appends($request->only([
            'task', 'status',
        ]));
        $tasks = ScheduleLog::distinct()->pluck('task_name');

        return view('admin.schedule.logs', compact('logs', 'tasks'));
    }

    /**
     * 清空历史记录
     */
    public function clearLogs(Request $request): RedirectResponse
    {
        $days = $request->input('days', 30);
        $date = now()->subDays($days);

        $count = ScheduleLog::where('created_at', '<', $date)->delete();

        return redirect()->route('admin.schedule.logs')
            ->with('success', "已清理 {$days} 天前的 {$count} 条历史记录。");
    }

    /**
     * 获取调度状态
     */
    public function status(): JsonResponse
    {
        // 检查 schedule:run 是否已配置到 cron
        $cronConfigured = $this->isCronConfigured();

        // 获取最近执行时间
        $lastRun = ScheduleLog::latest()->first();

        return response()->json([
            'cron_configured' => $cronConfigured,
            'last_run_at' => $lastRun?->created_at?->toIso8601String(),
            'last_run_status' => $lastRun?->status,
            'next_runs' => $this->getNextRunTimes(),
        ]);
    }

    /**
     * 获取所有计划任务
     */
    private function getScheduledTasks(): array
    {
        // 从 console.php 读取配置
        $tasks = [
            'notifications:send-scheduled' => [
                'description' => '发送定时通知',
                'schedule' => '每分钟',
                'cron' => '* * * * *',
                'command' => 'notifications:send-scheduled',
                'parameters' => [],
                'type' => 'command',
                'enabled' => true,
            ],
            'notifications:send-reminders-deadline' => [
                'description' => '发送申请截止提醒（3天内）',
                'schedule' => '每天 09:00',
                'cron' => '0 9 * * *',
                'command' => 'notifications:send-reminders',
                'parameters' => ['--type' => 'deadline', '--days' => 3],
                'type' => 'command',
                'enabled' => true,
            ],
            'notifications:send-reminders-interview' => [
                'description' => '发送面试未完成提醒',
                'schedule' => '每天 10:00',
                'cron' => '0 10 * * *',
                'command' => 'notifications:send-reminders',
                'parameters' => ['--type' => 'interview'],
                'type' => 'command',
                'enabled' => true,
            ],
            'oauth:diagnose-health' => [
                'description' => 'OAuth 健康巡检与告警',
                'schedule' => '每小时',
                'cron' => '0 * * * *',
                'command' => 'oauth:diagnose',
                'parameters' => ['--hours' => 1, '--alert-min-failures' => 3, '--alert-failure-rate' => 80, '--fail-on-alert' => true],
                'type' => 'command',
                'enabled' => true,
            ],
            'scrape:offerstar' => [
                'description' => '外部招聘数据采集（OfferStar）',
                'schedule' => '每小时',
                'cron' => '0 * * * *',
                'command' => 'scrape:offerstar',
                'parameters' => ['--page' => 1, '--pages' => 2, '--delay' => 1, '--auto-approve' => 0],
                'type' => 'command',
                'enabled' => true,
            ],
            'scrape:qiuzhifangzhou-campus' => [
                'description' => '外部招聘数据采集（求职方舟-校招）',
                'schedule' => '每2小时',
                'cron' => '0 */2 * * *',
                'command' => 'scrape:qiuzhifangzhou-campus',
                'parameters' => ['--days' => 7, '--auto-approve' => 0],
                'type' => 'command',
                'enabled' => true,
            ],
            'scrape:qiuzhifangzhou-position' => [
                'description' => '外部招聘数据采集（求职方舟-职位流，含社招候选）',
                'schedule' => '每2小时',
                'cron' => '0 */2 * * *',
                'command' => 'scrape:qiuzhifangzhou-position',
                'parameters' => ['--auto-approve' => 0],
                'type' => 'command',
                'enabled' => true,
            ],
            'links:probe-external-recruitments' => [
                'description' => '外部招聘投递链接巡检（失效自动回流待审核）',
                'schedule' => '每6小时',
                'cron' => '0 */6 * * *',
                'command' => 'links:probe-external-recruitments',
                'parameters' => ['--limit' => 300, '--only-approved' => 1, '--mark-pending' => 1, '--dry-run' => 0],
                'type' => 'command',
                'enabled' => true,
            ],
        ];

        // 检查是否有额外的定时任务配置
        $extraTasks = config('schedule.tasks', []);
        foreach ($extraTasks as $key => $task) {
            $tasks[$key] = array_merge([
                'type' => 'command',
                'enabled' => true,
                'parameters' => [],
            ], $task);
        }

        return $tasks;
    }

    /**
     * 检查 Cron 是否配置
     */
    private function isCronConfigured(): bool
    {
        try {
            $result = Process::run('crontab -l');
            $cronTab = $result->output();

            return str_contains($cronTab, 'schedule:run');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取下次执行时间
     */
    private function getNextRunTimes(): array
    {
        $tasks = $this->getScheduledTasks();
        $nextRuns = [];

        foreach ($tasks as $key => $task) {
            // 简化计算，实际应该使用 CronExpression 库
            $nextRuns[$key] = match ($task['cron']) {
                '* * * * *' => now()->addMinute()->startOfMinute(),
                '0 9 * * *' => now()->hour < 9 ? today()->setHour(9) : today()->addDay()->setHour(9),
                '0 10 * * *' => now()->hour < 10 ? today()->setHour(10) : today()->addDay()->setHour(10),
                '0 */2 * * *' => now()->addHours(2)->startOfHour(),
                '0 */6 * * *' => now()->addHours(6)->startOfHour(),
                default => now()->addHour(),
            };
        }

        return $nextRuns;
    }
}
