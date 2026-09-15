<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 清理 resumes 表重复索引
        // 初始迁移已创建 resumes_user_created_index (user_id, created_at)
        // 性能索引迁移又添加了同名索引（仅名称不同）
        if (Schema::hasTable('resumes')) {
            $indexes = collect(DB::select("SHOW INDEXES FROM " . DB::getTablePrefix() . "resumes"))
                ->pluck('Key_name')
                ->unique()
                ->toArray();

            // 删除重复的 resumes_user_created_index（如果存在两个同名不同 key_name 的索引）
            $userCreatedIndexes = array_filter($indexes, fn ($name) =>
                str_contains($name, 'resumes_user_created') || $name === 'resumes_user_created_index'
            );

            // 保留第一个，删除其余的
            if (count($userCreatedIndexes) > 1) {
                $toDelete = array_slice($userCreatedIndexes, 1);
                foreach ($toDelete as $indexName) {
                    try {
                        DB::statement("ALTER TABLE " . DB::getTablePrefix() . "resumes DROP INDEX `{$indexName}`");
                    } catch (\Throwable) {
                        // 索引可能不存在，忽略
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // 不可逆：删除的重复索引不需要恢复
    }
};
