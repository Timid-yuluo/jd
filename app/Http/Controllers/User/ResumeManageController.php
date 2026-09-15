<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\HandlesResumeModuleDisplay;
use App\Http\Requests\User\ResumeStoreRequest;
use App\Http\Requests\User\ResumeUpdateRequest;
use App\Models\Resume;
use App\Models\ResumeVersion;
use App\Services\Resume\ResumeCreateQuotaService;
use App\Services\Resume\ResumeModuleDisplayService;
use App\Services\Resume\ResumeVersionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ResumeManageController extends Controller
{
    use HandlesResumeModuleDisplay;

    public function __construct(
        private readonly ResumeModuleDisplayService $resumeModuleDisplayService,
        private readonly ResumeCreateQuotaService $resumeCreateQuotaService,
        private readonly ResumeVersionService $resumeVersionService,
    ) {}

    public function create(Request $request): View
    {
        $resumeQuota = $this->resumeCreateQuotaService->resolve($request->user());
        $latestResume = Resume::where('user_id', $request->user()->id)
            ->with('modules')
            ->latest()
            ->first();

        return view('user.resumes.create', compact('resumeQuota', 'latestResume'));
    }

    public function store(ResumeStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['template'] = $data['template'] ?? 'classic';

        $modulesJson = (string) $request->input('modules', '');
        $modules = [];
        if ($modulesJson !== '') {
            $decoded = json_decode($modulesJson, true);
            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'modules' => '模块数据格式无效，请刷新页面后重试。',
                ]);
            }

            $moduleValidator = Validator::make(
                ['modules' => $decoded],
                [
                    'modules' => ['array', 'max:120'],
                    'modules.*.type' => ['required', 'string', 'max:30', Rule::in($this->allowedModuleTypes())],
                    'modules.*.data' => ['required', 'array'],
                    'modules.*.sort_order' => ['nullable', 'integer', 'min:0'],
                ],
                [
                    'modules.max' => '模块数量超出限制，请精简后重试。',
                    'modules.*.type.in' => '包含不支持的模块类型，请刷新页面后重试。',
                ]
            );

            $modules = (array) ($moduleValidator->validated()['modules'] ?? []);
        }

        $resume = DB::transaction(function () use ($data, $modules) {
            $resume = Resume::create($data);

            foreach ($modules as $idx => $module) {
                if (! is_array($module) || empty($module['type'])) {
                    continue;
                }
                $resume->modules()->create([
                    'type' => $module['type'],
                    'data' => is_array($module['data'] ?? null) ? $module['data'] : [],
                    'sort_order' => $module['sort_order'] ?? $idx,
                ]);
            }

            return $resume;
        });

        DashboardController::clearUserCache($request->user()->id);

        return redirect()->route('user.resumes.show', $resume)
            ->with('success', '简历创建成功。');
    }

    public function edit(Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        return redirect()->route('user.resumes.editor', $resume);
    }

    public function duplicate(Resume $resume): RedirectResponse
    {
        $this->authorize('view', $resume);

        $newResume = DB::transaction(function () use ($resume) {
            $newResume = $resume->replicate();
            $newResume->title = $resume->title . '（副本）';
            $newResume->ats_score = 0;
            $newResume->optimized_text = null;
            $newResume->highlights = null;
            $newResume->content_structured = null;
            $newResume->is_shareable = false;
            $newResume->share_token = null;
            $newResume->share_password = null;
            $newResume->created_at = now();
            $newResume->updated_at = now();
            $newResume->save();

            foreach ($resume->modules as $module) {
                $newModule = $module->replicate();
                $newModule->resume_id = $newResume->id;
                $newModule->save();
            }

            return $newResume;
        });

        DashboardController::clearUserCache($resume->user_id);

        return redirect()->route('user.resumes.show', $newResume)
            ->with('success', '简历已复制。');
    }

    public function update(ResumeUpdateRequest $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $originalContent = (string) $resume->content_raw;
        $originalTargetJob = (string) ($resume->target_job ?? '');
        $validated = $request->validated();

        if (! isset($validated['template'])) {
            $validated['template'] = $resume->template ?? 'classic';
        }

        $contentChanged = array_key_exists('content_raw', $validated)
            && (string) $validated['content_raw'] !== $originalContent;

        $resume->update($validated);

        if ($contentChanged) {
            $resume->refresh();
            $this->resumeVersionService->createSnapshot(
                $resume,
                'auto_save',
                mb_substr($request->input('change_summary', '手动编辑'), 0, 500)
            );
        }

        $targetJobChanged = array_key_exists('target_job', $validated)
            && (string) ($validated['target_job'] ?? '') !== $originalTargetJob;

        $redirect = redirect()->route('user.resumes.show', $resume)
            ->with('success', '简历已更新。');

        if (($contentChanged || $targetJobChanged) && ($resume->ats_score !== null || filled($resume->optimized_text))) {
            $redirect->with('warning', '你刚修改了简历内容或目标岗位，建议重新执行 AI 优化和 ATS 评分，以确保结果与当前版本一致。');
        }

        return $redirect;
    }

    public function destroy(Resume $resume): RedirectResponse
    {
        $this->authorize('delete', $resume);

        $resume->delete();
        DashboardController::clearUserCache($resume->user_id);

        return redirect()->route('user.resumes.index')
            ->with('success', '简历已移入回收站，30天内可恢复。');
    }

    public function trash(Request $request): View
    {
        $trashedResumes = Resume::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('deleted_at')
            ->paginate((int) config('ui.pagination.user_grid', 9))
            ->withQueryString();

        return view('user.resumes.trash', compact('trashedResumes'));
    }

    public function restore(string $id): RedirectResponse
    {
        $resume = Resume::onlyTrashed()->where('id', $id)->firstOrFail();

        $this->authorize('delete', $resume);

        $resume->restore();

        return redirect()->route('user.resumes.trash')
            ->with('success', '简历已恢复。');
    }

    public function forceDelete(string $id): RedirectResponse
    {
        $resume = Resume::onlyTrashed()->where('id', $id)->firstOrFail();

        $this->authorize('delete', $resume);

        $resume->forceDelete();

        return redirect()->route('user.resumes.trash')
            ->with('success', '简历已永久删除。');
    }

    public function toggleShare(Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        if ($resume->is_shareable) {
            $resume->update(['is_shareable' => false, 'share_token' => null]);
            $message = '分享链接已关闭。';
        } else {
            $resume->update([
                'is_shareable' => true,
                'share_token' => $resume->share_token ?: bin2hex(random_bytes(32)),
            ]);
            $message = '分享链接已开启。';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * 更新分享密码
     */
    public function updateSharePassword(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $data = $request->validate([
            'share_password' => ['nullable', 'string', 'max:32'],
        ]);

        $resume->update(['share_password' => $data['share_password'] ?: null]);

        $message = $data['share_password']
            ? '访问密码已设置。'
            : '密码保护已移除。';

        return redirect()->back()->with('success', $message);
    }

    /**
     * 更新简历关联的求职赛道
     */
    public function updateCareerTrack(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $data = $request->validate([
            'career_track_id' => ['nullable', 'integer', 'exists:career_tracks,id'],
        ]);

        $trackId = $data['career_track_id'] ?? null;
        $oldTrackId = $resume->career_track_id;

        // 验证赛道是否启用
        $trackName = '通用策略';
        if ($trackId) {
            $track = \App\Models\CareerTrack::active()->find($trackId);
            if (! $track) {
                return redirect()->back()->withErrors(['career_track_id' => '所选赛道不可用']);
            }
            $trackName = $track->name;
        }

        $resume->update(['career_track_id' => $trackId]);

        // 赛道变更时自动创建快照
        if ((int) $oldTrackId !== (int) $trackId) {
            $resume->refresh();
            $this->resumeVersionService->createSnapshot(
                $resume,
                'manual',
                "切换赛道：{$trackName}"
            );
        }

        $message = $trackId
            ? '赛道已设置，AI优化将使用专属策略。'
            : '已取消赛道，将使用通用优化策略。';

        return redirect()->back()->with('success', $message);
    }

    public function versions(Resume $resume): View
    {
        $this->authorize('view', $resume);

        $versions = ResumeVersion::where('resume_id', $resume->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('user.resumes.versions', compact('resume', 'versions'));
    }

    public function restoreVersion(Resume $resume, ResumeVersion $version): RedirectResponse
    {
        $this->authorize('update', $resume);

        if ($version->resume_id !== $resume->id) {
            abort(403);
        }

        $this->resumeVersionService->restoreToVersion($resume, $version);

        return redirect()->route('user.resumes.show', $resume)
            ->with('success', '已恢复到所选版本。');
    }

    public function updateVersionLabel(Request $request, Resume $resume, ResumeVersion $version): RedirectResponse
    {
        $this->authorize('update', $resume);

        if ($version->resume_id !== $resume->id) {
            abort(403);
        }

        $label = mb_substr(trim((string) $request->input('label', '')), 0, 100);
        $version->update(['label' => $label ?: null]);

        return redirect()->route('user.resumes.versions', $resume)->with('success', '标签已更新。');
    }

    /**
     * 手动创建版本快照
     */
    public function createSnapshot(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);

        $version = $this->resumeVersionService->createManualSnapshot(
            $resume,
            $data['label'] ?? '',
            $data['change_summary'] ?? ''
        );

        $message = $version->label
            ? "快照「{$version->label}」已保存。"
            : '版本快照已保存。';

        return redirect()->back()->with('success', $message);
    }

    /**
     * 版本对比：选择两个版本进行可视化 diff
     */
    public function compareVersions(Request $request, Resume $resume): View
    {
        $this->authorize('view', $resume);

        $versionAId = (int) $request->query('a');
        $versionBId = (int) $request->query('b');

        $allVersions = ResumeVersion::where('resume_id', $resume->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'created_at', 'label', 'title', 'target_job', 'change_summary', 'source', 'module_count']);

        $versionA = null;
        $versionB = null;

        if ($versionAId && $versionBId) {
            $versionA = ResumeVersion::where('resume_id', $resume->id)->find($versionAId);
            $versionB = ResumeVersion::where('resume_id', $resume->id)->find($versionBId);
        }

        // 默认选中最新两个版本
        if (! $versionA && $allVersions->count() >= 2) {
            $versionA = ResumeVersion::where('resume_id', $resume->id)->find($allVersions[0]->id);
            $versionB = ResumeVersion::where('resume_id', $resume->id)->find($allVersions[1]->id);
        }

        $diff = null;
        $diffStats = null;
        if ($versionA && $versionB) {
            $diff = $this->buildVersionDiff($versionA, $versionB);
            $diffStats = $this->resumeVersionService->getDiffStats($versionA, $versionB);
        }

        return view('user.resumes.versions-compare', compact('resume', 'allVersions', 'versionA', 'versionB', 'diff', 'diffStats'));
    }

    /**
     * 构建两个版本之间的差异对比数据
     */
    private function buildVersionDiff(ResumeVersion $a, ResumeVersion $b): array
    {
        $diff = [
            'meta' => [],
            'modules' => [],
        ];

        // 元信息对比
        $metaFields = ['title', 'target_job'];
        foreach ($metaFields as $field) {
            $oldVal = $a->$field ?? '';
            $newVal = $b->$field ?? '';
            if ($oldVal !== $newVal) {
                $diff['meta'][] = [
                    'field' => $field,
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }

        // 模块对比
        $modulesA = collect($a->modules_snapshot ?? []);
        $modulesB = collect($b->modules_snapshot ?? []);

        $typesA = $modulesA->groupBy('type')->map(fn ($g) => $g->first());
        $typesB = $modulesB->groupBy('type')->map(fn ($g) => $g->first());

        $allTypes = $typesA->keys()->merge($typesB->keys())->unique();

        $typeLabels = [
            'summary' => '个人简介',
            'work_experience' => '工作经历',
            'education' => '教育背景',
            'skills' => '专业技能',
            'projects' => '项目经验',
            'certifications' => '证书资质',
            'languages' => '语言能力',
            'awards' => '获奖情况',
            'interests' => '兴趣爱好',
            'custom' => '自定义模块',
        ];

        foreach ($allTypes as $type) {
            $modA = $typesA->get($type);
            $modB = $typesB->get($type);
            $label = $typeLabels[$type] ?? $type;

            if ($modA && ! $modB) {
                $diff['modules'][] = ['type' => $type, 'label' => $label, 'status' => 'removed', 'old' => $modA, 'new' => null];
            } elseif (! $modA && $modB) {
                $diff['modules'][] = ['type' => $type, 'label' => $label, 'status' => 'added', 'old' => null, 'new' => $modB];
            } else {
                $dataA = json_encode($modA['data'] ?? [], JSON_UNESCAPED_UNICODE);
                $dataB = json_encode($modB['data'] ?? [], JSON_UNESCAPED_UNICODE);
                if ($dataA !== $dataB) {
                    $diff['modules'][] = ['type' => $type, 'label' => $label, 'status' => 'changed', 'old' => $modA, 'new' => $modB];
                } else {
                    $diff['modules'][] = ['type' => $type, 'label' => $label, 'status' => 'unchanged', 'old' => $modA, 'new' => $modB];
                }
            }
        }

        return $diff;
    }
}
