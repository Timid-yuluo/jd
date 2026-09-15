<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========== #1 薪资查询与谈判助手 ==========
        Schema::create('salary_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title', 120)->index();
            $table->string('company', 120)->nullable();
            $table->string('city', 60)->nullable()->index();
            $table->string('industry', 60)->nullable()->index();
            $table->unsignedInteger('salary_min')->default(0);
            $table->unsignedInteger('salary_max')->default(0);
            $table->string('currency', 10)->default('CNY');
            $table->string('experience_level', 30)->nullable();
            $table->string('source', 30)->default('user_report');
            $table->string('source_hash', 64)->nullable();
            $table->date('reported_at')->nullable();
            $table->timestamps();

            $table->index(['job_title', 'city', 'experience_level']);
            $table->index(['job_title', 'industry']);
        });

        Schema::create('salary_negotiation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('job_title', 120);
            $table->string('company', 120)->nullable();
            $table->unsignedInteger('current_salary')->default(0);
            $table->unsignedInteger('target_salary')->default(0);
            $table->string('city', 60)->nullable();
            $table->string('experience_years', 10)->nullable();
            $table->json('context')->nullable();
            $table->json('ai_strategy')->nullable();
            $table->json('ai_dialogue')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        // ========== #3 AI 职业规划助手 ==========
        Schema::create('career_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_role', 120)->nullable();
            $table->string('target_role', 120)->nullable();
            $table->string('timeline', 20)->default('3y');
            $table->json('ai_path')->nullable();
            $table->json('skill_gaps')->nullable();
            $table->json('salary_forecast')->nullable();
            $table->json('milestones')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        // ========== #5 简历智能诊断报告 ==========
        Schema::create('resume_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->integer('overall_score')->default(0);
            $table->json('dimensions')->nullable();
            $table->json('issues')->nullable();
            $table->json('fix_priority')->nullable();
            $table->json('benchmark')->nullable();
            $table->timestamps();
            $table->index(['resume_id', 'created_at']);
        });

        // ========== #7 智能投递助手 ==========
        Schema::create('job_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('external_recruitment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title', 120)->nullable();
            $table->string('company', 120)->nullable();
            $table->string('city', 60)->nullable();
            $table->integer('match_score')->default(0);
            $table->json('match_reasons')->nullable();
            $table->json('skill_gaps')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'match_score']);
        });

        // ========== #11 AI 模拟群面 ==========
        Schema::create('group_interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('topic', 500);
            $table->string('scenario_type', 60)->default('leaderless_discussion');
            $table->integer('participant_count')->default(4);
            $table->string('user_role', 60)->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('participants')->nullable();
            $table->json('transcript')->nullable();
            $table->json('evaluation')->nullable();
            $table->integer('overall_score')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // ========== #12 职业性格测评 ==========
        Schema::create('career_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('test_type', 30)->default('mbti');
            $table->json('answers')->nullable();
            $table->string('result_code', 20)->nullable();
            $table->string('result_label', 60)->nullable();
            $table->json('dimensions')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->json('recommended_careers')->nullable();
            $table->json('team_roles')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'test_type']);
        });

        // ========== #13 简历真实性增强审计 ==========
        Schema::create('resume_authenticity_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->integer('authenticity_score')->default(100);
            $table->json('risk_flags')->nullable();
            $table->json('timeline_conflicts')->nullable();
            $table->json('exaggeration_warnings')->nullable();
            $table->json('pressure_test_questions')->nullable();
            $table->json('ai_advice')->nullable();
            $table->timestamps();
            $table->index(['resume_id', 'created_at']);
        });

        // 注：原 #14 企业画像库（company_profiles）已整体下线，
        //     由 2026_06_26_120000_drop_company_intelligence_tables.php 处理删除。
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_authenticity_audits');
        Schema::dropIfExists('career_assessments');
        Schema::dropIfExists('group_interview_sessions');
        Schema::dropIfExists('job_recommendations');
        Schema::dropIfExists('resume_diagnostics');
        Schema::dropIfExists('career_plans');
        Schema::dropIfExists('salary_negotiation_sessions');
        Schema::dropIfExists('salary_surveys');
    }
};
