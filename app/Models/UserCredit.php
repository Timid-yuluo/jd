<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class UserCredit extends Model
{
    private const QUOTA_LABELS = [
        'optimize_full' => 'AI 岗位定向优化',
        'optimize_section' => '分段优化',
        'ats_score' => 'ATS 评分',
        'keywords_extract' => '关键词提取',
        'import_document' => 'AI 导入简历',
        'interview_sessions' => 'AI 面试',
        'interview_evaluation' => '面试评估',
        'job_match' => '岗位匹配',
        'match_analysis' => '匹配分析',
        'resume_job_compare' => '简历-岗位对比',
        'export_pdf' => '导出 PDF',
        // 新增功能模块
        'salary_negotiation' => 'AI 薪资谈判',
        'career_assessment' => 'AI 职业测评',
        'skill_learning_path' => 'AI 学习路径',
        'job_recommendation' => '智能岗位推荐',
        'company_review' => '公司点评',
    ];

    protected $fillable = [
        'user_id',
        'quota_key',
        'remaining',
        'source_type',
        'source_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'remaining' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPack(): BelongsTo
    {
        return $this->belongsTo(CreditPack::class, 'source_id');
    }

    public function creditPackOrder(): BelongsTo
    {
        return $this->belongsTo(CreditPackOrder::class, 'source_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUniversal(): bool
    {
        return $this->quota_key === null;
    }

    public function packName(): ?string
    {
        if (! $this->source_id) {
            return null;
        }

        if ($this->source_type === 'pack') {
            return $this->creditPack?->name;
        }

        if ($this->source_type === 'pack_order') {
            return $this->creditPackOrder?->creditPack?->name;
        }

        return null;
    }

    public static function quotaLabel(?string $quotaKey): string
    {
        if ($quotaKey === null) {
            return '通用次卡';
        }

        return self::QUOTA_LABELS[$quotaKey] ?? $quotaKey;
    }

    public static function usageDescription(?string $quotaKey): string
    {
        if ($quotaKey === null) {
            return '可用于所有 AI 功能，包括 AI 岗位定向优化、ATS 评分、关键词提取、AI 面试场次、面试评估额度、岗位匹配等。';
        }

        return '仅可用于「'.self::quotaLabel($quotaKey).'」功能。';
    }

    /**
     * @return array<int,string>
     */
    public static function keyFeatureLabels(?string $quotaKey): array
    {
        if ($quotaKey === null) {
            return [
                self::quotaLabel('optimize_full'),
                self::quotaLabel('ats_score'),
                self::quotaLabel('keywords_extract'),
                self::quotaLabel('interview_sessions'),
                self::quotaLabel('interview_evaluation'),
                self::quotaLabel('job_match'),
            ];
        }

        return [self::quotaLabel($quotaKey)];
    }

    public function quotaLabelText(): string
    {
        return self::quotaLabel($this->quota_key);
    }

    public function usageDescriptionText(): string
    {
        return self::usageDescription($this->quota_key);
    }

    /**
     * @return array<string, string>
     */
    public static function quotaOptions(): array
    {
        return self::QUOTA_LABELS;
    }

    public function supportsQuota(string $quotaKey): bool
    {
        return $this->quota_key === null || $this->quota_key === $quotaKey;
    }

    /**
     * 获取用户对某 quota_key 可用的次卡余额（未过期，优先专用）
     */
    /**
     * 获取用户可用的次卡列表（带 Redis 缓存）
     *
     * 缓存策略：
     * - 读取时优先走 Redis，TTL 5 分钟
     * - 扣减时通过 forgetAvailableCreditsCache 失效
     * - 避免高并发下频繁查询 user_credits 表
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function getAvailableCredits(int $userId, string $quotaKey)
    {
        $cacheKey = "user_credits:available:{$userId}:{$quotaKey}";

        return Cache::remember($cacheKey, 300, function () use ($userId, $quotaKey) {
            // 专用次卡
            $dedicated = static::where('user_id', $userId)
                ->where('quota_key', $quotaKey)
                ->where('remaining', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderBy('expires_at') // 先到期的先用
                ->get();

            // 通用次卡
            $universal = static::where('user_id', $userId)
                ->whereNull('quota_key')
                ->where('remaining', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderBy('expires_at')
                ->get();

            return $dedicated->concat($universal);
        });
    }

    /**
     * 失效用户可用次卡缓存
     *
     * 在次卡扣减、购买、过期等场景调用，确保下次读取获取最新数据
     */
    public static function forgetAvailableCreditsCache(int $userId, string $quotaKey = ''): void
    {
        if ($quotaKey !== '') {
            Cache::forget("user_credits:available:{$userId}:{$quotaKey}");
        }

        // 同时清除通用次卡缓存（quotaKey 为空字符串时的缓存键）
        Cache::forget("user_credits:available:{$userId}:");

        // 清除所有可能的 quotaKey 缓存（通过标签或前缀扫描）
        // 为避免 KEYS 命令的性能问题，仅在关键场景调用此方法
    }
}
