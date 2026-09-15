<?php

declare(strict_types=1);

namespace App\Services\Resume\Support;

final class ResumeOptimizeSelectionApplier
{
    /**
     * @param  array<int,array<string,mixed>>  $rawSelections
     * @return array<string,array<string,mixed>>
     */
    public function normalizeSelections(array $rawSelections): array
    {
        $selectionMap = [];
        foreach ($rawSelections as $row) {
            $moduleKey = trim((string) ($row['module_key'] ?? ''));
            $action = trim((string) ($row['action'] ?? 'replace'));
            if ($moduleKey === '' || ! in_array($action, ['replace', 'append'], true)) {
                continue;
            }

            if (! isset($selectionMap[$moduleKey])) {
                $selectionMap[$moduleKey] = [
                    'replace_module' => false,
                    'allow_sensitive' => false,
                    'fields' => [],
                    'item_indexes' => [],
                ];
            }

            $fieldKey = trim((string) ($row['field_key'] ?? ''));
            $itemIndex = isset($row['item_index']) && is_numeric($row['item_index'])
                ? (int) $row['item_index']
                : null;
            $allowSensitive = (bool) ($row['allow_sensitive'] ?? false);
            if ($allowSensitive) {
                $selectionMap[$moduleKey]['allow_sensitive'] = true;
            }

            if ($fieldKey === '' && $itemIndex === null) {
                $selectionMap[$moduleKey]['replace_module'] = true;

                continue;
            }

            if ($fieldKey !== '') {
                $selectionMap[$moduleKey]['fields'][$fieldKey] = true;
            }

            if ($itemIndex !== null && $itemIndex >= 0) {
                $selectionMap[$moduleKey]['fields']['items'] = true;
                $selectionMap[$moduleKey]['item_indexes'][$itemIndex] = true;
            }
        }

        return $selectionMap;
    }

    /**
     * @param  array<string,mixed>  $beforeModule
     * @param  array<string,mixed>  $afterModule
     * @param  array<string,mixed>  $selection
     * @return array<string,mixed>
     */
    public function applyModuleSelection(array $beforeModule, array $afterModule, array $selection): array
    {
        $replaceModule = ($selection['replace_module'] ?? false) === true;
        $allowSensitive = ($selection['allow_sensitive'] ?? false) === true;
        $fields = is_array($selection['fields'] ?? null) ? $selection['fields'] : [];
        $itemIndexes = is_array($selection['item_indexes'] ?? null) ? array_map('intval', array_keys($selection['item_indexes'])) : [];

        if ($replaceModule) {
            $nextModule = $afterModule;
            if (($nextModule['type'] ?? '') === 'personal' && ! $allowSensitive) {
                $nextModule = $this->mergePersonalModuleWithSensitiveGuard($beforeModule, $nextModule);
            }

            return $nextModule;
        }

        $beforeData = is_array($beforeModule['data'] ?? null) ? $beforeModule['data'] : [];
        $afterData = is_array($afterModule['data'] ?? null) ? $afterModule['data'] : [];
        $nextData = $beforeData;

        foreach (array_keys($fields) as $fieldKey) {
            if ($fieldKey === 'items') {
                $beforeItems = is_array($beforeData['items'] ?? null) ? array_values($beforeData['items']) : [];
                $afterItems = is_array($afterData['items'] ?? null) ? array_values($afterData['items']) : [];
                if ($itemIndexes !== []) {
                    foreach ($itemIndexes as $index) {
                        if (array_key_exists($index, $afterItems)) {
                            $beforeItems[$index] = $afterItems[$index];
                        }
                    }
                    $nextData['items'] = array_values($beforeItems);

                    continue;
                }
                $nextData['items'] = $afterItems;

                continue;
            }

            if (($beforeModule['type'] ?? '') === 'personal' && ! $allowSensitive && in_array($fieldKey, ['name', 'phone', 'email'], true)) {
                continue;
            }
            if (array_key_exists($fieldKey, $afterData)) {
                $nextData[$fieldKey] = $afterData[$fieldKey];
            }
        }

        $nextModule = $beforeModule;
        $nextModule['data'] = $nextData;

        return $nextModule;
    }

    /**
     * @param  array<string,mixed>  $beforeModule
     * @param  array<string,mixed>  $afterModule
     * @return array<string,mixed>
     */
    public function mergePersonalModuleWithSensitiveGuard(array $beforeModule, array $afterModule): array
    {
        $beforeData = is_array($beforeModule['data'] ?? null) ? $beforeModule['data'] : [];
        $afterData = is_array($afterModule['data'] ?? null) ? $afterModule['data'] : [];
        foreach (['name', 'phone', 'email'] as $field) {
            if (array_key_exists($field, $beforeData)) {
                $afterData[$field] = $beforeData[$field];
            }
        }
        $afterModule['data'] = $afterData;

        return $afterModule;
    }
}
