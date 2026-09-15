<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\User;

final class ResumeOptimizeUiService
{
    /**
     * @return array{advancedModelEnabled:bool,priorityQueueEnabled:bool}
     */
    public function resolveViewData(User $user): array
    {
        $plan = $user->currentPlan();

        return [
            'advancedModelEnabled' => (bool) $plan?->hasFeature('advanced_model'),
            'priorityQueueEnabled' => (bool) $plan?->hasFeature('priority_queue'),
        ];
    }

    public function buildOptimizeSuccessMessage(string $targetJob): string
    {
        return $targetJob !== ''
            ? "已按目标岗位「{$targetJob}」完成 AI 优化。"
            : '简历 AI 优化已完成。';
    }

    public function resolveApplyRedirectRoute(?string $redirectTo): string
    {
        return match ($redirectTo) {
            'show' => 'user.resumes.show',
            'editor' => 'user.resumes.editor',
            'optimize' => 'user.resumes.optimize-view',
            default => 'user.resumes.edit',
        };
    }

    public function buildApplySuccessMessage(bool $isPartialApply): string
    {
        return $isPartialApply
            ? '已按所选模块回写优化结果，未选模块保持不变，ATS 评分已重置，请重新评分。'
            : '已将优化结果写回简历正文并重新解析为模块，原 ATS 评分已重置，请重新评分。';
    }
}
