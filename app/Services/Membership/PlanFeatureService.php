<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\User;

final class PlanFeatureService
{
    /**
     * 深度策略包（对应前端 strategy-catalog 的 mode=deep 项）
     *
     * @var array<int, string>
     */
    private const ADVANCED_STRATEGY_TEMPLATES = [
        'backend',
        'devops_sre',
        'data_ai',
        'security',
    ];

    public function hasAdvancedModel(User $user): bool
    {
        return (bool) $user->currentPlan()?->hasFeature('advanced_model');
    }

    public function hasPriorityQueue(User $user): bool
    {
        return (bool) $user->currentPlan()?->hasFeature('priority_queue');
    }

    /**
     * @return array{
     *     allowed: bool,
     *     optimize_mode: string,
     *     prompt_strategy_template: string,
     *     message: string
     * }
     */
    public function resolveOptimizeAccess(
        User $user,
        ?string $optimizeMode,
        ?string $promptStrategyTemplate,
        bool $allowCreditOverride = false
    ): array {
        $mode = trim((string) ($optimizeMode ?? 'balanced'));
        if ($mode === '') {
            $mode = 'balanced';
        }
        $template = trim((string) ($promptStrategyTemplate ?? 'general'));
        if ($template === '') {
            $template = 'general';
        }

        if ($this->hasAdvancedModel($user)) {
            return [
                'allowed' => true,
                'optimize_mode' => $mode,
                'prompt_strategy_template' => $template,
                'message' => '',
            ];
        }

        if ($allowCreditOverride) {
            return [
                'allowed' => true,
                'optimize_mode' => 'balanced',
                'prompt_strategy_template' => 'general',
                'message' => '使用次卡仅可突破用量限制，高级模型与深度策略需升级套餐。',
            ];
        }

        if ($mode === 'deep' || in_array($template, self::ADVANCED_STRATEGY_TEMPLATES, true)) {
            return [
                'allowed' => false,
                'optimize_mode' => 'balanced',
                'prompt_strategy_template' => 'general',
                'message' => '当前套餐不支持高级模型与深度策略，请升级专业版后使用。',
            ];
        }

        return [
            'allowed' => true,
            'optimize_mode' => $mode,
            'prompt_strategy_template' => $template,
            'message' => '',
        ];
    }
}
