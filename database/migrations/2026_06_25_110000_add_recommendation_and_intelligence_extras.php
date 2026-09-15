<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 补充字段：
 * - #28 job_recommendations.fingerprint 用于去重
 * - #15 job_recommendations.viewed_at 已查看时间戳
 * - #25 recommendation_interactions 表用于数据埋点
 *
 * 注：原迁移同时创建了 company_review_status_logs / company_profile_change_logs /
 *     company_blacklist_appeals 三张表，公司情报模块已整体下线，相关结构
 *     由 2026_06_26_120000_drop_company_intelligence_tables.php 处理删除。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 为 job_recommendations 补充字段
        if (Schema::hasTable('job_recommendations')) {
            Schema::table('job_recommendations', function (Blueprint $table) {
                if (!Schema::hasColumn('job_recommendations', 'fingerprint')) {
                    $table->string('fingerprint', 64)->nullable()->after('city');
                    $table->index('fingerprint');
                }
                if (!Schema::hasColumn('job_recommendations', 'viewed_at')) {
                    $table->timestamp('viewed_at')->nullable()->after('dismissed_at');
                }
            });
        }

        // #25 数据埋点表
        if (!Schema::hasTable('recommendation_interactions')) {
            Schema::create('recommendation_interactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('job_recommendation_id')->constrained()->cascadeOnDelete();
                $table->string('interaction_type', 30)->comment('view/applied/dismiss/like');
                $table->string('reason', 100)->nullable()->comment('忽略原因等');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'interaction_type', 'created_at'], 'rec_inter_user_type_created_idx');
                $table->index('job_recommendation_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_interactions');

        if (Schema::hasTable('job_recommendations')) {
            Schema::table('job_recommendations', function (Blueprint $table) {
                if (Schema::hasColumn('job_recommendations', 'viewed_at')) {
                    $table->dropColumn('viewed_at');
                }
                if (Schema::hasColumn('job_recommendations', 'fingerprint')) {
                    $table->dropIndex(['fingerprint']);
                    $table->dropColumn('fingerprint');
                }
            });
        }
    }
};
