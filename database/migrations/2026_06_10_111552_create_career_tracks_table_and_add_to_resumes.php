<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 校招赛道表
        Schema::create('career_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('赛道名称');
            $table->string('slug', 50)->unique()->comment('赛道标识');
            $table->string('category', 30)->index()->comment('分类：tech/product_design/business/functional/industry/government');
            $table->string('icon', 50)->nullable()->comment('图标类名');
            $table->string('color', 20)->nullable()->comment('主题色');
            $table->text('description')->nullable()->comment('赛道描述');
            $table->json('optimization_focus')->nullable()->comment('优化侧重点说明');
            $table->json('recommended_templates')->nullable()->comment('推荐简历模板');
            $table->string('prompt_strategy_key', 50)->nullable()->comment('关联的Prompt策略key');
            $table->json('keywords')->nullable()->comment('赛道核心关键词');
            $table->json('avoid_words')->nullable()->comment('赛道避坑词汇');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();
        });

        // 为 resumes 表添加赛道关联
        Schema::table('resumes', function (Blueprint $table) {
            $table->foreignId('career_track_id')->nullable()->after('target_job_description')->constrained('career_tracks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropForeign(['career_track_id']);
            $table->dropColumn('career_track_id');
        });

        Schema::dropIfExists('career_tracks');
    }
};
