<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPack extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'quota_key',
        'credits',
        'price',
        'validity_days',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'price' => 'integer',
            'validity_days' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isUniversal(): bool
    {
        return $this->quota_key === null;
    }

    /**
     * 获取所有活跃次卡商品
     */
    public static function getActivePacks()
    {
        try {
            $dataArray = \Illuminate\Support\Facades\Cache::remember('credit_packs:active', 3600, fn () =>
                static::where('is_active', true)->orderBy('sort_order')->get()
                    ->map(fn (self $pack) => $pack->getAttributes())->toArray()
            );

            if (! is_array($dataArray) || $dataArray === []) {
                return static::where('is_active', true)->orderBy('sort_order')->get();
            }

            return static::hydrate($dataArray);
        } catch (\Throwable) {
            \Illuminate\Support\Facades\Cache::forget('credit_packs:active');

            return static::where('is_active', true)->orderBy('sort_order')->get();
        }
    }
}
