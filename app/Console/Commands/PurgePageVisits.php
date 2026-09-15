<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PageVisit;
use Illuminate\Console\Command;

final class PurgePageVisits extends Command
{
    protected $signature = 'purge:page-visits {--days=90 : 保留天数}';
    protected $description = '清理超过保留期的访问记录';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = PageVisit::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("已清理 {$count} 条超过 {$days} 天的访问记录。");

        return self::SUCCESS;
    }
}
