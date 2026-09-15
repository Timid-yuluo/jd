<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 技能评估与学习路径相关表
 *
 * 关联文档：docs/features-development-plan.md §4.3
 */
return new class extends Migration
{
    public function up(): void
    {
        // 技能自评表
        Schema::create('skill_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill_name', 80);
            $table->string('skill_category', 40)->default('hard'); // hard / soft
            $table->unsignedTinyInteger('proficiency')->default(1); // 1-5
            $table->unsignedTinyInteger('years_used')->default(0);
            $table->date('last_used_at')->nullable();
            $table->text('evidence')->nullable(); // 证书/项目证明
            $table->timestamps();

            $table->index(['user_id', 'skill_category']);
            $table->unique(['user_id', 'skill_name']);
        });

        // 学习路径表
        Schema::create('skill_learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('target_job', 120);
            $table->json('current_snapshot')->nullable(); // 当前技能快照
            $table->json('required_skills')->nullable(); // AI 分析的目标岗位要求技能
            $table->json('gap_analysis')->nullable(); // 差距分析 [{skill, gap_level, importance}]
            $table->json('ai_path')->nullable(); // AI 学习路径 [{phase, topic, resources, duration}]
            $table->unsignedInteger('total_duration_weeks')->default(0);
            $table->string('status', 20)->default('active'); // active / archived
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_learning_paths');
        Schema::dropIfExists('skill_assessments');
    }
};
