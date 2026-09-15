<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\JobApplicationMail;
use App\Models\JobApplication;
use App\Services\Notification\EmailNotificationService;

class JobApplicationObserver
{
    /**
     * Handle the JobApplication "created" event.
     */
    public function created(JobApplication $application): void
    {
        // 发送申请确认邮件
        $user = $application->user;

        if ($user && $user->email) {
            $emailService = app(EmailNotificationService::class);
            $emailService->sendJobApplication(
                $user,
                $application,
                JobApplicationMail::TYPE_APPLIED
            );
        }
    }

    /**
     * Handle the JobApplication "updated" event.
     */
    public function updated(JobApplication $application): void
    {
        // 状态变更时发送通知
        if ($application->isDirty('status')) {
            $oldStatus = $application->getOriginal('status');
            $newStatus = $application->status;

            // 只针对重要状态变更发送邮件
            $importantStatuses = ['interview', 'offer', 'rejected'];

            if (in_array($newStatus, $importantStatuses)) {
                $user = $application->user;

                if ($user && $user->email) {
                    $emailService = app(EmailNotificationService::class);
                    $emailService->sendJobApplication(
                        $user,
                        $application,
                        JobApplicationMail::TYPE_STATUS_UPDATE,
                        $oldStatus
                    );
                }
            }
        }
    }

    /**
     * Handle the JobApplication "deleted" event.
     */
    public function deleted(JobApplication $application): void
    {
        // 可选：发送申请撤回通知
    }
}
