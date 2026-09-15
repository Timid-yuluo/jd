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
            if (! Schema::hasColumn('external_recruitments', 'source_name')) {
                $table->string('source_name', 64)->default('offerstar')->after('source_id')->comment('来源名称');
            }
            if (! Schema::hasColumn('external_recruitments', 'source_url')) {
                $table->string('source_url', 1000)->nullable()->after('source_name')->comment('来源页面URL');
            }
            if (! Schema::hasColumn('external_recruitments', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('source_updated_at')->comment('原始载荷');
            }
            if (! Schema::hasColumn('external_recruitments', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->after('raw_payload')->comment('最近导入时间');
            }
            if (! Schema::hasColumn('external_recruitments', 'review_status')) {
                $table->string('review_status', 20)->default('pending')->after('imported_at')->comment('审核状态');
            }
            if (! Schema::hasColumn('external_recruitments', 'review_note')) {
                $table->text('review_note')->nullable()->after('review_status')->comment('审核备注');
            }
            if (! Schema::hasColumn('external_recruitments', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_note')->comment('审核人');
            }
            if (! Schema::hasColumn('external_recruitments', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by')->comment('审核时间');
            }

            if (! Schema::hasIndex('external_recruitments', 'ext_rec_source_review_idx')) {
                $table->index(['source_name', 'review_status'], 'ext_rec_source_review_idx');
            }
            if (! Schema::hasIndex('external_recruitments', 'external_recruitments_imported_at_index')) {
                $table->index('imported_at');
            }
            if (! Schema::hasIndex('external_recruitments', 'external_recruitments_reviewed_by_index')) {
                $table->index('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_recruitments', function (Blueprint $table): void {
            if (Schema::hasIndex('external_recruitments', 'ext_rec_source_review_idx')) {
                $table->dropIndex('ext_rec_source_review_idx');
            }
            if (Schema::hasIndex('external_recruitments', 'external_recruitments_imported_at_index')) {
                $table->dropIndex(['imported_at']);
            }
            if (Schema::hasIndex('external_recruitments', 'external_recruitments_reviewed_by_index')) {
                $table->dropIndex(['reviewed_by']);
            }

            $dropColumns = [];
            foreach ([
                'source_name',
                'source_url',
                'raw_payload',
                'imported_at',
                'review_status',
                'review_note',
                'reviewed_by',
                'reviewed_at',
            ] as $column) {
                if (Schema::hasColumn('external_recruitments', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
