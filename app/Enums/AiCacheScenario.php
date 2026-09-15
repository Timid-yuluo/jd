<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * AI 缓存场景枚举，用于差异化 TTL 配置。
 *
 * - Heavy:  简历优化/ATS 评分等高成本且稳定的场景
 * - Medium: 模块排序/翻译等中等成本场景
 * - Quick:  面试出题/评分等时效性较高的场景
 * - Stable: 关键词提取/解析等输入确定即输出确定的场景
 */
enum AiCacheScenario: string
{
    case Heavy = 'heavy';
    case Medium = 'medium';
    case Quick = 'quick';
    case Stable = 'stable';
}
