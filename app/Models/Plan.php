<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Plan extends Model
{
    private const ACTIVE_PLANS_CACHE_KEY = 'plans:active';

    protected $fillable = [
        'slug',
        'name',
        'price_monthly',
        'price_yearly',
        'quotas',
        'features',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'quotas' => 'array',
            'features' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 根据 slug 获取套餐（带缓存）
     */
    public static function findBySlug(string $slug): ?self
    {
        try {
            $data = Cache::remember(static::slugCacheKey($slug), 3600, fn () =>
                static::where('slug', $slug)->first()?->getAttributes()
            );

            if (! is_array($data) || $data === []) {
                return null;
            }

            return static::hydrate([$data])[0] ?? null;
        } catch (\Throwable) {
            Cache::forget(static::slugCacheKey($slug));

            return static::where('slug', $slug)->first();
        }
    }

    public static function getActivePlans(): Collection
    {
        try {
            $dataArray = Cache::remember(static::ACTIVE_PLANS_CACHE_KEY, 3600, fn () =>
                static::where('is_active', true)->orderBy('sort_order')->get()
                    ->map(fn (self $plan) => $plan->getAttributes())->toArray()
            );

            if (! is_array($dataArray) || $dataArray === []) {
                return static::where('is_active', true)->orderBy('sort_order')->get();
            }

            return static::hydrate($dataArray);
        } catch (\Throwable) {
            Cache::forget(static::ACTIVE_PLANS_CACHE_KEY);

            return static::where('is_active', true)->orderBy('sort_order')->get();
        }
    }

    /**
     * 获取指定 quota_key 的月配额限制，-1 或 null 表示不限
     */
    public function getMonthlyLimit(string $quotaKey): ?int
    {
        return $this->resolveQuotaLimit($quotaKey, 'monthly');
    }

    /**
     * 获取指定 quota_key 的总量限制（非月配额）
     */
    public function getTotalLimit(string $quotaKey): ?int
    {
        return $this->resolveQuotaLimit($quotaKey, 'total');
    }

    /**
     * 获取指定 quota_key 的月配额（兼容数组和标量）
     */
    public function getQuotaLimit(string $quotaKey): int
    {
        return $this->resolveQuotaLimit($quotaKey, 'any') ?? 0;
    }

    /**
     * 是否有某项权益
     */
    public function hasFeature(string $feature): bool
    {
        return (bool) ($this->features[$feature] ?? false);
    }

    /**
     * 清除套餐缓存
     */
    public static function clearCache(): void
    {
        Cache::forget(static::ACTIVE_PLANS_CACHE_KEY);
        foreach (static::pluck('slug') as $slug) {
            Cache::forget(static::slugCacheKey((string) $slug));
        }
    }

    private static function slugCacheKey(string $slug): string
    {
        return "plan:{$slug}";
    }

    private function resolveQuotaLimit(string $quotaKey, string $mode): ?int
    {
        $quota = $this->quotas[$quotaKey] ?? null;

        if ($quota === null) {
            return null;
        }

        if (is_array($quota)) {
            return match ($mode) {
                'total' => isset($quota['total']) ? (int) $quota['total'] : null,
                'monthly', 'any' => isset($quota['monthly']) ? (int) $quota['monthly'] : null,
                default => null,
            };
        }

        return $mode === 'monthly' ? null : (int) $quota;
    }
}
