<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        // 套餐数据
        $plans = [
            [
                'slug' => 'free',
                'name' => '免费版',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'quotas' => json_encode([
                    'resumes' => 1,
                    'ats_score' => ['daily_per_resume' => 3],
                    'optimize_full' => ['monthly' => 3],
                    'optimize_section' => ['monthly' => 5],
                    'keywords_extract' => ['monthly' => 5],
                    'export_pdf' => ['monthly' => 3],
                    'import_document' => ['monthly' => 1],
                    'interview_sessions' => ['monthly' => 2, 'max_questions' => 5],
                    'interview_evaluation' => ['monthly' => 2],
                    'custom_questions' => false,
                    'job_positions_practice' => 0,
                    'job_match' => ['monthly' => 5],
                    'match_analysis' => ['monthly' => 5],
                    'resume_job_compare' => ['monthly' => 3],
                    'job_applications' => 5,
                ]),
                'features' => json_encode(null),
                'sort_order' => 0,
                'is_active' => true,
            ],
            [
                'slug' => 'basic',
                'name' => '基础版',
                'price_monthly' => 2900,
                'price_yearly' => 26800,
                'quotas' => json_encode([
                    'resumes' => 3,
                    'ats_score' => ['daily_per_resume' => 3],
                    'optimize_full' => ['monthly' => 20],
                    'optimize_section' => ['monthly' => 30],
                    'keywords_extract' => ['monthly' => -1],
                    'export_pdf' => ['monthly' => -1],
                    'import_document' => ['monthly' => 10],
                    'interview_sessions' => ['monthly' => 15, 'max_questions' => 10],
                    'interview_evaluation' => ['monthly' => 15],
                    'custom_questions' => true,
                    'job_positions_practice' => 3,
                    'job_match' => ['monthly' => 30],
                    'match_analysis' => ['monthly' => 30],
                    'resume_job_compare' => ['monthly' => 20],
                    'job_applications' => 50,
                ]),
                'features' => json_encode([
                    'no_watermark' => true,
                    'deadline_reminder' => true,
                ]),
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'slug' => 'pro',
                'name' => '专业版',
                'price_monthly' => 7900,
                'price_yearly' => 68800,
                'quotas' => json_encode([
                    'resumes' => -1,
                    'ats_score' => ['daily_per_resume' => 3],
                    'optimize_full' => ['monthly' => -1],
                    'optimize_section' => ['monthly' => -1],
                    'keywords_extract' => ['monthly' => -1],
                    'export_pdf' => ['monthly' => -1],
                    'import_document' => ['monthly' => -1],
                    'interview_sessions' => ['monthly' => -1, 'max_questions' => 15],
                    'interview_evaluation' => ['monthly' => -1],
                    'custom_questions' => true,
                    'job_positions_practice' => -1,
                    'job_match' => ['monthly' => -1],
                    'match_analysis' => ['monthly' => -1],
                    'resume_job_compare' => ['monthly' => -1],
                    'job_applications' => -1,
                ]),
                'features' => json_encode([
                    'no_watermark' => true,
                    'priority_queue' => true,
                    'advanced_model' => true,
                    'exclusive_support' => true,
                    'deadline_reminder' => true,
                ]),
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                array_merge($plan, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 次卡商品数据
        $creditPacks = [
            ['slug' => 'resume_5', 'name' => '简历优化5次卡', 'quota_key' => 'optimize_full', 'credits' => 5, 'price' => 990, 'validity_days' => 365, 'sort_order' => 1],
            ['slug' => 'resume_20', 'name' => '简历优化20次卡', 'quota_key' => 'optimize_full', 'credits' => 20, 'price' => 2900, 'validity_days' => 365, 'sort_order' => 2],
            ['slug' => 'interview_5', 'name' => 'AI面试5次卡', 'quota_key' => 'interview_sessions', 'credits' => 5, 'price' => 990, 'validity_days' => 365, 'sort_order' => 3],
            ['slug' => 'interview_20', 'name' => 'AI面试20次卡', 'quota_key' => 'interview_sessions', 'credits' => 20, 'price' => 2900, 'validity_days' => 365, 'sort_order' => 4],
            ['slug' => 'job_match_10', 'name' => '岗位匹配10次卡', 'quota_key' => 'job_match', 'credits' => 10, 'price' => 990, 'validity_days' => 365, 'sort_order' => 5],
            ['slug' => 'universal_10', 'name' => '通用10次卡', 'quota_key' => null, 'credits' => 10, 'price' => 1900, 'validity_days' => 365, 'sort_order' => 6],
            ['slug' => 'universal_30', 'name' => '通用30次卡', 'quota_key' => null, 'credits' => 30, 'price' => 4900, 'validity_days' => 365, 'sort_order' => 7],
        ];

        foreach ($creditPacks as $pack) {
            DB::table('credit_packs')->updateOrInsert(
                ['slug' => $pack['slug']],
                array_merge($pack, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
