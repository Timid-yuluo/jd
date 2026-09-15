<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_name',
        'task_description',
        'status',
        'output',
        'duration_ms',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 触发用户
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * 获取格式化后的持续时间
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_ms === null) {
            return '-';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms.'ms';
        }

        return round($this->duration_ms / 1000, 2).'s';
    }

    /**
     * 获取状态标签
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => '<span class="badge bg-success">成功</span>',
            'failed' => '<span class="badge bg-danger">失败</span>',
            'running' => '<span class="badge bg-warning">运行中</span>',
            default => '<span class="badge bg-secondary">未知</span>',
        };
    }
}
