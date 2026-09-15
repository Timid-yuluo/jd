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
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->comment('模板标识');
            $table->string('title', 100)->comment('模板名称');
            $table->text('description')->nullable()->comment('模板描述');
            $table->text('system_prompt')->comment('系统 Prompt');
            $table->json('variables')->nullable()->comment('变量定义');
            $table->string('model', 50)->nullable()->comment('指定模型');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->unsignedInteger('version')->default(1)->comment('版本号');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('最后更新人');
            $table->timestamps();

            $table->index('key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }
};
