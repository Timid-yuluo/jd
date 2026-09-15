<?php

declare(strict_types=1);

namespace App\Services\Resume\Support;

final class ResumeOptimizationPayloadSanitizer
{
    /**
     * @return array<int,string>
     */
    public function sanitizeOptimizeGoals(mixed $goals): array
    {
        if (! is_array($goals)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($item): string => trim((string) $item), $goals),
            static fn (string $item): bool => $item !== ''
        ));
    }

    /**
     * @return array<string,mixed>
     */
    public function sanitizeResumeProfile(mixed $profile): array
    {
        if (! is_array($profile)) {
            return [];
        }

        $safe = [];
        foreach ([
            'profile_version',
            'prompt_strategy_template',
            'optimize_mode',
        ] as $key) {
            if (is_string($profile[$key] ?? null)) {
                $safe[$key] = mb_substr(trim((string) $profile[$key]), 0, 64);
            }
        }

        foreach ([
            'module_count',
            'heading_count',
            'bullet_count',
            'quantified_bullet_count',
            'quantified_ratio',
            'keyword_total',
            'keyword_hit',
            'keyword_miss',
            'module_overall_score',
            'estimated_optimize_score',
        ] as $key) {
            if (isset($profile[$key]) && is_numeric($profile[$key])) {
                $safe[$key] = (int) $profile[$key];
            }
        }

        if (is_array($profile['optimize_goals'] ?? null)) {
            $safe['optimize_goals'] = array_values(array_slice(array_filter(
                array_map(static fn ($item): string => trim((string) $item), $profile['optimize_goals']),
                static fn (string $item): bool => $item !== ''
            ), 0, 12));
        }

        if (is_array($profile['keyword_missing'] ?? null)) {
            $safe['keyword_missing'] = array_values(array_slice(array_filter(
                array_map(static fn ($item): string => mb_substr(trim((string) $item), 0, 64), $profile['keyword_missing']),
                static fn (string $item): bool => $item !== ''
            ), 0, 12));
        }

        if (is_array($profile['weak_modules'] ?? null)) {
            $safe['weak_modules'] = array_values(array_slice(array_filter(
                array_map(static fn ($item): string => mb_substr(trim((string) $item), 0, 64), $profile['weak_modules']),
                static fn (string $item): bool => $item !== ''
            ), 0, 8));
        }

        return $safe;
    }

    /**
     * @return array<string,string>
     */
    public function sanitizeModuleStrategies(mixed $moduleStrategies): array
    {
        if (! is_array($moduleStrategies)) {
            return [];
        }

        $allowed = ['balanced', 'results', 'technical'];
        $safe = [];
        foreach ($moduleStrategies as $moduleKey => $strategy) {
            $key = trim((string) $moduleKey);
            $value = trim((string) $strategy);
            if ($key === '' || $value === '' || ! in_array($value, $allowed, true)) {
                continue;
            }
            $safe[mb_substr($key, 0, 64)] = $value;
        }

        return $safe;
    }
}
