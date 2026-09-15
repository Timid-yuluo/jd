<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite' && ! Schema::hasIndex('external_recruitments', 'external_recruitments_fulltext_search')) {
            DB::statement('ALTER TABLE external_recruitments ADD FULLTEXT INDEX external_recruitments_fulltext_search (company, title, positions)');
        }

        if (! Schema::hasIndex('external_recruitments', 'external_recruitments_review_imported_index')) {
            Schema::table('external_recruitments', function (Blueprint $table): void {
                $table->index(['review_status', 'imported_at'], 'external_recruitments_review_imported_index');
            });
        }

        if (! Schema::hasIndex('external_recruitments', 'external_recruitments_industry_index')) {
            Schema::table('external_recruitments', function (Blueprint $table): void {
                $table->index('industry');
            });
        }

        if (! Schema::hasIndex('external_recruitments', 'external_recruitments_work_location_index')) {
            Schema::table('external_recruitments', function (Blueprint $table): void {
                $table->index('work_location');
            });
        }

        if (! Schema::hasIndex('resumes', 'resumes_user_created_index')) {
            Schema::table('resumes', function (Blueprint $table): void {
                $table->index(['user_id', 'created_at'], 'resumes_user_created_index');
            });
        }

        if (! Schema::hasIndex('job_applications', 'job_applications_user_status_index')) {
            Schema::table('job_applications', function (Blueprint $table): void {
                $table->index(['user_id', 'status'], 'job_applications_user_status_index');
            });
        }

        if (! Schema::hasIndex('job_applications', 'job_applications_user_created_index')) {
            Schema::table('job_applications', function (Blueprint $table): void {
                $table->index(['user_id', 'created_at'], 'job_applications_user_created_index');
            });
        }

        if (! Schema::hasIndex('interview_sessions', 'interview_sessions_user_created_index')) {
            Schema::table('interview_sessions', function (Blueprint $table): void {
                $table->index(['user_id', 'created_at'], 'interview_sessions_user_created_index');
            });
        }

        if (! Schema::hasIndex('interview_questions', 'interview_questions_session_created_index')) {
            Schema::table('interview_questions', function (Blueprint $table): void {
                $table->index(['interview_session_id', 'created_at'], 'interview_questions_session_created_index');
            });
        }

        if (! Schema::hasIndex('usage_logs', 'usage_logs_user_created_index')) {
            Schema::table('usage_logs', function (Blueprint $table): void {
                $table->index(['user_id', 'created_at'], 'usage_logs_user_created_index');
            });
        }

        if (! Schema::hasIndex('email_logs', 'email_logs_created_at_index')) {
            Schema::table('email_logs', function (Blueprint $table): void {
                $table->index('created_at');
            });
        }

        if (! Schema::hasIndex('orders', 'orders_user_status_index')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->index(['user_id', 'status'], 'orders_user_status_index');
            });
        }

        if (! Schema::hasIndex('credit_pack_orders', 'credit_pack_orders_user_status_index')) {
            Schema::table('credit_pack_orders', function (Blueprint $table): void {
                $table->index(['user_id', 'status'], 'credit_pack_orders_user_status_index');
            });
        }

        if (! Schema::hasIndex('resume_export_tasks', 'resume_export_tasks_user_created_index')) {
            Schema::table('resume_export_tasks', function (Blueprint $table): void {
                $table->index(['user_id', 'created_at'], 'resume_export_tasks_user_created_index');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE external_recruitments DROP INDEX IF EXISTS external_recruitments_fulltext_search');
        }

        $indexes = [
            ['external_recruitments', 'external_recruitments_review_imported_index'],
            ['external_recruitments', 'external_recruitments_industry_index'],
            ['external_recruitments', 'external_recruitments_work_location_index'],
            ['resumes', 'resumes_user_created_index'],
            ['job_applications', 'job_applications_user_status_index'],
            ['job_applications', 'job_applications_user_created_index'],
            ['interview_sessions', 'interview_sessions_user_created_index'],
            ['interview_questions', 'interview_questions_session_created_index'],
            ['usage_logs', 'usage_logs_user_created_index'],
            ['email_logs', 'email_logs_created_at_index'],
            ['orders', 'orders_user_status_index'],
            ['credit_pack_orders', 'credit_pack_orders_user_status_index'],
            ['resume_export_tasks', 'resume_export_tasks_user_created_index'],
        ];

        foreach ($indexes as [$table, $index]) {
            if (Schema::hasIndex($table, $index)) {
                Schema::table($table, function (Blueprint $table) use ($index): void {
                    $table->dropIndex($index);
                });
            }
        }
    }
};
