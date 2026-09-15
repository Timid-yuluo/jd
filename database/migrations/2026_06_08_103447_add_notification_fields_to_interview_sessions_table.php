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
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('interview_sessions', 'reminder_sent')) {
                $table->boolean('reminder_sent')->default(false)->after('status')
                    ->comment('是否已发送面试提醒');
            }
            if (! Schema::hasColumn('interview_sessions', 'started_notif_sent')) {
                $table->boolean('started_notif_sent')->default(false)->after('reminder_sent')
                    ->comment('是否已发送开始通知');
            }
            if (! Schema::hasColumn('interview_sessions', 'completed_notif_sent')) {
                $table->boolean('completed_notif_sent')->default(false)->after('started_notif_sent')
                    ->comment('是否已发送完成通知');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent', 'started_notif_sent', 'completed_notif_sent']);
        });
    }
};
