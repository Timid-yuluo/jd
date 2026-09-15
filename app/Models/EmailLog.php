<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 邮件发送记录模型
 */
class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_notification_id',
        'user_id',
        'recipient_email',
        'subject',
        'status',
        'error_message',
        'sent_at',
        'content',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * 关联的管理员通知
     */
    public function adminNotification(): BelongsTo
    {
        return $this->belongsTo(AdminNotification::class);
    }

    /**
     * 关联的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 作用域：成功的邮件
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * 作用域：失败的邮件
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * 标记为已发送
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * 标记为失败
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
