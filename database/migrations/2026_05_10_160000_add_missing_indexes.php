<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_logs', function (Blueprint $table): void {
            if (! Schema::hasIndex('usage_logs', 'usage_logs_scenario_index')) {
                $table->index('scenario');
            }
            if (! Schema::hasIndex('usage_logs', 'usage_logs_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('interview_sessions', function (Blueprint $table): void {
            if (! Schema::hasIndex('interview_sessions', 'interview_sessions_created_at_index')) {
                $table->index('created_at');
            }
            if (! Schema::hasIndex('interview_sessions', 'interview_sessions_qr_token_qr_expires_at_index')) {
                $table->index(['qr_token', 'qr_expires_at']);
            }
        });

        Schema::table('resumes', function (Blueprint $table): void {
            if (! Schema::hasIndex('resumes', 'resumes_ats_score_index')) {
                $table->index('ats_score');
            }
        });

        Schema::table('email_logs', function (Blueprint $table): void {
            if (! Schema::hasIndex('email_logs', 'email_logs_status_index')) {
                $table->index('status');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasIndex('users', 'users_is_admin_index')) {
                $table->index('is_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usage_logs', function (Blueprint $table): void {
            $table->dropIndex(['scenario']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['qr_token', 'qr_expires_at']);
        });

        Schema::table('resumes', function (Blueprint $table): void {
            $table->dropIndex(['ats_score']);
        });

        Schema::table('email_logs', function (Blueprint $table): void {
            $table->dropIndex(['status']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_admin']);
        });
    }
};
