<?php

declare(strict_types=1);

namespace App\Services\Resume\Template;

use App\Models\Resume;
use App\Models\ResumeTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TemplateApplicationService
{
    /**
     * @return array{resume: Resume, apply_mode: string}
     */
    public function applyTemplate(ResumeTemplate $template, int $userId, int $resumeId = 0): array
    {
        $targetResume = null;
        if ($resumeId > 0) {
            $targetResume = Resume::query()
                ->where('user_id', $userId)
                ->find($resumeId);
            if (! $targetResume instanceof Resume) {
                throw new \RuntimeException('选择的现有简历不存在或无权限。');
            }
        }

        $applyMode = $targetResume instanceof Resume ? 'existing' : 'new';
        $blueprint = is_array($template->module_blueprint) ? $template->module_blueprint : [];
        $fontSettings = is_array($template->font_settings) ? $template->font_settings : [];

        $resume = DB::transaction(function () use ($template, $targetResume, $applyMode, $blueprint, $fontSettings, $userId): Resume {
            if ($targetResume instanceof Resume) {
                $resume = Resume::query()
                    ->where('id', $targetResume->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->firstOrFail();
            } else {
                $resume = new Resume;
                $resume->user_id = $userId;
                $resume->title = $template->name;
                $resume->target_job = $template->position;
                $resume->content_raw = '';
            }

            $resume->template = $template->template ?: 'classic';
            $resume->theme = $template->theme ?: 'blue';
            if (trim((string) $resume->target_job) === '') {
                $resume->target_job = $template->position;
            }

            $contentStructured = is_array($resume->content_structured) ? $resume->content_structured : [];
            if ($applyMode === 'existing') {
                $contentStructured['template_apply_undo'] = $this->buildUndoSnapshot($resume, $contentStructured);
            }
            $contentStructured['font_settings'] = $fontSettings;
            $contentStructured['template_source'] = [
                'template_id' => (int) $template->id,
                'template_slug' => (string) $template->slug,
                'template_name' => (string) $template->name,
            ];
            $resume->content_structured = $contentStructured;
            $resume->save();

            if ($applyMode === 'new' || ! $resume->modules()->exists()) {
                if ($applyMode === 'new') {
                    $resume->modules()->delete();
                }
                foreach ($blueprint as $index => $module) {
                    if (! is_array($module) || ! isset($module['type'])) {
                        continue;
                    }
                    $resume->modules()->create([
                        'type' => (string) $module['type'],
                        'sort_order' => (int) ($module['sort_order'] ?? $index),
                        'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
                    ]);
                }
            }

            $template->increment('usage_count');

            return $resume;
        });

        return [
            'resume' => $resume,
            'apply_mode' => $applyMode,
        ];
    }

    public function checkIdempotency(int $userId, string $idempotencyKey): ?Resume
    {
        $cachedResumeId = (int) Cache::get($this->idempotencyCacheKey($userId, $idempotencyKey), 0);
        if ($cachedResumeId <= 0) {
            return null;
        }

        return Resume::query()
            ->where('id', $cachedResumeId)
            ->where('user_id', $userId)
            ->first();
    }

    public function storeIdempotency(int $userId, string $idempotencyKey, int $resumeId): void
    {
        Cache::put(
            $this->idempotencyCacheKey($userId, $idempotencyKey),
            $resumeId,
            now()->addMinutes(10)
        );
    }

    public function idempotencyCacheKey(int $userId, string $idempotencyKey): string
    {
        return 'resume:template-apply:idempotency:user:'.$userId.':'.$idempotencyKey;
    }

    public function undoApply(Resume $resume): bool
    {
        return DB::transaction(function () use ($resume): bool {
            $lockedResume = Resume::query()
                ->whereKey($resume->id)
                ->lockForUpdate()
                ->first();
            if (! $lockedResume instanceof Resume) {
                return false;
            }

            $contentStructured = is_array($lockedResume->content_structured) ? $lockedResume->content_structured : [];
            $undoSnapshot = is_array($contentStructured['template_apply_undo'] ?? null)
                ? $contentStructured['template_apply_undo']
                : null;
            if (! is_array($undoSnapshot)) {
                return false;
            }

            $resumeSnapshot = is_array($undoSnapshot['resume'] ?? null)
                ? $undoSnapshot['resume']
                : null;
            if (! is_array($resumeSnapshot)) {
                return false;
            }

            $restoredContentStructured = is_array($resumeSnapshot['content_structured'] ?? null)
                ? $resumeSnapshot['content_structured']
                : [];
            unset($restoredContentStructured['template_apply_undo']);

            $lockedResume->template = (string) ($resumeSnapshot['template'] ?? $lockedResume->template ?? 'classic');
            $lockedResume->theme = (string) ($resumeSnapshot['theme'] ?? $lockedResume->theme ?? 'blue');
            $lockedResume->content_structured = $restoredContentStructured;
            $lockedResume->save();

            return true;
        });
    }

    /**
     * @param  array<string, mixed>  $contentStructured
     * @return array<string, mixed>
     */
    private function buildUndoSnapshot(Resume $resume, array $contentStructured): array
    {
        unset($contentStructured['template_apply_undo']);

        return [
            'snapshot_at' => now()->toDateTimeString(),
            'resume' => [
                'template' => (string) ($resume->template ?? 'classic'),
                'theme' => (string) ($resume->theme ?? 'blue'),
                'content_structured' => $contentStructured,
            ],
        ];
    }
}
