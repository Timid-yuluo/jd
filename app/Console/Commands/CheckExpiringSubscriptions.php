<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 检查即将到期的订阅并发送提醒邮件
 * 使用 Cache 去重，确保每个用户每天只收到一次提醒
 */
final class CheckExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expiring {--days=3 : Days before expiry to notify}';
    protected $description = 'Notify users whose subscriptions are expiring soon and auto-expire past-due subscriptions';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $now = now();
        $notified = 0;

        // 查询即将到期的活跃订阅
        Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expires_at', '<=', $now->copy()->addDays($days))
            ->where('expires_at', '>', $now)
            ->with(['user', 'plan'])
            ->chunk(50, function ($subs) use ($now, &$notified) {
                foreach ($subs as $sub) {
                    $user = $sub->user;
                    if (! $user) {
                        continue;
                    }

                    // Cache 去重：每个用户每天最多发送一次到期提醒
                    $cacheKey = "subscription_expiry_notified:{$user->id}:{$now->toDateString()}";
                    if (Cache::has($cacheKey)) {
                        continue;
                    }

                    $daysLeft = max(0, (int) $now->diffInDays($sub->expires_at, false));
                    $user->notify(new SubscriptionExpiringNotification($sub, $daysLeft));

                    // 标记已发送，保留到当天结束
                    Cache::put($cacheKey, true, $now->copy()->endOfDay());

                    $notified++;
                    $this->line("Notified user #{$user->id}: {$daysLeft} days left");
                }
            });

        if ($notified > 0) {
            $this->info("Sent {$notified} expiry notifications.");
        }

        // 自动过期已到期的订阅
        $expiredCount = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expires_at', '<=', $now)
            ->update(['status' => Subscription::STATUS_EXPIRED]);

        if ($expiredCount > 0) {
            $this->info("Expired {$expiredCount} subscriptions.");
        }

        return self::SUCCESS;
    }
}
