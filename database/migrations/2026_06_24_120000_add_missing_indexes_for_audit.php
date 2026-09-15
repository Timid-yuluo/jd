<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 数据库索引审计补充迁移
 *
 * 补充以下缺失索引：
 * 1. feedback_replies.feedback_id — Feedback::replies() 关联查询使用
 * 2. user_credits.user_id_remaining — 按用户查询可用次卡（remaining > 0）使用
 */
return new class extends Migration
{
    public function up(): void
    {
        // feedback_replies.feedback_id 索引
        // 用于 Feedback::replies() HasMany 关联查询，避免全表扫描
        $this->createIndexIfNotExists('feedback_replies', 'feedback_id', 'feedback_replies_feedback_id_index');

        // user_credits 复合索引 (user_id, remaining)
        // 用于 CreditService::getUserCreditsSummary() 和 QuotaService 中的可用次卡查询
        // 已有 idx_user_quota (user_id, quota_key)，但 remaining > 0 条件无法利用该索引
        $this->createCompositeIndexIfNotExists('user_credits', ['user_id', 'remaining'], 'user_credits_user_remaining_index');
    }

    public function down(): void
    {
        Schema::table('feedback_replies', function (Blueprint $table): void {
            $this->dropIndexIfExists('feedback_replies', 'feedback_replies_feedback_id_index');
        });

        Schema::table('user_credits', function (Blueprint $table): void {
            $this->dropIndexIfExists('user_credits', 'user_credits_user_remaining_index');
        });
    }

    /**
     * 安全创建单列索引（兼容 SQLite 和 MySQL）
     */
    private function createIndexIfNotExists(string $table, string $column, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName): void {
            $blueprint->index($column, $indexName);
        });
    }

    /**
     * 安全创建复合索引（兼容 SQLite 和 MySQL）
     *
     * @param array<int,string> $columns
     */
    private function createCompositeIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    /**
     * 检查索引是否存在（兼容 SQLite 和 MySQL）
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            return collect($indexes)->pluck('name')->contains($indexName);
        }

        // MySQL
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * 安全删除索引
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("DROP INDEX IF EXISTS \"{$indexName}\"");
        } else {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};

