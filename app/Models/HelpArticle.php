<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HelpArticle extends Model
{
    public const STATUS_DRAFT = 0;

    public const STATUS_PUBLISHED = 1;

    protected $fillable = [
        'category_id', 'title', 'slug', 'content', 'excerpt',
        'sort_order', 'is_published', 'view_count', 'author_id', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'view_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $article): void {
            if ($article->is_published && $article->published_at === null) {
                $article->published_at = now();
            }
            if (($article->slug === null || $article->slug === '') && $article->title !== null) {
                $article->slug = self::generateUniqueSlug($article->title, $article->id);
            }
        });
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        if ($slug === '') {
            $slug = 'article-'.substr(md5($title), 0, 8);
        }
        $original = $slug;
        $count = 1;

        $query = self::where('slug', $slug);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $original.'-'.$count;
            $count++;
            $query = self::where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }
}
