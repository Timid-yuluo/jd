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
        Schema::table('job_applications', function (Blueprint $table) {
            $table->text('job_url')->nullable()->after('note')->comment('职位链接');
            $table->longText('job_description')->nullable()->after('job_url')->comment('职位描述/JD');
            $table->integer('salary_min')->nullable()->after('job_description')->comment('最低薪资');
            $table->integer('salary_max')->nullable()->after('salary_min')->comment('最高薪资');
            $table->string('location', 120)->nullable()->after('salary_max')->comment('工作地点');
            $table->string('company_size', 60)->nullable()->after('location')->comment('公司规模');
            $table->json('ai_suggestions')->nullable()->after('company_size')->comment('AI优化建议');
            $table->timestamp('parsed_at')->nullable()->after('ai_suggestions')->comment('职位信息解析时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['job_url', 'job_description', 'salary_min', 'salary_max', 'location', 'company_size', 'ai_suggestions', 'parsed_at']);
        });
    }
};
