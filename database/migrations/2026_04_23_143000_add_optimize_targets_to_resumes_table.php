<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->string('target_company', 120)->nullable()->after('target_job')->comment('目标公司');
            $table->string('target_job_title', 120)->nullable()->after('target_company')->comment('目标岗位名称');
            $table->text('target_job_description')->nullable()->after('target_job_title')->comment('目标岗位描述/JD');
            $table->json('optimize_goals')->nullable()->after('target_job_description')->comment('优化目标多选');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn(['target_company', 'target_job_title', 'target_job_description', 'optimize_goals']);
        });
    }
};
