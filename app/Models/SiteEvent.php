<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SiteEvent extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'admin_id',
        'title',
        'slug',
        'content',
        'cover_image',
        'category',
        'summary',
        'is_pinned',
        'is_published',
        'published_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function incrementViewCountAtomic(): void
    {
        DB::statement('UPDATE site_events SET view_count = view_count + 1 WHERE id = ?', [$this->id]);
        $this->refresh();
    }

    /**
     * @return array<int, array{id:int, title:string, slug:string, published_at:string}>
     */
    public static function latestPublished(int $limit = 3): array
    {
        return Cache::remember('site_events:latest:'.$limit, 300, function () use ($limit) {
            return static::query()->published()
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get(['id', 'title', 'slug', 'summary', 'category', 'published_at'])
                ->toArray();
        });
    }

    public static function clearLatestCache(): void
    {
        Cache::forget('site_events:latest:3');
        Cache::forget('site_events:latest:5');
    }

    protected static function booted(): void
    {
        static::saved(function (SiteEvent $event): void {
            static::clearLatestCache();
        });

        static::deleted(function (SiteEvent $event): void {
            static::clearLatestCache();
        });

        static::creating(function (SiteEvent $event): void {
            if (($event->slug ?? '') === '') {
                $event->slug = static::generateUniqueSlug($event->title);
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title, '-', 'zh_CN');
        if ($base === '') {
            $base = 'event-'.now()->format('Ymd-His');
        }

        $slug = $base;
        $counter = 1;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function scopePinnedFirst(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public static function categories(): array
    {
        return ['公告', '活动', '更新', '洞察'];
    }

    public function getCategoryBadgeClass(): string
    {
        return match ($this->category) {
            '公告' => 'bg-blue-lt',
            '活动' => 'bg-green-lt',
            '洞察' => 'bg-purple-lt',
            default => 'bg-azure-lt',
        };
    }
}
