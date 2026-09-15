<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

final class Resume extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'target_job',
        'target_company',
        'target_job_title',
        'target_job_description',
        'career_track_id',
        'optimize_goals',
        'content_raw',
        'content_structured',
        'template',
        'theme',
        'share_token',
        'is_shareable',
        'share_password',
        'share_view_count',
        'ats_score',
        'optimized_text',
        'highlights',
    ];

    protected function casts(): array
    {
        return [
            'content_structured' => 'array',
            'ats_score' => 'integer',
            'highlights' => 'array',
            'optimize_goals' => 'array',
        ];
    }

    /**
     * 设置 content_raw 时自动加密存储
     */
    public function setContentRawAttribute(?string $value): void
    {
        $this->attributes['content_raw'] = $value !== null
            ? encrypt($value, false)
            : null;
    }

    /**
     * 获取 content_raw 时自动解密
     */
    public function getContentRawAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // 兼容旧数据：如果解密失败，说明是明文存储的旧数据
        try {
            return decrypt($value, false);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * 设置分享密码时自动哈希加密存储
     */
    public function setSharePasswordAttribute(?string $value): void
    {
        $this->attributes['share_password'] = $value !== null && $value !== ''
            ? Hash::make($value)
            : null;
    }

    /**
     * 验证分享密码是否正确（兼容旧明文数据）
     */
    public function verifySharePassword(string $password): bool
    {
        $stored = $this->getRawOriginal('share_password');

        if ($stored === null || $stored === '') {
            return true;
        }

        // 兼容旧明文数据：bcrypt 哈希以 $2y$ 开头
        if (str_starts_with($stored, '$2y$')) {
            return Hash::check($password, $stored);
        }

        // 旧明文数据，使用常量时间比较防止时序攻击
        return hash_equals($stored, $password);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function careerTrack(): BelongsTo
    {
        return $this->belongsTo(CareerTrack::class);
    }

    public function interviewSessions(): HasMany
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(ResumeModule::class)->orderBy('sort_order');
    }

    public function atsScoreLogs(): HasMany
    {
        return $this->hasMany(ResumeAtsScoreLog::class)->orderByDesc('created_at');
    }

    public function completeness(): int
    {
        $items = $this->completenessChecklist();
        $completed = count(array_filter($items, fn ($item) => $item['passed']));
        $total = count($items);
        return $total > 0 ? (int) round($completed / $total * 100) : 0;
    }

    /**
     * 简历完整度检查清单
     * 返回每项检查的名称、是否通过、权重和建议
     *
     * @return array<int, array{name: string, passed: bool, weight: int, tip: string}>
     */
    public function completenessChecklist(): array
    {
        $modules = $this->relationLoaded('modules') ? $this->modules : $this->modules()->get();
        $moduleTypes = $modules->pluck('type')->unique()->values();

        $checks = [
            [
                'name' => '基本信息',
                'passed' => $moduleTypes->contains('personal_info'),
                'weight' => 15,
                'tip' => '添加个人信息模块，包含姓名、联系方式等',
            ],
            [
                'name' => '求职意向',
                'passed' => ! empty($this->target_job) || $moduleTypes->contains('job_intention'),
                'weight' => 10,
                'tip' => '设置目标岗位，帮助 AI 更精准优化',
            ],
            [
                'name' => '教育经历',
                'passed' => $moduleTypes->contains('education'),
                'weight' => 12,
                'tip' => '添加教育背景，展示学历与专业',
            ],
            [
                'name' => '工作/实习经历',
                'passed' => $moduleTypes->contains('work_experience') || $moduleTypes->contains('internship'),
                'weight' => 15,
                'tip' => '添加工作或实习经历，突出实践能力',
            ],
            [
                'name' => '项目经验',
                'passed' => $moduleTypes->contains('project'),
                'weight' => 12,
                'tip' => '添加项目经验，展示实际成果',
            ],
            [
                'name' => '专业技能',
                'passed' => $moduleTypes->contains('skill'),
                'weight' => 10,
                'tip' => '添加技能模块，列出核心技能',
            ],
            [
                'name' => '自我评价',
                'passed' => $moduleTypes->contains('self_evaluation'),
                'weight' => 8,
                'tip' => '添加自我评价，展现个人优势',
            ],
            [
                'name' => 'ATS 评分',
                'passed' => $this->ats_score !== null,
                'weight' => 10,
                'tip' => '进行 ATS 评分，了解简历通过率',
            ],
            [
                'name' => '模块数量充足',
                'passed' => $modules->count() >= 5,
                'weight' => 8,
                'tip' => '建议至少包含5个模块，信息更完整',
            ],
        ];

        return $checks;
    }

    public function optimizeSessions(): HasMany
    {
        return $this->hasMany(ResumeOptimizeSession::class);
    }

    public function optimizeApplyLogs(): HasMany
    {
        return $this->hasMany(ResumeOptimizeApplyLog::class);
    }

    public function buildModulesSnapshot(): array
    {
        // 优先使用已预加载的关系，避免额外查询
        $modules = $this->relationLoaded('modules') ? $this->modules : $this->modules()->get();

        return $modules->map(fn ($m) => [
            'type' => $m->type,
            'data' => $m->data,
            'sort_order' => $m->sort_order,
        ])->all();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeScored($query)
    {
        return $query->whereNotNull('ats_score');
    }

    public function scopeUnscored($query)
    {
        return $query->whereNull('ats_score');
    }

    public function scopeOptimized($query)
    {
        return $query->whereNotNull('optimized_text');
    }

    public function scopeUnoptimized($query)
    {
        return $query->whereNull('optimized_text');
    }
}
