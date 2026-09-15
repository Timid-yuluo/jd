<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HelpCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class, 'category_id');
    }

    public static function cachedVisible()
    {
        $dataArray = Cache::remember('help_categories:visible', 3600, fn () =>
            static::query()->where('is_visible', true)->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (self $cat) => $cat->getAttributes())->toArray()
        );

        if (! is_array($dataArray) || $dataArray === []) {
            return static::query()->where('is_visible', true)->orderBy('sort_order')->orderBy('id')->get();
        }

        return static::hydrate($dataArray);
    }

    public function publishedArticlesCount(): int
    {
        return $this->articles()->where('is_published', true)->count();
    }

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            if ($category->slug === null || $category->slug === '') {
                $category->slug = self::generateUniqueSlug($category->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'cat-'.substr(md5($name), 0, 8);
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
