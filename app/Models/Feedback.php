<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Feedback extends Model
{
    use SoftDeletes;

    protected $table = 'feedbacks';

    // 状态常量
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_REPLIED = 'replied';

    public const STATUS_CLOSED = 'closed';

    public const ADOPTION_PENDING = 'pending';

    public const ADOPTION_ADOPTED = 'adopted';

    public const ADOPTION_REJECTED = 'rejected';

    // 优先级常量
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    // 分类常量
    public const CATEGORY_BUG = 'bug';

    public const CATEGORY_SUGGESTION = 'suggestion';

    public const CATEGORY_UX = 'ux';

    public const CATEGORY_OTHER = 'other';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_REPLIED,
        self::STATUS_CLOSED,
    ];

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    public const CATEGORIES = [
        self::CATEGORY_BUG,
        self::CATEGORY_SUGGESTION,
        self::CATEGORY_UX,
        self::CATEGORY_OTHER,
    ];

    public const ADOPTION_STATUSES = [
        self::ADOPTION_PENDING,
        self::ADOPTION_ADOPTED,
        self::ADOPTION_REJECTED,
    ];

    public static array $statusColors = [
        'pending' => 'warning',
        'processing' => 'info',
        'replied' => 'success',
        'closed' => 'secondary',
    ];

    public static array $priorityColors = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
    ];

    public static array $adoptionColors = [
        'pending' => 'secondary',
        'adopted' => 'success',
        'rejected' => 'danger',
    ];

    protected $fillable = [
        'user_id',
        'visitor_email',
        'page_url',
        'page_name',
        'category',
        'title',
        'content',
        'metadata',
        'status',
        'priority',
        'admin_note',
        'adoption_status',
        'adopted_by',
        'adopted_at',
        'adoption_note',
        'satisfaction_score',
        'satisfaction_comment',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'adopted_at' => 'datetime',
            'satisfaction_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(FeedbackReply::class);
    }

    public function reward(): HasOne
    {
        return $this->hasOne(FeedbackReward::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(FeedbackAuditLog::class)->latest('id');
    }

    public function adoptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adopted_by');
    }

    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_BUG => 'Bug 报告',
            self::CATEGORY_SUGGESTION => '功能建议',
            self::CATEGORY_UX => '体验问题',
            default => '其他',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_REPLIED => '已回复',
            self::STATUS_CLOSED => '已关闭',
            default => $this->status,
        };
    }

    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => '低',
            self::PRIORITY_MEDIUM => '中',
            self::PRIORITY_HIGH => '高',
            self::PRIORITY_URGENT => '紧急',
            default => $this->priority,
        };
    }

    public function getAdoptionLabel(): string
    {
        return match ($this->adoption_status) {
            self::ADOPTION_ADOPTED => '已采纳',
            self::ADOPTION_REJECTED => '未采纳',
            default => '待评估',
        };
    }

    public function getStatusColor(): string
    {
        return self::$statusColors[$this->status] ?? 'secondary';
    }

    public function getPriorityColor(): string
    {
        return self::$priorityColors[$this->priority] ?? 'secondary';
    }

    public function getAdoptionColor(): string
    {
        return self::$adoptionColors[$this->adoption_status] ?? 'secondary';
    }

    public function isAdopted(): bool
    {
        return $this->adoption_status === self::ADOPTION_ADOPTED;
    }
}
