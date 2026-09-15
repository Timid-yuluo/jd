<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->text('optimized_text')->nullable()->after('content_structured')->comment('AI 优化后的简历内容');
            $table->json('highlights')->nullable()->after('optimized_text')->comment('AI 提取的简历亮点');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn(['optimized_text', 'highlights']);
        });
    }
};
