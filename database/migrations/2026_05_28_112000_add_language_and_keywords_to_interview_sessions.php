<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->string('language', 10)->default('zh')->after('candidate_profile')->comment('面试语言：zh中文，en英文');
            $table->string('tech_keywords', 500)->nullable()->after('language')->comment('技术栈关键词，逗号分隔');
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['language', 'tech_keywords']);
        });
    }
};
