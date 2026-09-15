<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_optimize_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('queued');
            $table->string('idempotency_key', 64)->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->json('config')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->unique(['resume_id', 'idempotency_key'], 'resume_optimize_sessions_resume_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_optimize_sessions');
    }
};
