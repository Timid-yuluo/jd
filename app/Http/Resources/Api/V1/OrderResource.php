<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 订单资源
 *
 * 用于 API 响应中标准化订单数据的输出格式
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'user_id' => $this->user_id,
            'plan_slug' => $this->plan?->slug,
            'plan_name' => $this->plan?->name,
            'billing_cycle' => $this->billing_cycle,
            'amount' => $this->amount,
            'amount_yuan' => bcdiv((string) $this->amount, '100', 2),
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_no' => $this->payment_no,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'expired_at' => $this->expired_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
