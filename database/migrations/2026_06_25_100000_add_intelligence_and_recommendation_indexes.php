<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 补充索引与唯一约束：
 * - #7  job_recommendations (user_id, external_recruitment_id)
 *
 * 注：原迁移同时为 company_profiles.slug / company_reviews 添加索引，
 *     公司情报模块已整体下线，相关索引随表删除一并消失。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 推荐表复合索引（#7）
        if (Schema::hasTable('job_recommendations')) {
            $indexExists = DB::selectOne(
                "SELECT 1 FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                   AND table_name = 'job_recommendations'
                   AND index_name = 'job_recommendations_user_recruitment_index'"
            );
            if (!$indexExists) {
                Schema::table('job_recommendations', function (Blueprint $table) {
                    $table->index(['user_id', 'external_recruitment_id'], 'job_recommendations_user_recruitment_index');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_recommendations')) {
            Schema::table('job_recommendations', function (Blueprint $table) {
                $table->dropIndex('job_recommendations_user_recruitment_index');
            });
        }
    }
};
