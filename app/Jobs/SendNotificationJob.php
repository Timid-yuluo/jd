<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Admin\MailRuntimeConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\LazyCollection;

/**
 * 发送通知队列作业
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;  // 5分钟超时

    public $tries = 3;      // 最多尝试3次

    private int $sentCount = 0;

    private int $emailCount = 0;

    private int $failCount = 0;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AdminNotification $notification
    ) {
        // 通知发送（含邮件）独立队列，避免与业务队列互相阻塞
        $this->onQueue('notification');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->notification->isSent()) {
            Log::info("Notification {$this->notification->id} already sent, skipping.");

            return;
        }

        // 应用邮件配置（从数据库读取）
        $this->applyMailConfig();

        // 获取目标用户
        $users = $this->getTargetUsers();

        foreach ($users as $user) {
            try {
                // 站内通知
                if (in_array($this->notification->channel, ['site', 'both'])) {
                    $this->createSiteNotification($user);
                    $this->sentCount++;
                }

                // 邮件通知
                if (in_array($this->notification->channel, ['email', 'both'])) {
                    $this->sendEmailNotification($user);
                    $this->emailCount++;
                }
            } catch (\Exception $e) {
                $this->failCount++;
                Log::error("Failed to send notification to user {$user->id}", [
                    'notification_id' => $this->notification->id,
                    'error' => $e->getMessage(),
                ]);
                // 不重新抛出异常，继续处理下一个用户
            }
        }

        // 更新通知状态
        $this->notification->update([
            'sent_at' => now(),
            'read_count' => 0,
        ]);

        Log::info("Notification {$this->notification->id} processing completed", [
            'site_count' => $this->sentCount,
            'email_count' => $this->emailCount,
            'fail_count' => $this->failCount,
        ]);
    }

    /**
     * Job 最终失败时的兜底处理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
        ]);

        // 标记通知发送失败，避免调度命令重复拾取
        if ($this->notification && ! $this->notification->isSent()) {
            $this->notification->update([
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * 应用邮件配置
     */
    private function applyMailConfig(): void
    {
        app(MailRuntimeConfigService::class)->applyFromSystemSettings();
    }

    /**
     * 获取目标用户列表
     *
     * @return LazyCollection<int, User>
     */
    private function getTargetUsers(): LazyCollection
    {
        return match ($this->notification->target_type) {
            'all' => User::query()->cursor(),
            'roles' => $this->getUsersByRoles(),
            'users' => User::whereIn('id', $this->notification->target_users ?? [])->cursor(),
            default => User::query()->whereRaw('0 = 1')->cursor(),
        };
    }

    /**
     * 根据角色获取用户
     *
     * @return LazyCollection<int, User>
     */
    private function getUsersByRoles(): LazyCollection
    {
        $roles = $this->notification->target_roles ?? [];

        if (empty($roles)) {
            return User::query()->whereRaw('0 = 1')->cursor();
        }

        // 如果包含 admin 角色，也包含 super-admin
        if (in_array('admin', $roles) && ! in_array('super-admin', $roles)) {
            $roles[] = 'super-admin';
        }

        return User::role($roles)->cursor();
    }

    /**
     * 创建站内通知
     */
    private function createSiteNotification(User $user): void
    {
        UserNotification::create([
            'user_id' => $user->id,
            'admin_notification_id' => $this->notification->id,
            'title' => $this->notification->title,
            'content' => $this->notification->content,
            'type' => $this->notification->type,
            'channel' => $this->notification->channel,
            'read_at' => null,
        ]);

        UserNotification::clearUnreadCache($user->id);
    }

    /**
     * 发送邮件通知
     */
    private function sendEmailNotification(User $user): void
    {
        $template = $this->notification->emailTemplate;

        // 创建邮件日志记录
        $emailLog = EmailLog::create([
            'admin_notification_id' => $this->notification->id,
            'user_id' => $user->id,
            'recipient_email' => $user->email,
            'subject' => $this->notification->title,
            'status' => 'pending',
        ]);

        try {
            if (! $template) {
                // 使用默认邮件模板
                $content = strip_tags($this->notification->content);
                Mail::raw($content, function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject($this->notification->title);
                });

                $emailLog->update([
                    'content' => $content,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                return;
            }

            // 使用自定义模板
            $rendered = $template->render([
                'site_name' => config('app.name'),
                'site_url' => config('app.url'),
                'user_name' => $user->name,
                'user_email' => $user->email,
                'current_date' => now()->format('Y-m-d'),
                'current_time' => now()->format('H:i:s'),
                'notification_title' => $this->notification->title,
                'notification_content' => $this->notification->content,
            ]);

            Mail::html($rendered['content'], function ($message) use ($user, $rendered) {
                $message->to($user->email)
                    ->subject($rendered['subject']);
            });

            $emailLog->update([
                'subject' => $rendered['subject'],
                'content' => $rendered['content'],
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            $emailLog->markAsFailed($e->getMessage());
            // 不重新抛出异常，让外层循环继续处理其他用户
            Log::error("Email sending failed for user {$user->id}", [
                'notification_id' => $this->notification->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
