<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\InterviewSessionMail;
use App\Mail\JobApplicationMail;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Services\Notification\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 发送业务提醒邮件
 * 包括：申请截止提醒、面试未完成提醒等
 */
class SendBusinessReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-reminders
                            {--type=all : 提醒类型 (all|deadline|interview)}
                            {--days=3 : 截止前多少天发送提醒}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送业务相关的提醒邮件（截止提醒、面试提醒等）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $days = (int) $this->option('days');

        $this->info("开始发送业务提醒... [类型: {$type}, 提前天数: {$days}]");

        $totalSuccess = 0;
        $totalFailed = 0;

        // 发送申请截止提醒
        if (in_array($type, ['all', 'deadline'])) {
            $result = $this->sendDeadlineReminders($days);
            $totalSuccess += $result['success'];
            $totalFailed += $result['failed'];
        }

        // 发送面试未完成提醒
        if (in_array($type, ['all', 'interview'])) {
            $result = $this->sendInterviewReminders();
            $totalSuccess += $result['success'];
            $totalFailed += $result['failed'];
        }

        $this->info("提醒发送完成：成功 {$totalSuccess} 条，失败 {$totalFailed} 条");

        Log::info('业务提醒发送完成', [
            'type' => $type,
            'days' => $days,
            'success' => $totalSuccess,
            'failed' => $totalFailed,
        ]);

        return self::SUCCESS;
    }

    /**
     * 发送申请截止提醒
     */
    private function sendDeadlineReminders(int $days): array
    {
        $this->info("正在查找 {$days} 天内截止的申请...");

        $targetDate = Carbon::now()->addDays($days);
        $startDate = Carbon::now();

        // 获取即将截止的申请
        $applications = JobApplication::whereNotNull('deadline')
            ->whereDate('deadline', '<=', $targetDate)
            ->whereDate('deadline', '>=', $startDate)
            ->whereIn('status', ['applied', 'screening'])
            ->where('reminder_sent', false)
            ->with('user')
            ->get();

        if ($applications->isEmpty()) {
            $this->info('没有找到需要发送截止提醒的申请。');

            return ['success' => 0, 'failed' => 0];
        }

        $this->info("找到 {$applications->count()} 条需要发送截止提醒的申请。");

        $emailService = app(EmailNotificationService::class);
        $successCount = 0;
        $failCount = 0;

        foreach ($applications as $application) {
            try {
                $user = $application->user;

                if (! $user) {
                    $this->warn("申请 {$application->id} 没有关联用户，跳过。");

                    continue;
                }

                if ($emailService->sendJobApplication(
                    $user,
                    $application,
                    JobApplicationMail::TYPE_DEADLINE_REMINDER
                )) {
                    $successCount++;
                    // 标记已发送提醒
                    $application->update(['reminder_sent' => true]);
                    $this->info("已发送截止提醒给 {$user->email} [{$application->company}]");
                } else {
                    $failCount++;
                    $this->warn("发送失败: {$user->email}");
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error('发送截止提醒失败', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("发送失败: {$application->id} - {$e->getMessage()}");
            }
        }

        $this->info("截止提醒发送完成：成功 {$successCount} 条，失败 {$failCount} 条");

        return ['success' => $successCount, 'failed' => $failCount];
    }

    /**
     * 发送面试未完成提醒
     */
    private function sendInterviewReminders(): array
    {
        $this->info('正在查找未完成的面试会话...');

        // 获取进行中的面试会话（超过24小时未完成的）
        $cutoffTime = Carbon::now()->subHours(24);

        $sessions = InterviewSession::where('status', 'in_progress')
            ->where('updated_at', '<', $cutoffTime)
            ->where('reminder_sent', false)
            ->with('user')
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('没有找到需要发送提醒的面试会话。');

            return ['success' => 0, 'failed' => 0];
        }

        $this->info("找到 {$sessions->count()} 条需要发送提醒的面试会话。");

        $emailService = app(EmailNotificationService::class);
        $successCount = 0;
        $failCount = 0;

        foreach ($sessions as $session) {
            try {
                $user = $session->user;

                if (! $user) {
                    $this->warn("会话 {$session->id} 没有关联用户，跳过。");

                    continue;
                }

                if ($emailService->sendInterviewSession(
                    $user,
                    $session,
                    InterviewSessionMail::TYPE_REMINDER
                )) {
                    $successCount++;
                    // 标记已发送提醒
                    $session->update(['reminder_sent' => true]);
                    $this->info("已发送面试提醒给 {$user->email} [{$session->company}]");
                } else {
                    $failCount++;
                    $this->warn("发送失败: {$user->email}");
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error('发送面试提醒失败', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("发送失败: {$session->id} - {$e->getMessage()}");
            }
        }

        $this->info("面试提醒发送完成：成功 {$successCount} 条，失败 {$failCount} 条");

        return ['success' => $successCount, 'failed' => $failCount];
    }
}
