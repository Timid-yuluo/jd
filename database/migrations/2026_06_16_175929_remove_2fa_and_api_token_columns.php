<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 删除 api_tokens 表
        Schema::dropIfExists('api_tokens');

        // sqlite 兼容性：先删除可能存在的 unique 索引
        // sqlite 在 dropColumn 重建表时，不会自动清理引用该列的索引，导致失败
        $this->dropIndexIfExists('users_api_token_unique');
        $this->dropIndexIfExists('users_two_factor_secret_unique');

        // 删除 users 表中的 2FA 和 API Token 相关列
        Schema::table('users', function (Blueprint $table): void {
            $columns = [
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'api_token',
                'api_token_expires_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * 安全删除索引（兼容 sqlite 和 MySQL）
     */
    private function dropIndexIfExists(string $indexName): void
    {
        try {
            \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS \"{$indexName}\"");
        } catch (\Throwable) {
            // MySQL 可能语法不同，尝试备用方式
            try {
                \Illuminate\Support\Facades\Schema::connection('users')->getConnection()
                    ->getSchemaBuilder()->dropIfExists($indexName);
            } catch (\Throwable) {
                // 忽略所有错误
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('api_token', 80)->nullable()->unique();
            $table->timestamp('api_token_expires_at')->nullable();
        });

        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }
};
