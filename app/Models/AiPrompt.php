<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPrompt extends Model
{
    protected $fillable = [
        'key',
        'title',
        'description',
        'system_prompt',
        'variables',
        'model',
        'is_active',
        'version',
        'updated_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * 最后更新人
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 通过 key 获取 Prompt
     */
    public static function getByKey(string $key): ?self
    {
        return self::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * 增加版本号并保存
     */
    public function incrementVersion(): void
    {
        $this->increment('version');
        $this->updated_by = auth()->id();
        $this->save();
    }
}
