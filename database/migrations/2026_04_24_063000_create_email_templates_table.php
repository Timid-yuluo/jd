<?php

declare(strict_types=1);

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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // 模板名称
            $table->string('slug')->unique();                // 唯一标识
            $table->string('subject');                       // 邮件主题
            $table->longText('content');                     // 邮件内容（HTML）
            $table->text('description')->nullable();         // 模板描述
            $table->json('variables')->nullable();           // 可用变量列表
            $table->boolean('is_active')->default(true);     // 是否启用
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
