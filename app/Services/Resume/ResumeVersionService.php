<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\Resume;
use App\Models\ResumeVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ResumeVersionService
{
    /**
     * 每份简历最多保留的版本数量
     */
    private const MAX_VERSIONS_PER_RESUME = 50;

    /**
     * 创建版本快照（带去重检查）
     *
     * @param Resume $resume 简历实例
     * @param string $source 快照来源：manual/auto_save/restore/optimize
     * @param string $changeSummary 变更说明
     * @return ResumeVersion|null 新创建的版本，如果内容无变化则返回 null
     */
    public function createSnapshot(Resume $resume, string $source = 'auto_save', string $changeSummary = ''): ?ResumeVersion
    {
        // 预加载 modules 关系，避免 buildModulesSnapshot 额外查询
        $resume->loadMissing('modules');

        $snapshotHash = ResumeVersion::computeSnapshotHash($resume);

        // 去重检查：如果最新版本的哈希与当前相同，跳过
        $latestVersion = ResumeVersion::where('resume_id', $resume->id)
            ->orderByDesc('created_at')
            ->first();

        if ($latestVersion && $latestVersion->snapshot_hash === $snapshotHash) {
            return null;
        }

        $version = ResumeVersion::create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'content_raw' => $resume->content_raw,
            'content_structured' => $resume->content_structured,
            'title' => $resume->title,
            'target_job' => $resume->target_job,
            'modules_snapshot' => $resume->buildModulesSnapshot(),
            'snapshot_hash' => $snapshotHash,
            'module_count' => $resume->modules()->count(),
            'source' => $source,
            'change_summary' => mb_substr($changeSummary, 0, 500) ?: $this->defaultSummary($source),
        ]);

        // 异步清理超限版本
        $this->pruneOldVersions($resume);

        return $version;
    }

    /**
     * 手动创建快照（用户主动保存标记）
     */
    public function createManualSnapshot(Resume $resume, string $label = '', string $changeSummary = ''): ResumeVersion
    {
        // 预加载 modules 关系
        $resume->loadMissing('modules');

        $snapshotHash = ResumeVersion::computeSnapshotHash($resume);

        // 即使内容相同，手动快照也强制保存（用户可能想标记一个重要节点）
        $version = ResumeVersion::create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'content_raw' => $resume->content_raw,
            'content_structured' => $resume->content_structured,
            'title' => $resume->title,
            'target_job' => $resume->target_job,
            'modules_snapshot' => $resume->buildModulesSnapshot(),
            'snapshot_hash' => $snapshotHash,
            'module_count' => $resume->modules()->count(),
            'source' => 'manual',
            'change_summary' => mb_substr($changeSummary, 0, 500) ?: '手动保存快照',
            'label' => $label ?: null,
        ]);

        $this->pruneOldVersions($resume);

        return $version;
    }

    /**
     * 恢复到指定版本
     *
     * @return bool 是否恢复成功
     */
    public function restoreToVersion(Resume $resume, ResumeVersion $targetVersion): bool
    {
        if ($targetVersion->resume_id !== $resume->id) {
            return false;
        }

        return DB::transaction(function () use ($resume, $targetVersion): bool {
            // 先保存当前版本作为恢复前快照
            $this->createSnapshot($resume, 'restore', '恢复版本前自动快照');

            // 恢复内容
            $resume->update([
                'content_raw' => $targetVersion->content_raw,
                'content_structured' => $targetVersion->content_structured,
                'title' => $targetVersion->title,
                'target_job' => $targetVersion->target_job,
            ]);

            // 恢复模块
            if ($targetVersion->modules_snapshot) {
                $resume->modules()->delete();
                foreach ($targetVersion->modules_snapshot as $idx => $mod) {
                    $resume->modules()->create([
                        'type' => $mod['type'] ?? 'summary',
                        'data' => $mod['data'] ?? [],
                        'sort_order' => $mod['sort_order'] ?? $idx,
                    ]);
                }
            }

            return true;
        });
    }

    /**
     * 清理超限的旧版本（保留手动标记版本优先）
     */
    public function pruneOldVersions(Resume $resume): int
    {
        $totalCount = ResumeVersion::where('resume_id', $resume->id)->count();

        if ($totalCount <= self::MAX_VERSIONS_PER_RESUME) {
            return 0;
        }

        $deleteCount = $totalCount - self::MAX_VERSIONS_PER_RESUME;

        // 获取要删除的版本ID：优先保留手动标记版本和最近版本
        $idsToKeep = ResumeVersion::where('resume_id', $resume->id)
            ->orderByRaw('CASE WHEN source = ? THEN 0 ELSE 1 END', ['manual'])
            ->orderByDesc('created_at')
            ->limit(self::MAX_VERSIONS_PER_RESUME)
            ->pluck('id')
            ->toArray();

        $deleted = ResumeVersion::where('resume_id', $resume->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        if ($deleted > 0) {
            Log::info("简历 #{$resume->id} 清理了 {$deleted} 个旧版本快照");
        }

        return $deleted;
    }

    /**
     * 获取两版本之间的变更统计
     */
    public function getDiffStats(ResumeVersion $a, ResumeVersion $b): array
    {
        $modulesA = collect($a->modules_snapshot ?? []);
        $modulesB = collect($b->modules_snapshot ?? []);

        $added = 0;
        $removed = 0;
        $changed = 0;

        $typesA = $modulesA->groupBy('type')->map(fn ($g) => $g->first());
        $typesB = $modulesB->groupBy('type')->map(fn ($g) => $g->first());

        $allTypes = $typesA->keys()->merge($typesB->keys())->unique();

        foreach ($allTypes as $type) {
            $modA = $typesA->get($type);
            $modB = $typesB->get($type);

            if ($modA && ! $modB) {
                $removed++;
            } elseif (! $modA && $modB) {
                $added++;
            } else {
                $dataA = json_encode($modA['data'] ?? [], JSON_UNESCAPED_UNICODE);
                $dataB = json_encode($modB['data'] ?? [], JSON_UNESCAPED_UNICODE);
                if ($dataA !== $dataB) {
                    $changed++;
                }
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'total_changes' => $added + $removed + $changed,
        ];
    }

    private function defaultSummary(string $source): string
    {
        return match ($source) {
            'auto_save' => '编辑器自动保存',
            'restore' => '恢复版本前自动快照',
            'optimize' => 'AI优化后自动保存',
            'manual' => '手动保存快照',
            default => '内容变更自动保存',
        };
    }
}
