<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // 发送欢迎邮件
        $this->sendWelcomeEmail($user);
    }

    /**
     * 发送欢迎邮件
     */
    private function sendWelcomeEmail(User $user): void
    {
        if (app()->environment('testing')) {
            return;
        }

        try {
            // 检查是否启用邮件通知
            if (isset($user->email_notifications_enabled) && ! $user->email_notifications_enabled) {
                return;
            }

            Mail::to($user->email)->queue(new WelcomeMail($user));

            Log::info('欢迎邮件已加入队列', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('发送欢迎邮件失败', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
