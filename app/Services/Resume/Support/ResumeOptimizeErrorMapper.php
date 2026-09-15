<?php

declare(strict_types=1);

namespace App\Services\Resume\Support;

/**
 * 简历优化会话错误码映射表
 *
 * 将内部技术错误码统一映射为面向用户的友好文案，
 * 避免将异常堆栈、底层信息直接暴露给前端。
 *
 * 错误码命名规范：{模块}_{场景}_{具体原因}
 *  - 模块：QUEUE / AI / RESUME / QUOTA / CREDIT / VALIDATION / SYSTEM
 *  - 场景：DISPATCH / RESULT / RATE / EXCEEDED 等
 *  - 级别：通过 severity 字段标识影响范围
 */
final class ResumeOptimizeErrorMapper
{
    /**
     * 错误严重级别：影响用户下一步行为和告警阈值
     */
    public const SEVERITY_INFO = 'info';       // 信息提示，用户可继续操作
    public const SEVERITY_WARNING = 'warning'; // 警告，建议用户重试
    public const SEVERITY_ERROR = 'error';     // 错误，需要用户介入
    public const SEVERITY_FATAL = 'fatal';     // 致命，系统级故障

    /**
     * 错误分类：用于前端展示样式和客服分流
     */
    public const CATEGORY_QUEUE = 'queue';       // 队列类故障
    public const CATEGORY_AI = 'ai';             // AI 服务类故障
    public const CATEGORY_BUSINESS = 'business'; // 业务规则限制
    public const CATEGORY_DATA = 'data';         // 数据异常
    public const CATEGORY_SYSTEM = 'system';     // 系统级故障

    /**
     * 错误码常量：供调用方使用，避免魔法字符串
     */
    public const CODE_QUEUE_DISPATCH_FAILED = 'QUEUE_DISPATCH_FAILED';
    public const CODE_QUEUE_JOB_FAILED = 'QUEUE_JOB_FAILED';
    public const CODE_QUEUE_STALLED = 'QUEUE_STALLED';
    public const CODE_RESUME_NOT_FOUND = 'RESUME_NOT_FOUND';
    public const CODE_OPTIMIZE_FAILED = 'OPTIMIZE_FAILED';
    public const CODE_AI_RATE_LIMITED = 'AI_RATE_LIMITED';
    public const CODE_AI_TIMEOUT = 'AI_TIMEOUT';
    public const CODE_AI_RESULT_INVALID = 'AI_RESULT_INVALID';
    public const CODE_AI_RESULT_EMPTY = 'AI_RESULT_EMPTY';
    public const CODE_AI_DRIVER_UNAVAILABLE = 'AI_DRIVER_UNAVAILABLE';
    public const CODE_QUOTA_EXCEEDED = 'QUOTA_EXCEEDED';
    public const CODE_INVALID_CREDIT = 'INVALID_CREDIT';
    public const CODE_CREDIT_KEY_MISMATCH = 'CREDIT_KEY_MISMATCH';
    public const CODE_VALIDATION_FAILED = 'VALIDATION_FAILED';
    public const CODE_SESSION_CANCELED = 'SESSION_CANCELED';
    public const CODE_STATE_TRANSITION_INVALID = 'STATE_TRANSITION_INVALID';
    public const CODE_PERSISTENCE_FAILED = 'PERSISTENCE_FAILED';
    public const CODE_UNKNOWN = 'UNKNOWN';

    /**
     * 错误码 → 用户友好文案映射表
     *
     * @var array<string, array{message: string, retryable: bool, hint: string, severity: string, category: string}>
     */
    private const ERROR_MAP = [
        // ===== 队列类故障 =====
        self::CODE_QUEUE_DISPATCH_FAILED => [
            'message' => '优化任务提交失败，请稍后重试。',
            'retryable' => true,
            'hint' => '若多次失败，请检查网络后重试或联系客服。',
            'severity' => self::SEVERITY_WARNING,
            'category' => self::CATEGORY_QUEUE,
        ],
        self::CODE_QUEUE_JOB_FAILED => [
            'message' => '优化任务在后台执行失败。',
            'retryable' => true,
            'hint' => '请稍后重试，若持续失败请联系客服。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_QUEUE,
        ],
        self::CODE_QUEUE_STALLED => [
            'message' => '优化任务长时间未完成，可能队列处理超时。',
            'retryable' => true,
            'hint' => '请点击"重试"重新提交任务。',
            'severity' => self::SEVERITY_WARNING,
            'category' => self::CATEGORY_QUEUE,
        ],

        // ===== AI 服务类故障 =====
        self::CODE_OPTIMIZE_FAILED => [
            'message' => 'AI 优化处理失败，请稍后重试。',
            'retryable' => true,
            'hint' => '可尝试简化简历内容或更换优化目标后重试。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_AI,
        ],
        self::CODE_AI_RATE_LIMITED => [
            'message' => 'AI 服务当前请求过多，已自动切换备用模型仍未成功。',
            'retryable' => true,
            'hint' => '请稍候 1-2 分钟后重试。',
            'severity' => self::SEVERITY_WARNING,
            'category' => self::CATEGORY_AI,
        ],
        self::CODE_AI_TIMEOUT => [
            'message' => 'AI 服务响应超时，请稍后重试。',
            'retryable' => true,
            'hint' => '可尝试减少优化模块数量后重试。',
            'severity' => self::SEVERITY_WARNING,
            'category' => self::CATEGORY_AI,
        ],
        self::CODE_AI_RESULT_INVALID => [
            'message' => 'AI 返回的结果格式不正确，无法解析。',
            'retryable' => true,
            'hint' => '请重试，若仍失败可尝试调整优化目标。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_AI,
        ],
        self::CODE_AI_RESULT_EMPTY => [
            'message' => 'AI 未返回有效优化内容。',
            'retryable' => true,
            'hint' => '请确认简历内容非空后重试。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_AI,
        ],
        self::CODE_AI_DRIVER_UNAVAILABLE => [
            'message' => 'AI 服务暂时不可用，所有备用模型均无响应。',
            'retryable' => true,
            'hint' => '请稍后重试，若持续不可用请联系客服。',
            'severity' => self::SEVERITY_FATAL,
            'category' => self::CATEGORY_AI,
        ],

        // ===== 数据异常 =====
        self::CODE_RESUME_NOT_FOUND => [
            'message' => '关联的简历不存在或已被删除。',
            'retryable' => false,
            'hint' => '请返回简历列表重新选择目标简历。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_DATA,
        ],
        self::CODE_VALIDATION_FAILED => [
            'message' => '提交的优化参数不合法。',
            'retryable' => false,
            'hint' => '请检查优化目标和模块选择后重新提交。',
            'severity' => self::SEVERITY_WARNING,
            'category' => self::CATEGORY_DATA,
        ],

        // ===== 业务规则限制 =====
        self::CODE_QUOTA_EXCEEDED => [
            'message' => '当前套餐的优化次数已用尽。',
            'retryable' => false,
            'hint' => '请升级套餐或使用次卡继续。',
            'severity' => self::SEVERITY_INFO,
            'category' => self::CATEGORY_BUSINESS,
        ],
        self::CODE_INVALID_CREDIT => [
            'message' => '所选次卡无效或已过期。',
            'retryable' => false,
            'hint' => '请重新选择有效的次卡。',
            'severity' => self::SEVERITY_WARNING,
            'category' => self::CATEGORY_BUSINESS,
        ],
        self::CODE_CREDIT_KEY_MISMATCH => [
            'message' => '所选次卡类型不匹配当前操作。',
            'retryable' => false,
            'hint' => '请选择对应类型的次卡。',
            'severity' => self::SEVERITY_WARNING,
            'category' => self::CATEGORY_BUSINESS,
        ],
        self::CODE_SESSION_CANCELED => [
            'message' => '优化任务已被取消。',
            'retryable' => false,
            'hint' => '如需优化，请重新发起任务。',
            'severity' => self::SEVERITY_INFO,
            'category' => self::CATEGORY_BUSINESS,
        ],

        // ===== 系统级故障 =====
        self::CODE_STATE_TRANSITION_INVALID => [
            'message' => '优化任务状态异常，无法继续处理。',
            'retryable' => false,
            'hint' => '请刷新页面后重试，若持续出现请联系客服。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_SYSTEM,
        ],
        self::CODE_PERSISTENCE_FAILED => [
            'message' => '优化结果保存失败。',
            'retryable' => true,
            'hint' => '请稍后重试，若持续失败请联系客服。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_SYSTEM,
        ],
        self::CODE_UNKNOWN => [
            'message' => '优化过程中发生未知错误。',
            'retryable' => true,
            'hint' => '请稍后重试，若持续失败请联系客服并提供错误时间。',
            'severity' => self::SEVERITY_ERROR,
            'category' => self::CATEGORY_SYSTEM,
        ],
    ];

    /**
     * 默认兜底文案
     */
    private const DEFAULT_FALLBACK = [
        'message' => '优化失败，请稍后重试。',
        'retryable' => true,
        'hint' => '若持续失败，请联系客服处理。',
        'severity' => self::SEVERITY_ERROR,
        'category' => self::CATEGORY_SYSTEM,
    ];

    /**
     * 根据错误码获取完整的错误信息
     *
     * @param  string|null  $errorCode  内部错误码
     * @return array{code: string, message: string, retryable: bool, hint: string, severity: string, category: string}
     */
    public function resolve(?string $errorCode): array
    {
        $code = trim((string) $errorCode);
        $entry = self::ERROR_MAP[$code] ?? self::DEFAULT_FALLBACK;

        return [
            'code' => $code !== '' ? $code : self::CODE_UNKNOWN,
            'message' => $entry['message'],
            'retryable' => $entry['retryable'],
            'hint' => $entry['hint'],
            'severity' => $entry['severity'],
            'category' => $entry['category'],
        ];
    }

    /**
     * 仅获取用户友好消息文案
     */
    public function message(?string $errorCode): string
    {
        return $this->resolve($errorCode)['message'];
    }

    /**
     * 判断错误码是否可重试
     */
    public function isRetryable(?string $errorCode): bool
    {
        return $this->resolve($errorCode)['retryable'];
    }

    /**
     * 获取错误严重级别
     */
    public function severity(?string $errorCode): string
    {
        return $this->resolve($errorCode)['severity'];
    }

    /**
     * 获取错误分类
     */
    public function category(?string $errorCode): string
    {
        return $this->resolve($errorCode)['category'];
    }

    /**
     * 判断错误码是否为致命级别（需要立即告警）
     */
    public function isFatal(?string $errorCode): bool
    {
        return $this->resolve($errorCode)['severity'] === self::SEVERITY_FATAL;
    }

    /**
     * 判断错误码是否属于队列类故障
     */
    public function isQueueFailure(?string $errorCode): bool
    {
        return $this->resolve($errorCode)['category'] === self::CATEGORY_QUEUE;
    }

    /**
     * 判断错误码是否属于 AI 服务类故障
     */
    public function isAiFailure(?string $errorCode): bool
    {
        return $this->resolve($errorCode)['category'] === self::CATEGORY_AI;
    }

    /**
     * 获取所有已定义的错误码列表
     *
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys(self::ERROR_MAP);
    }
}
