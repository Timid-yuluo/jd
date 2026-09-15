<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 添加文档 2.4 节中缺少的数据库索引
 */
return new class extends Migration
{
    public function up(): void
    {
        // job_applications: deadline 按截止日期查询/排序
        Schema::table('job_applications', function (Blueprint $table) {
            $table->index('deadline', 'job_applications_deadline_index');
        });

        // job_applications: reminder_sent 定时任务筛选未提醒记录
        Schema::table('job_applications', function (Blueprint $table) {
            $table->index('reminder_sent', 'job_applications_reminder_sent_index');
        });

        // job_applications: status + created_at 分页查询
        Schema::table('job_applications', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'job_applications_status_created_at_index');
        });

        // users: is_suspended 管理员筛选封禁用户
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_suspended', 'users_is_suspended_index');
        });

        // users: deletion_scheduled_at 定时清理任务
        Schema::table('users', function (Blueprint $table) {
            $table->index('deletion_scheduled_at', 'users_deletion_scheduled_at_index');
        });

        // users: last_login_at 活跃用户统计
        Schema::table('users', function (Blueprint $table) {
            $table->index('last_login_at', 'users_last_login_at_index');
        });

        // system_setting_audit_logs: setting_key + created_at 审计日志查询
        Schema::table('system_setting_audit_logs', function (Blueprint $table) {
            $table->index(['setting_key', 'created_at'], 'sal_setting_key_created_at_index');
        });

        // feedbacks: status + created_at 反馈列表分页查询
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'feedbacks_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex('job_applications_deadline_index');
            $table->dropIndex('job_applications_reminder_sent_index');
            $table->dropIndex('job_applications_status_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_suspended_index');
            $table->dropIndex('users_deletion_scheduled_at_index');
            $table->dropIndex('users_last_login_at_index');
        });

        Schema::table('system_setting_audit_logs', function (Blueprint $table) {
            $table->dropIndex('sal_setting_key_created_at_index');
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropIndex('feedbacks_status_created_at_index');
        });
    }
};
