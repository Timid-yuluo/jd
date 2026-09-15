<?php

declare(strict_types=1);

namespace App\Services\JobRecommendation\Matchers;

use App\Models\ExternalRecruitment;
use App\Models\Resume;

/**
 * 规则匹配器（#9 从 JobRecommendationService 拆分）
 *
 * 基于「技能/经验/学历/地域/薪资」五维加权快速打分
 *
 * 关联文档：docs/features-development-plan.md §5.2.1
 */
final class RuleMatcher
{
    /**
     * 计算规则匹配分（0-100）
     */
    public function match(Resume $resume, ExternalRecruitment $recruitment): int
    {
        $weights = (array) config('job-matching.weights', []);

        $skillScore = $this->scoreSkill($resume, $recruitment);
        $expScore = $this->scoreExperience($resume, $recruitment);
        $eduScore = $this->scoreEducation($resume, $recruitment);
        $locScore = $this->scoreLocation($resume, $recruitment);
        $salaryScore = $this->scoreSalary($resume, $recruitment);

        $total = $skillScore * ((float) ($weights['skill'] ?? 40)) / 100
            + $expScore * ((float) ($weights['experience'] ?? 25)) / 100
            + $eduScore * ((float) ($weights['education'] ?? 15)) / 100
            + $locScore * ((float) ($weights['location'] ?? 10)) / 100
            + $salaryScore * ((float) ($weights['salary'] ?? 10)) / 100;

        return (int) round(min(100, max(0, $total)));
    }

    /**
     * 构建规则匹配的详情结果
     *
     * @return array{match_score: int, match_reasons: array<int, string>, skill_gaps: array<int, string>}
     */
    public function buildResult(Resume $resume, ExternalRecruitment $recruitment, int $score): array
    {
        // 委派回原 Service 的私有逻辑（通过 buildRuleBasedResult 反射）
        // 这里独立实现，避免循环依赖
        $reasons = [];
        $gaps = [];

        if ($score >= 70) {
            $reasons[] = '技能/经验整体匹配度较高';
        }

        // 简单的技能缺口判断
        $resumeSkills = $this->extractSkills($resume);
        $jobSkills = $this->extractJobSkills($recruitment);
        $missing = array_diff($jobSkills, $resumeSkills);
        foreach (array_slice($missing, 0, 3) as $skill) {
            $gaps[] = "建议补充「{$skill}」相关经验";
        }

        return [
            'match_score' => $score,
            'match_reasons' => $reasons,
            'skill_gaps' => $gaps,
        ];
    }

    private function scoreSkill(Resume $resume, ExternalRecruitment $recruitment): int
    {
        $resumeSkills = $this->extractSkills($resume);
        $jobSkills = $this->extractJobSkills($recruitment);

        if ($jobSkills === []) {
            return 50; // 岗位未声明技能要求时给中位数
        }

        $intersect = array_intersect($resumeSkills, $jobSkills);
        $ratio = count($intersect) / count($jobSkills);

        return (int) round($ratio * 100);
    }

    private function scoreExperience(Resume $resume, ExternalRecruitment $recruitment): int
    {
        $resumeYears = (int) ($resume->experience_years ?? 0);
        $requiredYears = (int) ($recruitment->min_experience_years ?? 0);

        if ($requiredYears === 0) {
            return 80;
        }

        return $resumeYears >= $requiredYears ? 100 : (int) min(60, $resumeYears * 60 / max(1, $requiredYears));
    }

    private function scoreEducation(Resume $resume, ExternalRecruitment $recruitment): int
    {
        $levels = ['大专' => 1, '本科' => 2, '硕士' => 3, '博士' => 4];
        $resumeLevel = $levels[$resume->education_level ?? ''] ?? 0;
        $requiredLevel = $levels[$recruitment->min_education ?? ''] ?? 0;

        return $resumeLevel >= $requiredLevel ? 100 : (int) ($resumeLevel / max(1, $requiredLevel) * 100);
    }

    private function scoreLocation(Resume $resume, ExternalRecruitment $recruitment): int
    {
        $resumeCity = (string) ($resume->preferred_city ?? '');
        $jobCities = (array) ($recruitment->work_locations ?? []);

        if ($resumeCity === '' || $jobCities === []) {
            return 50;
        }

        return in_array($resumeCity, $jobCities, true) ? 100 : 30;
    }

    private function scoreSalary(Resume $resume, ExternalRecruitment $recruitment): int
    {
        $expected = (int) ($resume->expected_salary_min ?? 0);
        $jobMin = (int) ($recruitment->salary_min ?? 0);
        $jobMax = (int) ($recruitment->salary_max ?? 0);

        if ($expected === 0 || $jobMax === 0) {
            return 50;
        }

        return $expected <= $jobMax && $expected >= $jobMin ? 100 : 30;
    }

    private function extractSkills(Resume $resume): array
    {
        $raw = (string) ($resume->skills ?? '');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_map('strtolower', array_map('strval', $decoded));
        }

        return array_map('strtolower', array_filter(array_map('trim', explode(',', $raw))));
    }

    private function extractJobSkills(ExternalRecruitment $recruitment): array
    {
        $tags = (array) ($recruitment->tags ?? []);
        $required = (array) ($recruitment->required_skills ?? []);

        return array_values(array_unique(array_map('strtolower', array_merge($tags, $required))));
    }
}
