<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #30 job_recommendation_runs 历史表
 *
 * 记录每次"生成推荐"的运行元信息：触发者、耗时、生成数、失败原因等
 * 用于审计、趋势分析与 #29 看板的数据源
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('job_recommendation_runs')) {
            Schema::create('job_recommendation_runs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status', 20)->default('running')->comment('running/completed/failed');
                $table->unsignedInteger('candidate_count')->default(0)->comment('候选岗位数');
                $table->unsignedInteger('created_count')->default(0)->comment('实际生成数');
                $table->unsignedInteger('high_match_count')->default(0)->comment('高匹配数');
                $table->unsignedInteger('duration_ms')->default(0)->comment('执行耗时（毫秒）');
                $table->string('trigger_source', 30)->default('manual')->comment('manual/schedule/api');
                $table->string('error_message', 500)->nullable()->comment('失败原因');
                $table->json('metadata')->nullable()->comment('附加信息：规则/AI 命中等');
                $table->timestamps();

                $table->index(['user_id', 'created_at'], 'job_rec_runs_user_created_idx');
                $table->index('status', 'job_rec_runs_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_recommendation_runs');
    }
};
