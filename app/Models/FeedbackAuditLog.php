<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FeedbackAuditLog extends Model
{
    public const ACTION_STATUS_UPDATED = 'status_updated';

    public const ACTION_REPLY_SENT = 'reply_sent';

    public const ACTION_NOTE_UPDATED = 'note_updated';

    public const ACTION_ADOPTION_UPDATED = 'adoption_updated';

    public const ACTION_REWARD_GRANTED = 'reward_granted';

    public const ACTION_FEEDBACK_DELETED = 'feedback_deleted';

    protected $fillable = [
        'feedback_id',
        'changed_by_user_id',
        'action',
        'old_values',
        'new_values',
        'context',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'context' => 'array',
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_STATUS_UPDATED => '更新状态/优先级',
            self::ACTION_REPLY_SENT => '发送回复',
            self::ACTION_NOTE_UPDATED => '更新内部备注',
            self::ACTION_ADOPTION_UPDATED => '更新采纳结果',
            self::ACTION_REWARD_GRANTED => '发放反馈奖励',
            self::ACTION_FEEDBACK_DELETED => '删除反馈',
            default => $this->action,
        };
    }
}
