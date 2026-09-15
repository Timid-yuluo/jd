<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\WeeklyDigestNotification;
use Illuminate\Console\Command;

final class SendWeeklyDigestCommand extends Command
{
    protected $signature = 'app:send-weekly-digest';

    protected $description = '发送每周求职进度邮件摘要';

    public function handle(): int
    {
        $weekStart = now()->startOfWeek()->subWeek()->format('m/d');
        $weekEnd = now()->startOfWeek()->subDay()->format('m/d');
        $weekRange = "{$weekStart} - {$weekEnd}";

        $lastWeek = now()->subWeek();

        // 查找过去7天有活跃行为且开启了周报的用户
        $activeUserIds = User::query()
            ->where('notify_weekly_digest', true)
            ->where(function ($q) use ($lastWeek) {
                $q->whereHas('resumes', fn ($q2) => $q2->where('created_at', '>=', $lastWeek))
                    ->orWhereHas('interviewSessions', fn ($q2) => $q2->where('created_at', '>=', $lastWeek))
                    ->orWhereHas('jobApplications', fn ($q2) => $q2->where('created_at', '>=', $lastWeek));
            })
            ->pluck('id')
            ->unique();

        $sentCount = 0;

        foreach ($activeUserIds as $userId) {
            $user = User::find($userId);
            if (! $user instanceof User) {
                continue;
            }

            try {
                $stats = [
                    'resume_count' => $user->resumes()->count(),
                    'interview_count' => $user->interviewSessions()->where('created_at', '>=', $lastWeek)->count(),
                    'application_count' => $user->jobApplications()->where('created_at', '>=', $lastWeek)->count(),
                    'upcoming_interviews' => JobApplication::query()
                        ->where('user_id', $user->id)
                        ->where('status', JobApplication::STATUS_INTERVIEW)
                        ->whereNotNull('interview_at')
                        ->where('interview_at', '>', now())
                        ->count(),
                    'match_count' => $user->jobMatchAnalyses()->where('created_at', '>=', $lastWeek)->count(),
                ];

                $user->notify(new WeeklyDigestNotification($stats, $weekRange));
                $sentCount++;
            } catch (\Throwable $e) {
                $this->warn("Failed to send digest to user #{$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sentCount} weekly digests.");

        return self::SUCCESS;
    }
}
