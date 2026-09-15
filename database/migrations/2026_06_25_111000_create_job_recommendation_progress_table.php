<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #3 推荐进度持久化表
 *
 * 当任务排队或 Job 重启时，原 Cache 进度会丢失
 * 持久化到 DB 后，用户可在 Job 重启后仍看到准确进度
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('job_recommendation_progress')) {
            Schema::create('job_recommendation_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('status', 20)->default('idle')->comment('queued|processing|completed|failed|idle');
                $table->unsignedInteger('processed')->default(0);
                $table->unsignedInteger('total')->default(0);
                $table->string('error', 500)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_recommendation_progress');
    }
};
