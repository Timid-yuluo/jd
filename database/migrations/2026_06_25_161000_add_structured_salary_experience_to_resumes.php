<?php

declare(strict_types=1);

/**
 * #15 Resume 增加结构化薪资期望字段
 *
 * 之前 extractSalaryFromResume 依赖正则匹配自由文本，命中率低
 * 增加结构化字段后，规则匹配与 AI prompt 都可直接读取
 *
 * 关联文档：docs/features-development-plan.md §5.5
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table): void {
            // #15 期望薪资范围（单位：元/月，0 表示不限）
            $table->unsignedInteger('expected_salary_min')->default(0)->after('target_job_description')->comment('期望最低月薪');
            $table->unsignedInteger('expected_salary_max')->default(0)->after('expected_salary_min')->comment('期望最高月薪');
            // #15 期望城市
            $table->string('preferred_city', 60)->default('')->after('expected_salary_max')->comment('期望工作城市');
            // #16 工作经验年限（年），从工作经历累加或用户自填
            $table->decimal('experience_years', 4, 1)->default(0)->after('preferred_city')->comment('工作年限');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table): void {
            $table->dropColumn(['expected_salary_min', 'expected_salary_max', 'preferred_city', 'experience_years']);
        });
    }
};
