<?php

declare(strict_types=1);

namespace App\Services\Resume\Support;

use Illuminate\Support\Arr;

final class ResumeOptimizeDiffAnalyzer
{
    /**
     * @param  array<int,array<string,mixed>>  $beforeModules
     * @param  array<int,array<string,mixed>>  $afterModules
     * @return array<string,mixed>
     */
    public function buildDiffMap(array $beforeModules, array $afterModules): array
    {
        $beforeIndexed = $this->indexModulesByKey($beforeModules);
        $afterIndexed = $this->indexModulesByKey($afterModules);

        $changed = [];
        foreach ($afterIndexed as $key => $module) {
            $before = $beforeIndexed[$key] ?? null;
            if ($before === null) {
                $changed[] = ['module_key' => $key, 'change' => 'added'];

                continue;
            }
            if (json_encode($before['data'], JSON_UNESCAPED_UNICODE) !== json_encode($module['data'], JSON_UNESCAPED_UNICODE)) {
                $changed[] = ['module_key' => $key, 'change' => 'modified'];
            }
        }

        foreach ($beforeIndexed as $key => $module) {
            if (! isset($afterIndexed[$key])) {
                $changed[] = ['module_key' => $key, 'change' => 'removed'];
            }
        }

        return [
            'changed_count' => count($changed),
            'changed_modules' => $changed,
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $beforeModules
     * @param  array<int,array<string,mixed>>  $afterModules
     * @return array<int,string>
     */
    public function buildRiskTips(array $beforeModules, array $afterModules): array
    {
        $tips = [];
        if (count($afterModules) < count($beforeModules)) {
            $tips[] = '优化后模块数量减少，请重点确认内容是否被过度压缩。';
        }

        foreach ($afterModules as $module) {
            $content = trim((string) Arr::get($module, 'data.content', ''));
            if ($content === '' && empty(Arr::get($module, 'data.items', []))) {
                $tips[] = '存在空模块，请确认是否需要保留。';
                break;
            }
        }

        if ($tips === []) {
            $tips[] = '未发现明显结构风险，建议按目标岗位再做人工复核。';
        }

        return $tips;
    }

    /**
     * @param  array<int,array<string,mixed>>  $modules
     */
    public function estimateScore(array $modules, string $raw): int
    {
        $moduleCount = count($modules);
        $charCount = mb_strlen($raw);
        $bulletCount = 0;
        foreach ($modules as $module) {
            $items = Arr::get($module, 'data.items', []);
            if (is_array($items)) {
                $bulletCount += count(array_filter($items, static fn ($item): bool => trim((string) $item) !== ''));
            }
        }

        return min(100, max(20, (int) round($moduleCount * 8 + $bulletCount * 2 + min(40, $charCount / 60))));
    }

    /**
     * @param  array<int,array<string,mixed>>  $modules
     * @return array<string,array<string,mixed>>
     */
    public function indexModulesByKey(array $modules): array
    {
        $indexed = [];
        foreach ($modules as $module) {
            $key = trim((string) ($module['module_key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $indexed[$key] = $module;
        }

        return $indexed;
    }
}
