<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 公司情报模块整体下线：删除所有相关表
 *
 * 涉及表：
 * - company_blacklist_appeals  (#49 黑名单申诉)
 * - company_profile_change_logs (#47 公司信息审核日志)
 * - company_review_status_logs  (#48 点评审核通知)
 * - company_blacklists          (黑名单预警)
 * - company_reviews             (公司点评)
 * - company_profiles            (企业画像)
 *
 * 注：原表分别由以下迁移创建（已删除）：
 * - 2026_06_24_200000_create_new_features_tables.php        (company_profiles)
 * - 2026_06_24_210000_create_company_intelligence_tables.php (company_reviews, company_blacklists)
 * - 2026_06_25_110000_add_recommendation_and_intelligence_extras.php (3 张日志/申诉表)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 按外键依赖顺序删除
        Schema::dropIfExists('company_blacklist_appeals');
        Schema::dropIfExists('company_profile_change_logs');
        Schema::dropIfExists('company_review_status_logs');
        Schema::dropIfExists('company_blacklists');
        Schema::dropIfExists('company_reviews');
        Schema::dropIfExists('company_profiles');
    }

    public function down(): void
    {
        // 公司情报模块已下线，不提供回滚重建
        // 如需恢复，请从版本控制历史中找回对应迁移文件
    }
};
