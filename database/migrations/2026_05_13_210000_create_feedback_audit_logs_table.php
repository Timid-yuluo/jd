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
        $driver = DB::getDriverName();

        if (! Schema::hasTable('feedback_audit_logs')) {
            Schema::create('feedback_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('feedback_id');
                $table->foreignId('changed_by_user_id')->nullable();
                $table->string('action', 64);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('context')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
                $table->index(['feedback_id', 'action']);
                $table->index(['changed_by_user_id', 'created_at']);
                $table->foreign('feedback_id')->references('id')->on('feedbacks')->cascadeOnDelete();
                $table->foreign('changed_by_user_id')->references('id')->on('users')->nullOnDelete();
            });

            return;
        }

        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('feedback_audit_logs', function (Blueprint $table): void {
            if (! $this->hasIndex('feedback_audit_logs', 'feedback_audit_logs_feedback_id_action_index')) {
                $table->index(['feedback_id', 'action']);
            }

            if (! $this->hasIndex('feedback_audit_logs', 'feedback_audit_logs_changed_by_user_id_created_at_index')) {
                $table->index(['changed_by_user_id', 'created_at']);
            }

            if (! $this->hasForeignKey('feedback_audit_logs', 'feedback_audit_logs_feedback_id_foreign')) {
                $table->foreign('feedback_id')->references('id')->on('feedbacks')->cascadeOnDelete();
            }

            if (! $this->hasForeignKey('feedback_audit_logs', 'feedback_audit_logs_changed_by_user_id_foreign')) {
                $table->foreign('changed_by_user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_audit_logs');
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
