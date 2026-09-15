<?php

declare(strict_types=1);

/**
 * #14 推荐列表页常用查询的复合索引
 *
 * 列表页 where(user_id).whereNotIn(status).orderByDesc(match_score)
 * 单独索引在数据量大时会回表，复合索引可覆盖排序
 *
 * 关联文档：docs/features-development-plan.md §5.5
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_recommendations', function (Blueprint $table): void {
            // 列表页核心查询：WHERE user_id=? AND status!=? ORDER BY match_score DESC
            $table->index(['user_id', 'status', 'match_score'], 'job_rec_user_status_score_index');
            // 高匹配筛选：WHERE user_id=? AND match_score>=? AND status!=?
            $table->index(['user_id', 'match_score', 'status'], 'job_rec_user_score_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('job_recommendations', function (Blueprint $table): void {
            $table->dropIndex('job_rec_user_status_score_index');
            $table->dropIndex('job_rec_user_score_status_index');
        });
    }
};
