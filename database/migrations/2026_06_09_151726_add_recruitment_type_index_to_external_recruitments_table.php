<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_recruitments', function (Blueprint $table): void {
            // Kanban 页面按 recruitment_type + review_status 筛选
            if (! Schema::hasIndex('external_recruitments', 'ext_rec_type_review_idx')) {
                $table->index(['recruitment_type', 'review_status'], 'ext_rec_type_review_idx');
            }

            // 按 review_status + imported_at 排序查询
            if (! Schema::hasIndex('external_recruitments', 'ext_rec_review_imported_idx')) {
                $table->index(['review_status', 'imported_at'], 'ext_rec_review_imported_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_recruitments', function (Blueprint $table): void {
            if (Schema::hasIndex('external_recruitments', 'ext_rec_type_review_idx')) {
                $table->dropIndex('ext_rec_type_review_idx');
            }
            if (Schema::hasIndex('external_recruitments', 'ext_rec_review_imported_idx')) {
                $table->dropIndex('ext_rec_review_imported_idx');
            }
        });
    }
};
