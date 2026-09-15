<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 邮件通知偏好设置
            if (! Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')->default(true)->after('email_verified_at')
                    ->comment('是否启用邮件通知');
            }
            if (! Schema::hasColumn('users', 'notify_resume_completed')) {
                $table->boolean('notify_resume_completed')->default(true)->after('email_notifications_enabled')
                    ->comment('简历优化完成通知');
            }
            if (! Schema::hasColumn('users', 'notify_interview_started')) {
                $table->boolean('notify_interview_started')->default(false)->after('notify_resume_completed')
                    ->comment('面试开始通知');
            }
            if (! Schema::hasColumn('users', 'notify_interview_completed')) {
                $table->boolean('notify_interview_completed')->default(true)->after('notify_interview_started')
                    ->comment('面试完成通知');
            }
            if (! Schema::hasColumn('users', 'notify_job_application')) {
                $table->boolean('notify_job_application')->default(true)->after('notify_interview_completed')
                    ->comment('职位申请通知');
            }
            if (! Schema::hasColumn('users', 'notify_deadline_reminder')) {
                $table->boolean('notify_deadline_reminder')->default(true)->after('notify_job_application')
                    ->comment('截止提醒通知');
            }
            if (! Schema::hasColumn('users', 'notify_marketing')) {
                $table->boolean('notify_marketing')->default(false)->after('notify_deadline_reminder')
                    ->comment('营销邮件通知');
            }
            if (! Schema::hasColumn('users', 'email_preferences_updated_at')) {
                $table->timestamp('email_preferences_updated_at')->nullable()->after('notify_marketing')
                    ->comment('偏好设置更新时间');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_notifications_enabled',
                'notify_resume_completed',
                'notify_interview_started',
                'notify_interview_completed',
                'notify_job_application',
                'notify_deadline_reminder',
                'notify_marketing',
                'email_preferences_updated_at',
            ]);
        });
    }
};
