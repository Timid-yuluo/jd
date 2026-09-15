<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateJobRecommendations;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 每日为活跃用户生成岗位推荐
 *
 * 调度：每日凌晨执行
 * 关联文档：docs/features-development-plan.md §5.2.3
 */
final class GenerateDailyRecommendations extends Command
{
    protected $signature = 'recommendations:daily
                            {--user= : 仅为指定用户 ID 生成}
                            {--limit=50 : 最大处理用户数}';

    protected $description = '为活跃用户生成每日岗位推荐（通过队列异步处理）';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $specificUser = $this->option('user');

        $query = User::query()
            ->whereHas('resumes', function ($q) {
                $q->whereNotNull('content_raw')
                  ->where('updated_at', '>', now()->subDays(90));
            })
            ->limit($limit);

        if ($specificUser !== null) {
            $query->where('id', (int) $specificUser);
        }

        $users = $query->get();
        $dispatched = 0;

        foreach ($users as $user) {
            $latestResume = Resume::where('user_id', $user->id)
                ->whereNotNull('content_raw')
                ->latest('updated_at')
                ->first();

            if ($latestResume === null) {
                continue;
            }

            GenerateJobRecommendations::dispatch($user->id, $latestResume->id);
            $dispatched++;
        }

        $this->info("已分发 {$dispatched} 个推荐生成任务");
        Log::info('每日推荐任务已分发', ['dispatched' => $dispatched]);

        return self::SUCCESS;
    }
}
