<?php

declare(strict_types=1);

namespace App\Services\Resume;

final class ResumeOptimizationHandlerService
{
    /**
     * @param  array<int, array{type:string,data:array<string,mixed>}>  $existingModules
     * @param  array<int, array{type:string,data:array<string,mixed>}>  $optimizedModules
     * @param  array<int, string>  $selectedTypes
     * @return array<int, array{type:string,data:array<string,mixed>}>
     */
    public function mergeModulesBySelectedTypes(array $existingModules, array $optimizedModules, array $selectedTypes): array
    {
        $selectedLookup = array_fill_keys($selectedTypes, true);
        $optimizedQueues = [];
        foreach ($optimizedModules as $module) {
            $type = (string) ($module['type'] ?? '');
            if ($type === '' || ! isset($selectedLookup[$type])) {
                continue;
            }
            if (! isset($optimizedQueues[$type])) {
                $optimizedQueues[$type] = [];
            }
            $optimizedQueues[$type][] = [
                'type' => $type,
                'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
            ];
        }

        $merged = [];
        foreach ($existingModules as $module) {
            $type = (string) ($module['type'] ?? '');
            if ($type === '') {
                continue;
            }

            if (! isset($selectedLookup[$type])) {
                $merged[] = [
                    'type' => $type,
                    'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
                ];

                continue;
            }

            if (! empty($optimizedQueues[$type])) {
                $merged[] = array_shift($optimizedQueues[$type]);
            }
        }

        foreach ($optimizedQueues as $queue) {
            foreach ($queue as $module) {
                $merged[] = $module;
            }
        }

        return $merged;
    }
}
