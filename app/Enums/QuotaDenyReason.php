<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 配额拒绝原因 — 替代代码中的魔法字符串
 */
enum QuotaDenyReason: string
{
    /** 月配额已用完且无可用次卡 */
    case QuotaExceeded = 'QUOTA_EXCEEDED';

    /** 月配额已用完但有次卡可用（需用户确认） */
    case QuotaExceededButCreditAvailable = 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE';

    /** 指定的次卡无效或已用完 */
    case InvalidCredit = 'INVALID_CREDIT';

    /** 次卡类型与当前操作不匹配 */
    case CreditKeyMismatch = 'CREDIT_KEY_MISMATCH';

    /**
     * 人类可读的中文描述
     */
    public function label(): string
    {
        return match ($this) {
            self::QuotaExceeded => '本月次数已用完，请升级套餐或购买次卡',
            self::QuotaExceededButCreditAvailable => '本月免费次数已用完',
            self::InvalidCredit => '次卡无效或已用完',
            self::CreditKeyMismatch => '次卡类型与当前操作不匹配',
        };
    }
}
