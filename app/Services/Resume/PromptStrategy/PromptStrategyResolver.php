<?php

declare(strict_types=1);

namespace App\Services\Resume\PromptStrategy;

final class PromptStrategyResolver
{
    /**
     * @return array<string,mixed>
     */
    public function resolve(?string $templateKey): array
    {
        $templates = config('resume_prompt_strategies.templates', []);
        $key = trim((string) $templateKey);
        $resolvedKey = $key !== '' && isset($templates[$key]) ? $key : 'general';
        $strategy = is_array($templates[$resolvedKey] ?? null) ? $templates[$resolvedKey] : [];

        return [
            'version' => (string) config('resume_prompt_strategies.version', 'v1'),
            'template_key' => $resolvedKey,
            'title' => (string) ($strategy['title'] ?? '通用策略'),
            'mode_bias' => (string) ($strategy['mode_bias'] ?? 'balanced'),
            'goal_weights' => is_array($strategy['goal_weights'] ?? null) ? $strategy['goal_weights'] : [],
            'hard_constraints' => is_array($strategy['hard_constraints'] ?? null) ? $strategy['hard_constraints'] : [],
            'style_rules' => is_array($strategy['style_rules'] ?? null) ? $strategy['style_rules'] : [],
            'module_bias' => is_array($strategy['module_bias'] ?? null) ? $strategy['module_bias'] : [],
            'risk_control' => is_array($strategy['risk_control'] ?? null) ? $strategy['risk_control'] : [],
        ];
    }
}
