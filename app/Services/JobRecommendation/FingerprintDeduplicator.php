<?php

declare(strict_types=1);

namespace App\Services\JobRecommendation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 指纹去重器（#9 从 JobRecommendationService 拆分）
 *
 * 基于 (company + title + city) 计算指纹，避免同一岗位多次推荐
 *
 * 关联文档：docs/features-development-plan.md §5.2.1
 */
final class FingerprintDeduplicator
{
    /**
     * 构建岗位指纹
     */
    public function build(string $company, string $title, string $city): string
    {
        return hash('sha256', Str::lower(trim($company).'|'.trim($title).'|'.trim($city)));
    }

    /**
     * 判断该指纹是否已被该用户推荐过
     */
    public function isDuplicate(int $userId, string $fingerprint): bool
    {
        return DB::table('job_recommendations')
            ->where('user_id', $userId)
            ->where('fingerprint', $fingerprint)
            ->exists();
    }
}
