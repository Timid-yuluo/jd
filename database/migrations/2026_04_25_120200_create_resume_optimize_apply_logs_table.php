<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_optimize_apply_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('resume_optimize_sessions')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->json('selections')->nullable();
            $table->json('applied_result')->nullable();
            $table->string('undo_token', 64)->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_optimize_apply_logs');
    }
};
