<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\Resume;
use App\Models\ResumeModule;
use App\Models\ResumeOptimizeApplyLog;
use App\Models\ResumeOptimizeSession;
use App\Models\ResumeOptimizeVersion;
use App\Models\User;
use App\Services\Resume\Support\ResumeOptimizeDiffAnalyzer;
use App\Services\Resume\Support\ResumeOptimizeSelectionApplier;
use App\Support\Resume\ResumeModuleSerializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ResumeOptimizeSelectionService
{
    public function __construct(
        private readonly ResumeOptimizeSelectionApplier $selectionApplier,
        private readonly ResumeOptimizeDiffAnalyzer $diffAnalyzer,
    ) {}

    /**
     * @param  array<int,array<string,mixed>>  $selections
     * @return array<string,mixed>
     */
    public function applySelections(ResumeOptimizeSession $session, User $user, array $selections, ?string $resumeUpdatedAt = null): array
    {
        $resume = $session->resume;
        if (! $resume instanceof Resume) {
            throw new \RuntimeException('当前会话未关联有效简历。');
        }
        if ($session->status !== ResumeOptimizeSession::STATUS_SUCCEEDED && $session->status !== ResumeOptimizeSession::STATUS_APPLIED) {
            throw new \RuntimeException('会话尚未完成，不能应用替换。');
        }

        if ($resumeUpdatedAt !== null) {
            $resumeCurrent = optional($resume->updated_at)?->toIso8601String();
            if ($resumeCurrent !== $resumeUpdatedAt) {
                throw new \RuntimeException('检测到简历已发生更新，请刷新对比页后重试。');
            }
        }

        $version = $session->version;
        if (! $version instanceof ResumeOptimizeVersion) {
            throw new \RuntimeException('当前会话缺少可应用的版本数据。');
        }

        $beforeModules = $this->normalizeModulesArray($version->before_modules ?? []);
        $afterModules = $this->normalizeModulesArray($version->after_modules ?? []);
        $selectionMap = $this->selectionApplier->normalizeSelections($selections);
        $selectedKeys = array_keys($selectionMap);
        $selectedLookup = array_fill_keys($selectedKeys, true);
        $beforeIndexed = $this->diffAnalyzer->indexModulesByKey($beforeModules);
        $afterIndexed = $this->diffAnalyzer->indexModulesByKey($afterModules);
        $finalModules = [];

        foreach ($beforeIndexed as $key => $module) {
            if (isset($selectedLookup[$key]) && isset($afterIndexed[$key])) {
                $nextModule = $this->selectionApplier->applyModuleSelection(
                    $module,
                    $afterIndexed[$key],
                    is_array($selectionMap[$key] ?? null) ? $selectionMap[$key] : []
                );
                $finalModules[] = $nextModule;

                continue;
            }
            $finalModules[] = $module;
        }

        foreach ($afterIndexed as $key => $module) {
            if (! isset($beforeIndexed[$key]) && isset($selectedLookup[$key])) {
                $selection = is_array($selectionMap[$key] ?? null) ? $selectionMap[$key] : [];
                $onlyModule = ($selection['replace_module'] ?? false) === true;
                if ($onlyModule) {
                    $finalModules[] = $module;
                }
            }
        }

        foreach ($finalModules as $idx => $module) {
            $finalModules[$idx]['sort_order'] = $idx;
        }

        $undoToken = Str::random(48);
        $beforeSnapshot = [
            'content_raw' => (string) $resume->content_raw,
            'modules' => $this->resumeModulesToArray($resume),
            'captured_at' => now()->toIso8601String(),
        ];

        DB::transaction(function () use ($session, $user, $resume, $version, $selections, $finalModules, $undoToken, $beforeSnapshot): void {
            $session->forceFill([
                'status' => ResumeOptimizeSession::STATUS_APPLYING,
            ])->save();

            $resume->forceFill([
                'content_raw' => $this->buildRawFromModulesArray($finalModules, (string) $resume->content_raw),
                'ats_score' => 0,
            ])->save();

            $resume->modules()->delete();
            foreach ($finalModules as $idx => $module) {
                $resume->modules()->create([
                    'type' => (string) ($module['type'] ?? 'summary'),
                    'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
                    'sort_order' => $idx,
                ]);
            }

            $appliedResult = [
                'applied_module_count' => count($selections),
                'final_module_count' => count($finalModules),
                'before_snapshot' => $beforeSnapshot,
            ];

            ResumeOptimizeApplyLog::query()->create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'resume_id' => $resume->id,
                'selections' => $selections,
                'applied_result' => $appliedResult,
                'undo_token' => $undoToken,
            ]);

            $version->forceFill([
                'risk_tips' => array_merge(
                    is_array($version->risk_tips) ? $version->risk_tips : [],
                    ['应用完成后 ATS 已重置，建议重新评分。']
                ),
            ])->save();

            $session->forceFill([
                'status' => ResumeOptimizeSession::STATUS_APPLIED,
                'progress' => 100,
                'finished_at' => now(),
            ])->save();
            $this->safeLogInfo('resume_optimize_session_applied', [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'resume_id' => $resume->id,
                'user_id' => $user->id,
                'selection_count' => count($selections),
            ]);
            $this->emitOptimizeEvent('resume_optimize_applied', [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'resume_id' => $resume->id,
                'user_id' => $user->id,
                'status' => $session->status,
                'selection_count' => count($selections),
                'ab_variant' => (string) (($session->config['ab_variant'] ?? null) ?: 'baseline'),
            ]);
        });

        return [
            'undo_token' => $undoToken,
            'applied_module_count' => count($selections),
            'final_module_count' => count($finalModules),
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $modules
     * @return array<int,array<string,mixed>>
     */
    private function normalizeModulesArray(array $modules): array
    {
        $rows = [];
        $typeIndex = [];
        foreach ($modules as $idx => $module) {
            $type = trim((string) ($module['type'] ?? ''));
            if ($type === '') {
                continue;
            }
            $typeIndex[$type] = ($typeIndex[$type] ?? 0) + 1;
            $position = $typeIndex[$type];
            $rows[] = [
                'module_key' => $type.':'.$position,
                'type' => $type,
                'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
                'sort_order' => is_numeric($module['sort_order'] ?? null) ? (int) $module['sort_order'] : $idx,
            ];
        }

        return array_values($rows);
    }

    /**
     * @return array<int,array{type:string,data:array<string,mixed>,sort_order:int}>
     */
    private function resumeModulesToArray(Resume $resume): array
    {
        $modules = $resume->relationLoaded('modules')
            ? $resume->modules
            : $resume->modules()->orderBy('sort_order')->get();

        return $modules->map(static fn (ResumeModule $module): array => [
            'type' => (string) $module->type,
            'data' => is_array($module->data) ? $module->data : [],
            'sort_order' => (int) $module->sort_order,
        ])->values()->all();
    }

    /**
     * @param  array<int,array<string,mixed>>  $modules
     */
    private function buildRawFromModulesArray(array $modules, string $fallbackRaw): string
    {
        if ($modules === []) {
            return $fallbackRaw;
        }

        return ResumeModuleSerializer::toRawText($modules);
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function safeLogInfo(string $message, array $context): void
    {
        try {
            Log::info($message, $context);
        } catch (Throwable) {
            // ignore logging failures in restricted environments
        }
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function emitOptimizeEvent(string $event, array $context): void
    {
        $payload = array_merge([
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
        ], $context);
        $this->safeLogInfo('resume_optimize_event', $payload);
    }
}
