<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\AccessBansExpired;
use App\Models\AccessBan;
use App\Models\AdminAccessLog;
use Illuminate\Console\Command;

final class PurgeAccessBansAndLogs extends Command
{
    protected $signature = 'purge:access-bans-logs {--days=30 : Days of logs to retain}';

    protected $description = 'Purge expired access bans and old admin access logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $expiredBans = AccessBan::query()
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredBans->isNotEmpty()) {
            AccessBansExpired::dispatch($expiredBans);

            $count = AccessBan::query()
                ->where('expires_at', '<', now())
                ->delete();

            $this->info("Purged {$count} expired ban rules and dispatched expiry event.");
        } else {
            $this->info('No expired ban rules found.');
        }

        $cutoff = now()->subDays($days);

        $oldLogs = AdminAccessLog::query()
            ->where('accessed_at', '<', $cutoff)
            ->delete();

        $this->info("Purged {$oldLogs} access logs older than {$days} days.");

        return self::SUCCESS;
    }
}
