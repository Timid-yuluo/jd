<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resume_versions', function (Blueprint $table) {
            $table->string('snapshot_hash', 64)->nullable()->after('modules_snapshot')->index();
            $table->unsignedSmallInteger('module_count')->nullable()->after('snapshot_hash');
            $table->string('source', 30)->nullable()->after('module_count')->comment('快照来源：manual/auto_save/restore/optimize');
        });

        // 为 resume_id + snapshot_hash 添加联合索引，用于去重
        Schema::table('resume_versions', function (Blueprint $table) {
            $table->index(['resume_id', 'snapshot_hash'], 'rv_resume_hash_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resume_versions', function (Blueprint $table) {
            $table->dropIndex('rv_resume_hash_idx');
            $table->dropColumn(['snapshot_hash', 'module_count', 'source']);
        });
    }
};
