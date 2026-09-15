<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 管理员通知/公告模型
 */
class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'channel',
        'email_template_id',
        'target_type',
        'target_roles',
        'target_users',
        'sender_id',
        'sent_at',
        'scheduled_at',
        'read_count',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'target_users' => 'array',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * 发送者
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * 获取类型标签
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'announcement' => '系统公告',
            'maintenance' => '维护通知',
            'feature' => '功能更新',
            'warning' => '重要提醒',
            default => '其他',
        };
    }

    /**
     * 获取类型颜色
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'announcement' => 'blue',
            'maintenance' => 'yellow',
            'feature' => 'green',
            'warning' => 'red',
            default => 'gray',
        };
    }

    /**
     * 是否已发送
     */
    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    /**
     * 获取目标类型标签
     */
    public function getTargetTypeLabelAttribute(): string
    {
        return match ($this->target_type) {
            'all' => '所有用户',
            'roles' => '指定角色',
            'users' => '指定用户',
            default => '未知',
        };
    }

    /**
     * 邮件模板
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * 邮件发送记录
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * 获取渠道标签
     */
    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            'site' => '仅站内',
            'email' => '仅邮件',
            'both' => '站内+邮件',
            default => '未知',
        };
    }

    /**
     * 是否是定时发送
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null && $this->scheduled_at > now();
    }

    /**
     * 作用域：待发送的定时通知
     */
    public function scopePendingScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at');
    }
}
