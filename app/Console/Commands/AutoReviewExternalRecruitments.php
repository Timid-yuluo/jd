<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExternalRecruitment;
use Illuminate\Console\Command;

/**
 * 外部招聘数据智能审核命令
 *
 * 基于规则自动通过/驳回待审核记录：
 * - 通过：有公司名 + 有链接 + 有岗位信息 + 非重复
 * - 驳回：无公司名 / 无有效链接 / 无岗位信息 / 重复记录
 */
final class AutoReviewExternalRecruitments extends Command
{
    protected $signature = 'external-recruitments:auto-review';

    protected $description = '基于规则自动审核待审核的外部招聘数据';

    private int $approvedCount = 0;

    private int $rejectedCount = 0;

    public function handle(): int
    {
        $pending = ExternalRecruitment::where('review_status', ExternalRecruitment::REVIEW_PENDING)->get();

        if ($pending->isEmpty()) {
            $this->info('没有待审核记录');

            return self::SUCCESS;
        }

        $this->info("开始智能审核 {$pending->count()} 条待审核记录...");

        $bar = $this->output->createProgressBar($pending->count());
        $bar->start();

        foreach ($pending as $item) {
            $this->reviewItem($item);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("智能审核完成：通过 {$this->approvedCount} 条，驳回 {$this->rejectedCount} 条");

        return self::SUCCESS;
    }

    private function reviewItem(ExternalRecruitment $item): void
    {
        $hasCompany = !empty(trim((string) $item->company));
        $hasApplyUrl = !empty(trim((string) $item->apply_url));
        $hasAnnouncementUrl = !empty(trim((string) $item->announcement_url));
        $hasPositions = !empty(trim((string) $item->positions)) || !empty(trim((string) $item->title));

        // 驳回条件
        if (!$hasCompany || (!$hasApplyUrl && !$hasAnnouncementUrl) || !$hasPositions) {
            $reasons = [];
            if (!$hasCompany) {
                $reasons[] = '无公司名';
            }
            if (!$hasApplyUrl && !$hasAnnouncementUrl) {
                $reasons[] = '无有效链接';
            }
            if (!$hasPositions) {
                $reasons[] = '无岗位信息';
            }

            $item->update([
                'review_status' => ExternalRecruitment::REVIEW_REJECTED,
                'review_note' => '自动驳回：' . implode('、', $reasons),
                'reviewed_at' => now(),
            ]);
            $this->rejectedCount++;

            return;
        }

        // 重复检测
        if ($item->fingerprint) {
            $duplicateApproved = ExternalRecruitment::where('fingerprint', $item->fingerprint)
                ->where('id', '!=', $item->id)
                ->where('review_status', ExternalRecruitment::REVIEW_APPROVED)
                ->exists();
            if ($duplicateApproved) {
                $item->update([
                    'review_status' => ExternalRecruitment::REVIEW_REJECTED,
                    'review_note' => '自动驳回：重复记录',
                    'reviewed_at' => now(),
                ]);
                $this->rejectedCount++;

                return;
            }
        }

        // 通过
        $item->update([
            'review_status' => ExternalRecruitment::REVIEW_APPROVED,
            'review_note' => '自动审核通过',
            'reviewed_at' => now(),
        ]);
        $this->approvedCount++;
    }
}
