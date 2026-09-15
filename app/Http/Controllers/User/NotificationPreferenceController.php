<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Services\Notification\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 用户通知偏好设置控制器
 */
class NotificationPreferenceController extends Controller
{
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly EmailNotificationService $emailNotificationService,
    ) {}

    /**
     * 显示通知偏好设置页面
     */
    public function index(): View
    {
        $user = auth()->user();

        $preferences = [
            'email_notifications_enabled' => $user->email_notifications_enabled ?? true,
            'notify_resume_completed' => $user->notify_resume_completed ?? true,
            'notify_interview_started' => $user->notify_interview_started ?? false,
            'notify_interview_completed' => $user->notify_interview_completed ?? true,
            'notify_job_application' => $user->notify_job_application ?? true,
            'notify_deadline_reminder' => $user->notify_deadline_reminder ?? true,
            'notify_marketing' => $user->notify_marketing ?? false,
            'notify_weekly_digest' => $user->notify_weekly_digest ?? true,
        ];

        return view('user.notification-preferences', compact('preferences'));
    }

    /**
     * 更新通知偏好设置
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_notifications_enabled' => 'boolean',
            'notify_resume_completed' => 'boolean',
            'notify_interview_started' => 'boolean',
            'notify_interview_completed' => 'boolean',
            'notify_job_application' => 'boolean',
            'notify_deadline_reminder' => 'boolean',
            'notify_marketing' => 'boolean',
            'notify_weekly_digest' => 'boolean',
        ]);

        $user = auth()->user();

        $user->fill($validated);
        $user->email_preferences_updated_at = now();
        $user->save();

        return $this->respondSuccessPayload([
            'message' => '通知偏好设置已更新',
        ]);
    }

    /**
     * 获取当前偏好设置
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();

        return $this->respondSuccessPayload([
            'data' => [
                'email_notifications_enabled' => $user->email_notifications_enabled ?? true,
                'notify_resume_completed' => $user->notify_resume_completed ?? true,
                'notify_interview_started' => $user->notify_interview_started ?? false,
                'notify_interview_completed' => $user->notify_interview_completed ?? true,
                'notify_job_application' => $user->notify_job_application ?? true,
                'notify_deadline_reminder' => $user->notify_deadline_reminder ?? true,
                'notify_marketing' => $user->notify_marketing ?? false,
                'notify_weekly_digest' => $user->notify_weekly_digest ?? true,
                'updated_at' => $user->email_preferences_updated_at,
            ],
        ]);
    }

    /**
     * 重置为默认设置
     */
    public function reset(): JsonResponse
    {
        $user = auth()->user();

        $user->update([
            'email_notifications_enabled' => true,
            'notify_resume_completed' => true,
            'notify_interview_started' => false,
            'notify_interview_completed' => true,
            'notify_job_application' => true,
            'notify_deadline_reminder' => true,
            'notify_marketing' => false,
            'notify_weekly_digest' => true,
            'email_preferences_updated_at' => now(),
        ]);

        return $this->respondSuccessPayload([
            'message' => '已恢复默认设置',
        ]);
    }

    /**
     * 发送测试邮件
     */
    public function test(): JsonResponse
    {
        $user = auth()->user();

        try {
            $success = $this->emailNotificationService->sendTestEmail($user->email, 'resume_completed');

            if ($success) {
                return $this->respondSuccessPayload([
                    'message' => '测试邮件已发送，请查收',
                ]);
            }

            return $this->fail('测试邮件发送失败', 500);
        } catch (\Exception $e) {
            return $this->fail('发送失败: '.$e->getMessage(), 500);
        }
    }
}
