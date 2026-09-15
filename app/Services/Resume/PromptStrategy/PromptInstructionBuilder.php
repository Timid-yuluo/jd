<?php

declare(strict_types=1);

namespace App\Services\Resume\PromptStrategy;

final class PromptInstructionBuilder
{
    /**
     * @param  array<string,mixed>  $strategy
     * @return array<int,string>
     */
    public function buildLines(array $strategy): array
    {
        $lines = [];
        $lines[] = '策略版本：'.(string) ($strategy['version'] ?? 'v1');
        $lines[] = '策略模板：'.(string) ($strategy['title'] ?? '通用策略').' ('.(string) ($strategy['template_key'] ?? 'general').')';
        $lines[] = '推荐模式：'.(string) ($strategy['mode_bias'] ?? 'balanced');

        $goalWeights = is_array($strategy['goal_weights'] ?? null) ? $strategy['goal_weights'] : [];
        if ($goalWeights !== []) {
            $segments = [];
            foreach ($goalWeights as $goal => $weight) {
                $segments[] = sprintf('%s=%.2f', (string) $goal, (float) $weight);
            }
            $lines[] = '目标权重：'.implode('，', $segments);
        }

        $hardConstraints = is_array($strategy['hard_constraints'] ?? null) ? $strategy['hard_constraints'] : [];
        if ($hardConstraints !== []) {
            $lines[] = '硬约束：'.implode('、', array_map(static fn ($item): string => (string) $item, $hardConstraints));
        }

        $styleRules = is_array($strategy['style_rules'] ?? null) ? $strategy['style_rules'] : [];
        if ($styleRules !== []) {
            $lines[] = '风格规则：'.implode('、', array_map(static fn ($item): string => (string) $item, $styleRules));
        }

        $riskControl = is_array($strategy['risk_control'] ?? null) ? $strategy['risk_control'] : [];
        if ($riskControl !== []) {
            $lines[] = '风险控制：'.implode('、', array_map(static fn ($item): string => (string) $item, $riskControl));
        }

        return $lines;
    }
}
