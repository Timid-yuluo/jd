<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 邮件模板模型
 */
class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subject',
        'content',
        'description',
        'variables',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * 创建者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 使用该模板的通知
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class);
    }

    /**
     * 作用域：仅启用
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 渲染模板内容
     */
    public function render(array $data = []): array
    {
        $subject = $this->subject;
        $content = $this->content;

        foreach ($data as $key => $value) {
            $subject = str_replace('{{ '.$key.' }}', $value, $subject);
            $subject = str_replace('{{'.$key.'}}', $value, $subject);
            $content = str_replace('{{ '.$key.' }}', $value, $content);
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    /**
     * 获取默认变量
     */
    public static function defaultVariables(): array
    {
        return [
            'site_name' => '网站名称',
            'site_url' => '网站地址',
            'user_name' => '用户名称',
            'user_email' => '用户邮箱',
            'current_date' => '当前日期',
            'current_time' => '当前时间',
            'notification_title' => '通知标题',
            'notification_content' => '通知内容',
        ];
    }
}
