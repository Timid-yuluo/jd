<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_match_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_match_analysis_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('status', 32)->nullable();
            $table->string('driver', 32)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('failure_type', 64)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('quota_source', 32)->nullable();
            $table->unsignedBigInteger('credit_id')->nullable();
            $table->string('request_id', 100)->nullable();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'status', 'created_at']);
            $table->index(['failure_type', 'created_at']);
            $table->index(['job_match_analysis_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_match_audit_logs');
    }
};
