<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

final class SendInterviewRemindersCommand extends Command
{
    /**
     * 提前多少小时发送提醒
     */
    private const REMIND_BEFORE_HOURS = 24;

    protected $signature = 'app:send-interview-reminders';

    protected $description = '发送面试提醒通知给即将面试的用户';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $remindBefore = now()->addHours(self::REMIND_BEFORE_HOURS);

        // 查找即将面试且未发送提醒的申请
        $applications = JobApplication::query()
            ->where('status', JobApplication::STATUS_INTERVIEW)
            ->whereNotNull('interview_at')
            ->where('interview_at', '<=', $remindBefore)
            ->where('interview_at', '>', now())
            ->where('reminder_sent', false)
            ->with('user')
            ->get();

        $sentCount = 0;

        foreach ($applications as $application) {
            $user = $application->user;
            if (! $user instanceof User) {
                continue;
            }

            try {
                // 通过系统通知发送提醒
                $user->notify(new \App\Notifications\InterviewReminderNotification($application));

                $application->update([
                    'reminder_sent' => true,
                    'reminder_sent_at' => now(),
                ]);

                $sentCount++;
            } catch (\Throwable $e) {
                $this->warn("Failed to send reminder for application #{$application->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sentCount} interview reminders.");

        return self::SUCCESS;
    }
}
