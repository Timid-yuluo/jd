<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Membership\SubscriptionService;
use Illuminate\Console\Command;

final class ExpireSubscriptions extends Command
{
    protected $signature = 'membership:expire-subscriptions';

    protected $description = '将到期订阅标记为过期，重置用户套餐为免费版';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $count = $subscriptionService->expireSubscriptions();

        $this->info("已处理 {$count} 个到期订阅");

        return self::SUCCESS;
    }
}
