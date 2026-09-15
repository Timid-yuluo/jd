<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\QuotaDenyReason;

/**
 * 配额检查结果 — 中间件与 Service 之间的唯一数据载体
 *
 * 不可变 DTO，中间件 handle() 中创建后不再修改，
 * terminate() 中直接读取决策结果进行扣减。
 */
final readonly class QuotaResolution
{
    /**
     * @param bool $canProceed 是否允许继续
     * @param string $quotaKey 配额键名
     * @param string $source 扣减来源：'quota' | 'credit' | ''
     * @param int|null $creditId 使用的次卡 ID
     * @param string|null $creditName 次卡名称（用于提示消息）
     * @param bool $autoSelected 是否自动选择次卡
     * @param string|null $denyReason 拒绝原因（QuotaDenyReason 枚举值）
     * @param int|null $monthlyLimit 月配额上限
     * @param int|null $monthlyUsed 月配额已用
     * @param array|null $credits 可用次卡列表（确认流程用）
     */
    public function __construct(
        public bool $canProceed,
        public string $quotaKey,
        public string $source,
        public ?int $creditId = null,
        public ?string $creditName = null,
        public bool $autoSelected = false,
        public ?string $denyReason = null,
        public ?int $monthlyLimit = null,
        public ?int $monthlyUsed = null,
        public ?array $credits = null,
    ) {}

    /**
     * 是否需要次卡确认流程
     */
    public function needsCreditConfirmation(): bool
    {
        return $this->denyReason === QuotaDenyReason::QuotaExceededButCreditAvailable->value;
    }

    /**
     * 是否配额耗尽且无次卡
     */
    public function isQuotaExceeded(): bool
    {
        return $this->denyReason === QuotaDenyReason::QuotaExceeded->value;
    }
}
