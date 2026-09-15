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
            if (! Schema::hasColumn('external_recruitments', 'announcement_url')) {
                $table->string('announcement_url', 1000)->nullable()->after('source_url')->comment('公告链接');
            }
            if (! Schema::hasColumn('external_recruitments', 'batch')) {
                $table->string('batch', 120)->nullable()->after('channel')->comment('招聘批次');
            }
            if (! Schema::hasColumn('external_recruitments', 'is_hot')) {
                $table->boolean('is_hot')->default(false)->after('batch')->comment('是否热门');
            }
            if (! Schema::hasColumn('external_recruitments', 'source_tags')) {
                $table->json('source_tags')->nullable()->after('position_tags')->comment('来源标签');
            }
            if (! Schema::hasColumn('external_recruitments', 'recruitment_type')) {
                $table->string('recruitment_type', 20)->nullable()->after('review_status')->comment('招聘类型：campus/social');
            }
            if (! Schema::hasColumn('external_recruitments', 'apply_deadline_text')) {
                $table->string('apply_deadline_text', 255)->nullable()->after('deadline')->comment('投递截止原文');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_recruitments', function (Blueprint $table): void {
            $table->dropColumn([
                'announcement_url',
                'batch',
                'is_hot',
                'source_tags',
                'recruitment_type',
                'apply_deadline_text',
            ]);
        });
    }
};
