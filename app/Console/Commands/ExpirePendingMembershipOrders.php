<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Membership\SubscriptionService;
use Illuminate\Console\Command;

final class ExpirePendingMembershipOrders extends Command
{
    protected $signature = 'membership:expire-pending-orders {--hours=72 : 超过多少小时的待支付订单将过期}';

    protected $description = '将超时未支付的会员订单与次卡订单标记为已过期';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $result = $subscriptionService->expirePendingOrders($hours);

        $this->info(sprintf(
            '已过期订阅订单 %d 笔，次卡订单 %d 笔（阈值：%d 小时）',
            $result['subscription_orders'],
            $result['credit_pack_orders'],
            $hours
        ));

        return self::SUCCESS;
    }
}
