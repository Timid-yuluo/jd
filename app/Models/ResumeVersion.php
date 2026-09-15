<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResumeVersion extends Model
{
    protected $fillable = [
        'resume_id',
        'user_id',
        'content_raw',
        'content_structured',
        'title',
        'target_job',
        'modules_snapshot',
        'snapshot_hash',
        'module_count',
        'source',
        'change_summary',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'content_structured' => 'array',
            'modules_snapshot' => 'array',
            'module_count' => 'integer',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 根据模块快照计算哈希值，用于去重判断
     */
    public static function computeSnapshotHash(Resume $resume): string
    {
        $data = [
            'title' => $resume->title,
            'target_job' => $resume->target_job,
            'modules' => $resume->buildModulesSnapshot(),
        ];

        return hash('xxh128', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 判断该版本是否与指定简历当前内容相同
     */
    public function isSameAsCurrent(Resume $resume): bool
    {
        return $this->snapshot_hash === static::computeSnapshotHash($resume);
    }
}
