<?php

declare(strict_types=1);

/**
 * 智能岗位推荐配置
 *
 * 关联文档：docs/features-development-plan.md §5
 * 关联类：App\Services\JobRecommendationService
 */
return [

    // ========== 匹配算法权重（#21） ==========
    // 五维评分权重，总和应为 100
    'weights' => [
        'skill' => 40,
        'experience' => 25,
        'education' => 15,
        'location' => 10,
        'salary' => 10,
    ],

    // 规则匹配阈值：>= 此分数时直接采用规则结果，不调用 AI
    'rule_match_threshold' => 70,

    // 高匹配阈值：超过此分数推送通知（#HIGH_MATCH_THRESHOLD）
    'high_match_threshold' => 80,

    // 单次推荐分析的最大岗位数
    'batch_size' => 50,

    // AI 分析缓存有效期（秒）
    'cache_ttl' => 86400,

    // AI 并发请求数（#9）
    'ai_concurrency' => 5,

    // 每用户每日最大生成次数（#19 配额提示）
    'daily_quota' => 3,

    // AI 请求超时（秒）
    'ai_timeout' => 30,

    // 简历内容截取长度（用于 AI prompt）
    'resume_max_length' => 3000,

    // 匹配结果字段限制
    'max_match_reasons' => 5,
    'max_skill_gaps' => 5,
];
