<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Resume;
use App\Models\User;
use App\Notifications\HighMatchJobRecommendationNotification;
use App\Services\JobRecommendation\RecommendationQuotaService;
use App\Services\JobRecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 生成岗位推荐队列任务
 *
 * 职责：
 * 1. 获取用户最新简历
 * 2. 查询未推荐过的有效岗位
 * 3. 调用匹配分析（规则 + AI）
 * 4. 写入 job_recommendations 表
 * 5. 高分（>=80）自动推送通知
 *
 * 关联文档：docs/features-development-plan.md §5.6
 */
final class GenerateJobRecommendations implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** #12 重试 3 次：AI 网络抖动自动重试 */
    public int $tries = 3;

    /** #12 指数退避：10s → 30s → 90s */
    public array $backoff = [10, 30, 90];

    /** 任务超时时间（秒）：50 个岗位 × AI 调用，需较长处理时间 */
    public int $timeout = 600;

    public function __construct(
        private readonly int $userId,
        private readonly ?int $resumeId = null,
    ) {}

    public function handle(JobRecommendationService $service, RecommendationQuotaService $quotaService): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            Log::warning('岗位推荐任务：用户不存在', ['user_id' => $this->userId]);
            $quotaService->release($this->userId);

            return;
        }

        $resume = $this->resolveResume($user);

        if ($resume === null) {
            Log::info('岗位推荐任务：用户无可用简历', ['user_id' => $this->userId]);
            $service->clearProgress($this->userId);
            $quotaService->release($this->userId);

            return;
        }

        $service->setProgress($this->userId, 'processing', 0, 0);

        try {
            $result = $service->generateForResume($resume, $this->userId);

            // 高匹配岗位推送通知
            if ($result['high_match'] > 0) {
                $this->notifyHighMatchJobs($this->userId);
            }

            $service->setProgress($this->userId, 'completed', $result['created'], $result['created']);

            Log::info('岗位推荐任务完成', [
                'user_id' => $this->userId,
                'resume_id' => $resume->id,
                'created' => $result['created'],
                'high_match' => $result['high_match'],
            ]);
        } catch (Throwable $e) {
            // #24 失败反馈：写入失败原因，前端可显示具体错误
            $service->setProgress($this->userId, 'failed', 0, 0);
            $this->recordFailure($this->userId, $e);
            // #3 仅最后一次重试失败时归还配额，避免重试期间多次释放
            if ($this->attempts() >= $this->tries) {
                $quotaService->release($this->userId);
            }

            Log::error('岗位推荐任务失败', [
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
        // 不在 finally 中清除进度，保留 completed/failed 状态供前端轮询读取
        // 进度缓存会在 10 分钟后自动过期（见 setProgress 的 TTL）
    }

    /**
     * #24 失败反馈：将错误信息持久化到进度表
     */
    private function recordFailure(int $userId, Throwable $e): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('job_recommendation_progress')
                ->updateOrInsert(
                    ['user_id' => $userId],
                    [
                        'status' => 'failed',
                        'error' => mb_substr($e->getMessage(), 0, 500),
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]
                );

            // 同时写入缓存供前端立即读取
            \Illuminate\Support\Facades\Cache::put(
                "recommendation_progress:{$userId}",
                [
                    'status' => 'failed',
                    'total' => 0,
                    'processed' => 0,
                    'error' => mb_substr($e->getMessage(), 0, 200),
                ],
                now()->addMinutes(30)
            );
        } catch (Throwable $cacheException) {
            Log::warning('写入失败进度时出错', ['error' => $cacheException->getMessage()]);
        }
    }

    /**
     * 解析用户简历：优先指定 ID，否则取最新一份
     */
    private function resolveResume(User $user): ?Resume
    {
        if ($this->resumeId !== null) {
            return $user->resumes()->find($this->resumeId);
        }

        return $user->resumes()
            ->whereNotNull('content_raw')
            ->latest('updated_at')
            ->first();
    }

    /**
     * 为高匹配岗位推送通知
     */
    private function notifyHighMatchJobs(int $userId): void
    {
        $recommendations = \App\Models\JobRecommendation::where('user_id', $userId)
            ->where('match_score', '>=', JobRecommendationService::HIGH_MATCH_THRESHOLD)
            ->where('status', JobRecommendation::STATUS_NEW)
            ->limit(3)
            ->get();

        $user = User::find($userId);

        if ($user === null) {
            return;
        }

        foreach ($recommendations as $recommendation) {
            $user->notify(new HighMatchJobRecommendationNotification($recommendation));
        }
    }
}
