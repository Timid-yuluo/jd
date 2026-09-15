<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Mail\InterviewSessionMail;
use App\Mail\JobApplicationMail;
use App\Mail\ResumeCompletedMail;
use App\Mail\ResumeExportMail;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\ResumeExportTask;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 邮件通知服务
 * 统一管理业务相关的邮件通知发送
 */
final class EmailNotificationService
{
    /**
     * 发送简历优化完成通知
     */
    public function sendResumeCompleted(User $user, Resume $resume, array $stats = []): bool
    {
        try {
            if (! $this->shouldSendNotificationType($user, 'resume_completed')) {
                return false;
            }

            // 去重检查：同用户 + 同简历，24小时内不重复发送
            if ($this->isRecentlySent($user->email, "resume_completed:{$resume->id}", 24)) {
                Log::info('简历优化完成邮件跳过（近期已发送）', [
                    'user_id' => $user->id,
                    'resume_id' => $resume->id,
                ]);

                return false;
            }

            Mail::to($user->email)->queue(
                new ResumeCompletedMail($user, $resume, $stats)
            );

            Log::info('简历优化完成邮件已加入队列', [
                'user_id' => $user->id,
                'resume_id' => $resume->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('发送简历优化完成邮件失败', [
                'user_id' => $user->id,
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 发送面试会话相关通知
     */
    public function sendInterviewSession(
        User $user,
        InterviewSession $session,
        string $type = InterviewSessionMail::TYPE_STARTED,
        ?array $reportData = null
    ): bool {
        try {
            // 根据类型检查用户偏好
            $preferenceType = match ($type) {
                InterviewSessionMail::TYPE_STARTED => 'interview_started',
                InterviewSessionMail::TYPE_COMPLETED => 'interview_completed',
                default => null,
            };

            if ($preferenceType && ! $this->shouldSendNotificationType($user, $preferenceType)) {
                return false;
            }

            // 去重检查：同用户 + 同会话 + 同类型，24小时内不重复发送
            if ($this->isRecentlySent($user->email, "interview_session:{$session->id}:{$type}", 24)) {
                Log::info('面试会话邮件跳过（近期已发送）', [
                    'user_id' => $user->id,
                    'session_id' => $session->id,
                    'type' => $type,
                ]);

                return false;
            }

            Mail::to($user->email)->queue(
                new InterviewSessionMail($user, $session, $type, $reportData)
            );

            Log::info('面试会话邮件已加入队列', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('发送面试会话邮件失败', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 发送职位申请相关通知
     */
    public function sendJobApplication(
        User $user,
        JobApplication $application,
        string $type = JobApplicationMail::TYPE_APPLIED,
        ?string $oldStatus = null,
        array $suggestions = []
    ): bool {
        try {
            // 检查通知类型偏好
            $preferenceType = match ($type) {
                JobApplicationMail::TYPE_DEADLINE_REMINDER => 'deadline_reminder',
                default => 'job_application',
            };

            if (! $this->shouldSendNotificationType($user, $preferenceType)) {
                return false;
            }

            // 去重检查：同用户 + 同申请 + 同类型，24小时内不重复发送
            if ($this->isRecentlySent($user->email, "job_application:{$application->id}:{$type}", 24)) {
                Log::info('职位申请邮件跳过（近期已发送）', [
                    'user_id' => $user->id,
                    'application_id' => $application->id,
                    'type' => $type,
                ]);

                return false;
            }

            Mail::to($user->email)->queue(
                new JobApplicationMail($user, $application, $type, $oldStatus, $suggestions)
            );

            Log::info('职位申请邮件已加入队列', [
                'user_id' => $user->id,
                'application_id' => $application->id,
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('发送职位申请邮件失败', [
                'user_id' => $user->id,
                'application_id' => $application->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 发送简历导出相关通知
     */
    public function sendResumeExport(
        User $user,
        ResumeExportTask $task,
        string $type = ResumeExportMail::TYPE_COMPLETED,
        ?string $errorMessage = null
    ): bool {
        try {
            if (! $this->shouldSendToUser($user)) {
                return false;
            }

            // 只有完成和失败状态才发送邮件
            if ($type === ResumeExportMail::TYPE_STARTED) {
                // 可选择是否发送开始通知
                if (! config('notification.resume_export_send_started', false)) {
                    return false;
                }
            }

            Mail::to($user->email)->queue(
                new ResumeExportMail($user, $task, $type, $errorMessage)
            );

            Log::info('简历导出邮件已加入队列', [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('发送简历导出邮件失败', [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 批量发送面试提醒
     */
    public function sendBatchInterviewReminders(array $sessions): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($sessions as $session) {
            $user = $session->user;

            if (! $user) {
                $results['skipped']++;

                continue;
            }

            if ($this->sendInterviewSession($user, $session, InterviewSessionMail::TYPE_REMINDER)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info('批量发送面试提醒完成', $results);

        return $results;
    }

    /**
     * 批量发送申请截止提醒
     */
    public function sendBatchDeadlineReminders(array $applications): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($applications as $application) {
            $user = $application->user;

            if (! $user) {
                $results['skipped']++;

                continue;
            }

            if ($this->sendJobApplication($user, $application, JobApplicationMail::TYPE_DEADLINE_REMINDER)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info('批量发送截止提醒完成', $results);

        return $results;
    }

    /**
     * 检查是否应该发送给用户
     */
    private function shouldSendToUser(User $user): bool
    {
        // 检查用户是否有邮箱
        if (empty($user->email)) {
            return false;
        }

        // 检查用户是否启用了邮件通知
        if (isset($user->email_notifications_enabled) && ! $user->email_notifications_enabled) {
            return false;
        }

        return true;
    }

    /**
     * 检查用户是否允许特定类型的通知
     */
    private function shouldSendNotificationType(User $user, string $type): bool
    {
        if (! $this->shouldSendToUser($user)) {
            return false;
        }

        // 检查特定类型的通知偏好
        $preferenceMap = [
            'resume_completed' => 'notify_resume_completed',
            'interview_started' => 'notify_interview_started',
            'interview_completed' => 'notify_interview_completed',
            'job_application' => 'notify_job_application',
            'deadline_reminder' => 'notify_deadline_reminder',
            'marketing' => 'notify_marketing',
        ];

        if (isset($preferenceMap[$type])) {
            $preferenceKey = $preferenceMap[$type];
            if (isset($user->$preferenceKey) && ! $user->$preferenceKey) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查是否应该发送面试通知
     */
    private function shouldSendInterviewNotification(User $user, string $type): bool
    {
        // 可以基于用户设置进行过滤
        $settings = [
            InterviewSessionMail::TYPE_STARTED => true,
            InterviewSessionMail::TYPE_COMPLETED => true,
            InterviewSessionMail::TYPE_REMINDER => true,
            InterviewSessionMail::TYPE_FEEDBACK => true,
        ];

        return $settings[$type] ?? true;
    }

    /**
     * 发送测试邮件
     */
    public function sendTestEmail(string $email, string $type = 'resume_completed'): bool
    {
        try {
            $user = User::where('email', $email)->first();

            if (! $user) {
                // 创建一个临时用户对象用于测试
                $user = new User([
                    'name' => '测试用户',
                    'email' => $email,
                ]);
            }

            switch ($type) {
                case 'resume_completed':
                    $resume = $user->resumes()->first() ?? new Resume([
                        'title' => '测试简历',
                        'target_job' => '高级软件工程师',
                        'target_company' => '测试公司',
                        'ats_score' => 85,
                    ]);
                    Mail::to($email)->send(new ResumeCompletedMail($user, $resume));
                    break;

                case 'interview_completed':
                    $session = $user->interviewSessions()->first() ?? new InterviewSession([
                        'company' => '测试公司',
                        'position' => '高级软件工程师',
                        'type' => 'technical',
                        'overall_score' => 88,
                    ]);
                    Mail::to($email)->send(new InterviewSessionMail($user, $session, InterviewSessionMail::TYPE_COMPLETED));
                    break;

                case 'job_applied':
                    $application = $user->jobApplications()->first() ?? new JobApplication([
                        'company' => '测试公司',
                        'position' => '高级软件工程师',
                        'status' => 'applied',
                    ]);
                    Mail::to($email)->send(new JobApplicationMail($user, $application, JobApplicationMail::TYPE_APPLIED));
                    break;

                default:
                    Mail::raw('这是一封测试邮件', function ($message) use ($email) {
                        $message->to($email)->subject('测试邮件 - '.config('app.name'));
                    });
            }

            return true;
        } catch (\Exception $e) {
            Log::error('发送测试邮件失败', [
                'email' => $email,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 检查指定邮箱+业务标识在指定小时内是否已发送过邮件
     * 基于 Cache 做去重，防止同一通知重复发送
     */
    private function isRecentlySent(string $email, string $businessKey, int $hours = 24): bool
    {
        $cacheKey = 'email_sent:'.md5($email.$businessKey);

        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return true;
        }

        // 发送成功后标记（由调用方在确认发送后调用）
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours($hours));

        return false;
    }
}
