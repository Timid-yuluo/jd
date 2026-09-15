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
        // 职位申请表添加提醒标记字段
        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                if (! Schema::hasColumn('job_applications', 'reminder_sent')) {
                    $table->boolean('reminder_sent')->default(false)->after('status')
                        ->comment('是否已发送截止提醒');
                }
                if (! Schema::hasColumn('job_applications', 'reminder_sent_at')) {
                    $table->timestamp('reminder_sent_at')->nullable()->after('reminder_sent')
                        ->comment('提醒发送时间');
                }
            });
        }

        // 面试会话表添加提醒标记字段
        if (Schema::hasTable('interview_sessions')) {
            Schema::table('interview_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('interview_sessions', 'reminder_sent')) {
                    $table->boolean('reminder_sent')->default(false)->after('status')
                        ->comment('是否已发送提醒');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->dropColumn(['reminder_sent', 'reminder_sent_at']);
            });
        }

        if (Schema::hasTable('interview_sessions')) {
            Schema::table('interview_sessions', function (Blueprint $table) {
                $table->dropColumn(['reminder_sent']);
            });
        }
    }
};
