<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at', 'created_at'], 'idx_user_notifications_unread');
        });
        Schema::table('job_match_analyses', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_job_match_analyses_user');
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_user_notifications_unread');
        });
        Schema::table('job_match_analyses', function (Blueprint $table) {
            $table->dropIndex('idx_job_match_analyses_user');
        });
    }
};
