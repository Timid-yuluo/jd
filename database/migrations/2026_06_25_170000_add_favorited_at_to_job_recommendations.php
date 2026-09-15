<?php

declare(strict_types=1);

/**
 * #19 推荐收藏功能：独立 favorited_at 字段，不与 status 互斥
 *
 * 设计原因：用户可能想"收藏已投递的岗位"或"收藏新岗位"，独立字段比新增 STATUS_FAVORITED 更灵活
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
            $table->timestamp('favorited_at')->nullable()->after('viewed_at')->comment('收藏时间，NULL 表示未收藏');
            $table->index(['user_id', 'favorited_at'], 'job_rec_user_favorited_index');
        });
    }

    public function down(): void
    {
        Schema::table('job_recommendations', function (Blueprint $table): void {
            $table->dropIndex('job_rec_user_favorited_index');
            $table->dropColumn('favorited_at');
        });
    }
};
