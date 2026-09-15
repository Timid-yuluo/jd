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
            $table->string('job_tags', 500)->nullable()->after('position')->comment('职位标签，逗号分隔');
            $table->string('experience_required', 60)->nullable()->after('job_tags')->comment('经验要求');
            $table->string('education_required', 60)->nullable()->after('experience_required')->comment('学历要求');
            $table->string('industry', 120)->nullable()->after('company_size')->comment('所属行业');
            $table->date('applied_at')->nullable()->after('deadline')->comment('实际投递日期');
            $table->date('interview_at')->nullable()->after('applied_at')->comment('面试日期');
            $table->text('interview_info')->nullable()->after('interview_at')->comment('面试信息（轮次、面试官等）');
            $table->integer('resume_id')->nullable()->after('user_id')->comment('关联的简历ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['job_tags', 'experience_required', 'education_required', 'industry', 'applied_at', 'interview_at', 'interview_info', 'resume_id']);
        });
    }
};
