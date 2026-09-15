<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->index('share_token');
        });
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'idx_notifications_unread');
        });
        Schema::table('help_articles', function (Blueprint $table) {
            $table->index(['category_id', 'is_published', 'sort_order'], 'idx_help_articles_list');
        });
        Schema::table('interview_questions', function (Blueprint $table) {
            $table->index(['interview_session_id', 'round_no'], 'idx_questions_session_round');
        });
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->index(['user_id', 'scenario', 'created_at'], 'idx_usage_logs_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropIndex('share_token');
        });
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_unread');
        });
        Schema::table('help_articles', function (Blueprint $table) {
            $table->dropIndex('idx_help_articles_list');
        });
        Schema::table('interview_questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_session_round');
        });
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropIndex('idx_usage_logs_lookup');
        });
    }
};
