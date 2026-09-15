<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Membership\CreditService;
use Illuminate\Console\Command;

final class CleanExpiredCredits extends Command
{
    protected $signature = 'membership:clean-expired-credits';

    protected $description = '清理过期次卡（将余额置零）';

    public function handle(CreditService $creditService): int
    {
        $count = $creditService->cleanExpired();

        $this->info("已清理 {$count} 张过期次卡");

        return self::SUCCESS;
    }
}
