<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class ExternalRecruitment extends Model
{
    public const REVIEW_PENDING = 'pending';

    public const REVIEW_APPROVED = 'approved';

    public const REVIEW_REJECTED = 'rejected';

    public const TYPE_CAMPUS = 'campus';

    public const TYPE_SOCIAL = 'social';

    private const FULLTEXT_MIN_LENGTH = 3;

    protected $fillable = [
        'source_id',
        'source_name',
        'source_url',
        'announcement_url',
        'company',
        'title',
        'work_location',
        'industry',
        'positions',
        'channel',
        'batch',
        'is_hot',
        'apply_url',
        'position_tags',
        'source_tags',
        'work_locations',
        'deadline',
        'apply_deadline_text',
        'fingerprint',
        'remarks',
        'source_created_at',
        'source_updated_at',
        'raw_payload',
        'imported_at',
        'review_status',
        'recruitment_type',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'position_tags' => 'array',
            'source_tags' => 'array',
            'work_locations' => 'array',
            'is_hot' => 'boolean',
            'deadline' => 'datetime',
            'source_created_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'raw_payload' => 'array',
            'imported_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * 模型启动时注册缓存清除钩子
     */
    protected static function booted(): void
    {
        static::saved(function (): void {
            static::clearKanbanCache();
        });

        static::deleted(function (): void {
            static::clearKanbanCache();
        });
    }

    /**
     * 清除 Kanban 聚合缓存
     */
    public static function clearKanbanCache(): void
    {
        Cache::forget('kanban:stats');
        Cache::forget('kanban:hot_industries');
        Cache::forget('kanban:hot_locations');
        Cache::forget('kanban:type_tabs');
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return $query;
        }

        if (mb_strlen($keyword) >= self::FULLTEXT_MIN_LENGTH) {
            return $query->whereRaw(
                'MATCH(company, title, positions) AGAINST(? IN BOOLEAN MODE)',
                [$keyword.'*']
            );
        }

        return $query->where(function (Builder $builder) use ($keyword): void {
            $safeKeyword = escapeLike($keyword);
            $builder->where('company', 'like', '%'.$safeKeyword.'%')
                ->orWhere('title', 'like', '%'.$safeKeyword.'%')
                ->orWhere('positions', 'like', '%'.$safeKeyword.'%');
        });
    }

    /**
     * 仅查询审核通过的岗位
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('review_status', self::REVIEW_APPROVED);
    }
}
