<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * 用户通知模型
 */
class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_notification_id',
        'title',
        'content',
        'type',
        'channel',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * 用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联的管理员通知
     */
    public function adminNotification(): BelongsTo
    {
        return $this->belongsTo(AdminNotification::class);
    }

    /**
     * 是否已读
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * 标记为已读
     */
    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
            static::clearUnreadCache($this->user_id);
        }
    }

    /**
     * 标记为未读
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
        static::clearUnreadCache($this->user_id);
    }

    /**
     * 获取用户未读通知数量（带缓存，TTL 30s）
     */
    public static function getUnreadCount(int $userId): int
    {
        return (int) Cache::remember("notifications:unread_count:{$userId}", 30, fn () =>
            static::where('user_id', $userId)->unread()->count()
        );
    }

    /**
     * 清除用户未读通知计数缓存
     */
    public static function clearUnreadCache(int $userId): void
    {
        Cache::forget("notifications:unread_count:{$userId}");
        Cache::forget("notifications:recent:{$userId}");
    }

    /**
     * 作用域：未读通知
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * 作用域：已读通知
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
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
            'system' => '系统通知',
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
            'system' => 'purple',
            default => 'gray',
        };
    }

    /**
     * 从通知内容中提取首个反馈详情链接。
     */
    public function feedbackActionUrl(): ?string
    {
        $content = (string) $this->content;

        if ($content === '') {
            return null;
        }

        if (preg_match('/href="([^"]*\/feedback\/\d+[^"]*)"/i', $content, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        }

        return null;
    }

    /**
     * 是否包含反馈详情跳转入口。
     */
    public function hasFeedbackAction(): bool
    {
        return $this->feedbackActionUrl() !== null;
    }
}
