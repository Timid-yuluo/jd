<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\CreditPack;
use App\Models\CreditPackOrder;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class CreditService
{
    /**
     * 购买次卡（创建订单，等待支付）
     */
    public function createOrder(User $user, CreditPack $pack): CreditPackOrder
    {
        return CreditPackOrder::create([
            'order_no' => $this->generateOrderNo($user->id, $pack->id),
            'user_id' => $user->id,
            'credit_pack_id' => $pack->id,
            'amount' => $pack->price,
            'status' => CreditPackOrder::STATUS_PENDING,
        ]);
    }

    /**
     * 支付成功后发放次卡
     */
    public function fulfillOrder(
        CreditPackOrder $order,
        ?string $paymentNo = null,
        ?int $paidAmount = null,
        string $paymentMethod = 'wechat'
    ): UserCredit {
        return DB::transaction(function () use ($order, $paymentNo, $paidAmount, $paymentMethod) {
            /** @var CreditPackOrder|null $lockedOrder */
            $lockedOrder = CreditPackOrder::query()
                ->with('creditPack')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                throw new RuntimeException('次卡订单不存在');
            }

            if ($lockedOrder->isPaid()) {
                if ($paymentNo !== null && $lockedOrder->payment_no !== null && $lockedOrder->payment_no !== $paymentNo) {
                    throw new RuntimeException('订单已由其他支付流水完成');
                }

                $existing = UserCredit::query()
                    ->where('user_id', $lockedOrder->user_id)
                    ->where('source_type', 'pack_order')
                    ->where('source_id', $lockedOrder->id)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            if ($paidAmount === null || $paidAmount !== (int) $lockedOrder->amount) {
                throw new InvalidArgumentException('支付金额与订单金额不一致');
            }

            if ($paymentNo !== null && CreditPackOrder::query()
                ->where('payment_no', $paymentNo)
                ->where('id', '!=', $lockedOrder->id)
                ->exists()
            ) {
                throw new RuntimeException('支付流水号已被其他订单使用');
            }

            $lockedOrder->update([
                'status' => CreditPackOrder::STATUS_PAID,
                'paid_at' => now(),
                'payment_method' => $paymentMethod,
                'payment_no' => $paymentNo,
            ]);

            $pack = $lockedOrder->creditPack;

            return UserCredit::create([
                'user_id' => $lockedOrder->user_id,
                'quota_key' => $pack->quota_key,
                'remaining' => $pack->credits,
                'source_type' => 'pack_order',
                'source_id' => $lockedOrder->id,
                'expires_at' => $pack->validity_days > 0
                    ? now()->addDays($pack->validity_days)
                    : null,
            ]);
        });
    }

    /**
     * 后台管理员给用户充值次卡
     */
    public function adminGrant(User $user, ?string $quotaKey, int $credits, ?int $validityDays = null): UserCredit
    {
        return $this->grant($user, $quotaKey, $credits, $validityDays, 'admin');
    }

    /**
     * 反馈采纳奖励次卡
     */
    public function feedbackRewardGrant(User $user, ?string $quotaKey, int $credits, ?int $validityDays, int $feedbackRewardId): UserCredit
    {
        return $this->grant($user, $quotaKey, $credits, $validityDays, 'feedback_reward', $feedbackRewardId);
    }

    /**
     * 清理过期次卡（定时任务调用）
     */
    public function cleanExpired(): int
    {
        return UserCredit::where('expires_at', '<', now())
            ->where('remaining', '>', 0)
            ->update(['remaining' => 0]);
    }

    /**
     * 获取用户可用次卡汇总
     */
    public function getUserCreditsSummary(User $user): array
    {
        $credits = UserCredit::where('user_id', $user->id)
            ->where('remaining', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('expires_at')
            ->get();

        $summary = [];
        foreach ($credits as $credit) {
            $key = $credit->quota_key ?? 'universal';
            if (! isset($summary[$key])) {
                $summary[$key] = ['total' => 0, 'items' => []];
            }
            $summary[$key]['total'] += $credit->remaining;
            $summary[$key]['items'][] = $credit;
        }

        return $summary;
    }

    private function generateOrderNo(int $userId, int $packId): string
    {
        return 'CP'.now()->format('YmdHis').str_pad((string) ($userId % 10000), 4, '0', STR_PAD_LEFT).random_int(10, 99);
    }

    private function grant(
        User $user,
        ?string $quotaKey,
        int $credits,
        ?int $validityDays,
        string $sourceType,
        ?int $sourceId = null
    ): UserCredit {
        if ($credits < 1) {
            throw new InvalidArgumentException('赠送次数必须大于 0');
        }

        return UserCredit::create([
            'user_id' => $user->id,
            'quota_key' => $quotaKey,
            'remaining' => $credits,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'expires_at' => $validityDays && $validityDays > 0
                ? now()->addDays($validityDays)
                : null,
        ]);
    }
}
