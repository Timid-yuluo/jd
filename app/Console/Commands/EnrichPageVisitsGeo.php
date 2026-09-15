<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PageVisit;
use App\Services\IpLocationService;
use Illuminate\Console\Command;

final class EnrichPageVisitsGeo extends Command
{
    protected $signature = 'visits:enrich-geo {--limit=500 : 最大处理条数}';
    protected $description = '为缺少地域信息的访问记录补充 IP 归属地数据';

    public function handle(IpLocationService $ipLocation): int
    {
        $limit = (int) $this->option('limit');

        $visits = PageVisit::query()
            ->whereNull('country')
            ->whereNotNull('real_ip')
            ->where('real_ip', '!=', '')
            ->latest('created_at')
            ->limit($limit)
            ->get();

        if ($visits->isEmpty()) {
            $this->info('没有需要补充地域信息的记录。');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($visits->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($visits as $visit) {
            $geo = $ipLocation->lookup($visit->real_ip);
            if ($geo) {
                $visit->update([
                    'country' => $geo['country'] ?? null,
                    'city' => $geo['city'] ?? null,
                ]);
                $updated++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("完成：已更新 {$updated} 条记录的地域信息。");

        if ($failed > 0) {
            $this->warn("有 {$failed} 条记录未能解析。");
        }

        return self::SUCCESS;
    }
}
