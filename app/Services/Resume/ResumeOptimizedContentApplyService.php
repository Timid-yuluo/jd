<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\Resume;
use App\Models\ResumeModule;
use App\Services\Resume\Parsing\ResumeModuleParserService;
use App\Support\Resume\ResumeModuleSerializer;
use Illuminate\Support\Facades\DB;

final class ResumeOptimizedContentApplyService
{
    public function __construct(
        private readonly ResumeModuleParserService $moduleParserService,
        private readonly ResumeOptimizationHandlerService $optimizationHandlerService,
        private readonly ResumeVersionService $versionService,
    ) {}

    /**
     * @param  array<int, string>  $selectedModuleTypes
     * @return array{is_partial_apply:bool}
     */
    public function apply(Resume $resume, array $selectedModuleTypes = []): array
    {
        // 应用优化前先保存当前版本快照
        $this->versionService->createSnapshot($resume, 'optimize', '应用AI优化结果前自动保存');

        $contentStructured = is_array($resume->content_structured) ? $resume->content_structured : [];
        unset($contentStructured['ats']);

        $selectedModuleTypes = array_values(array_unique(array_filter(
            array_map(static fn ($type): string => is_string($type) ? trim($type) : '', $selectedModuleTypes),
            static fn (string $type): bool => $type !== ''
        )));
        $isPartialApply = $selectedModuleTypes !== [];

        DB::transaction(function () use ($resume, $contentStructured, $selectedModuleTypes, $isPartialApply): void {
            $parsed = $this->moduleParserService->parseRawToModules((string) $resume->optimized_text);

            $finalModules = $parsed;
            if ($isPartialApply) {
                $existingModules = $resume->modules()
                    ->orderBy('sort_order')
                    ->get()
                    ->map(static fn (ResumeModule $module) => [
                        'type' => (string) $module->type,
                        'data' => is_array($module->data) ? $module->data : [],
                    ])
                    ->all();

                $finalModules = $this->optimizationHandlerService->mergeModulesBySelectedTypes(
                    $existingModules,
                    $parsed,
                    $selectedModuleTypes
                );
            }

            foreach ($finalModules as $idx => $module) {
                $finalModules[$idx]['sort_order'] = $idx;
            }

            $resume->forceFill([
                'content_raw' => ResumeModuleSerializer::toRawText($finalModules),
                'ats_score' => 0,
                'content_structured' => $contentStructured,
            ])->save();

            $resume->modules()->delete();
            foreach ($finalModules as $idx => $module) {
                $resume->modules()->create([
                    'type' => (string) ($module['type'] ?? 'summary'),
                    'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
                    'sort_order' => $idx,
                ]);
            }
        });

        return [
            'is_partial_apply' => $isPartialApply,
        ];
    }
}
