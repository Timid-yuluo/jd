<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\JobRecommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * 高匹配岗位推荐通知
 *
 * 当 Queue Job 生成匹配分 >= 80 的推荐时自动推送。
 */
final class HighMatchJobRecommendationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly JobRecommendation $recommendation,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'high_match_job',
            'title' => '发现高匹配岗位',
            'body' => sprintf(
                '%s - %s（匹配度 %d%%）',
                $this->recommendation->company ?? '该公司',
                $this->recommendation->job_title ?? '岗位',
                $this->recommendation->match_score,
            ),
            'recommendation_id' => $this->recommendation->id,
            'job_title' => $this->recommendation->job_title,
            'company' => $this->recommendation->company,
            'city' => $this->recommendation->city,
            'match_score' => $this->recommendation->match_score,
        ];
    }
}
