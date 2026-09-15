<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_export_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('task_id', 64)->unique();
            $table->string('type', 16);
            $table->string('status', 20)->default('processing');
            $table->json('resume_ids');
            $table->unsignedSmallInteger('resume_count')->default(0);
            $table->json('position_keywords')->nullable();
            $table->boolean('include_optimized')->default(true);
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['user_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_export_tasks');
    }
};
