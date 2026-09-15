<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\HandlesResumeControllerLogging;
use App\Http\Controllers\User\Traits\HandlesResumeFileImport;
use App\Http\Controllers\User\Traits\HandlesResumeModuleDisplay;
use App\Http\Controllers\User\Traits\InteractsWithAsyncResponses;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Models\Resume;
use App\Models\ResumeModule;
use App\Services\Api\V1\ResumeService;
use App\Services\Resume\ResumeVersionService;
use App\Services\Resume\ResumeFileImportService;
use App\Services\Resume\ResumeModuleDisplayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ResumeEditorController extends Controller
{
    use HandlesResumeControllerLogging;
    use HandlesResumeFileImport;
    use HandlesResumeModuleDisplay;
    use InteractsWithAsyncResponses;
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeService $resumeService,
        private readonly ResumeFileImportService $resumeFileImportService,
        private readonly ResumeModuleDisplayService $resumeModuleDisplayService,
    ) {}

    public function editor(Request $request, Resume $resume): View
    {
        $this->authorize('update', $resume);

        $resume->load('modules');

        $moduleTypes = [
            'personal' => '个人信息',
            'objective' => '求职意向',
            'education' => '教育经历',
            'experience' => '实习经历',
            'project' => '项目经验',
            'skill' => '技能证书',
            'certificate' => '获奖情况',
            'summary' => '自我评价',
        ];

        $defaultModules = $this->buildShowModules($resume);
        $theme = $resume->theme ?? 'blue';
        $fontSettings = (is_array($resume->content_structured ?? null) && is_array($resume->content_structured['font_settings'] ?? null))
            ? $resume->content_structured['font_settings']
            : [];
        $templateUndoAvailable = is_array($resume->content_structured ?? null)
            && is_array($resume->content_structured['template_apply_undo'] ?? null);
        $plan = $request->user()->currentPlan();
        $advancedModelEnabled = (bool) $plan?->hasFeature('advanced_model');
        $priorityQueueEnabled = (bool) $plan?->hasFeature('priority_queue');

        return view('user.resumes.editor', compact(
            'resume',
            'moduleTypes',
            'defaultModules',
            'theme',
            'fontSettings',
            'templateUndoAvailable',
            'advancedModelEnabled',
            'priorityQueueEnabled'
        ));
    }

    public function saveModules(Request $request, Resume $resume): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $resume);

        if (is_string($request->input('modules'))) {
            $decoded = json_decode($request->input('modules'), true);
            if (is_array($decoded)) {
                $request->merge(['modules' => $decoded]);
            }
        }

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.id' => ['nullable', 'integer', 'min:1'],
            'modules.*.type' => ['required', 'string', 'max:30', Rule::in($this->allowedModuleTypes())],
            'modules.*.data' => ['required', 'array'],
            'modules.*.sort_order' => ['required', 'integer', 'min:0'],
            'template' => ['nullable', 'string', 'in:classic,modern,minimal,timeline,creative,elegant'],
            'theme' => ['nullable', 'string', 'in:blue,coral,green,purple,orange'],
            'after_save_action' => ['nullable', 'string', 'in:save,optimize'],
            'font_settings' => ['nullable', 'json'],
        ]);

        $lockKey = sprintf('resume:save-modules:%d:user:%d', (int) $resume->id, (int) $request->user()->id);
        $lock = Cache::lock($lockKey, 10);
        if (! $lock->get()) {
            if ($this->expectsAsyncResponse($request)) {
                return $this->fail('正在保存中，请稍后重试。', 429);
            }

            return redirect()->route('user.resumes.editor', $resume)
                ->with('warning', '上一次保存尚未完成，请稍后重试。');
        }

        try {
            DB::transaction(function () use ($resume, $validated): void {
                $existingModules = $resume->modules()
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $keptModuleIds = [];

                foreach ($validated['modules'] as $index => $moduleData) {
                    $payload = [
                        'type' => $moduleData['type'],
                        'data' => $moduleData['data'],
                        'sort_order' => $index,
                    ];

                    $moduleId = isset($moduleData['id']) ? (int) $moduleData['id'] : 0;
                    if ($moduleId > 0) {
                        /** @var ResumeModule|null $existingModule */
                        $existingModule = $existingModules->get($moduleId);
                        if (! $existingModule) {
                            throw ValidationException::withMessages([
                                'modules' => '检测到无效的模块标识，请刷新页面后重试。',
                            ]);
                        }

                        $existingModule->fill($payload);
                        if ($existingModule->isDirty()) {
                            $existingModule->save();
                        }
                        $keptModuleIds[] = $existingModule->id;

                        continue;
                    }

                    $createdModule = $resume->modules()->create($payload);
                    $keptModuleIds[] = $createdModule->id;
                }

                $resume->modules()
                    ->when(! empty($keptModuleIds), fn ($query) => $query->whereNotIn('id', $keptModuleIds))
                    ->when(empty($keptModuleIds), fn ($query) => $query)
                    ->delete();
            });

            $updateData = [];
            if (isset($validated['template'])) {
                $updateData['template'] = $validated['template'];
            }
            if (isset($validated['theme'])) {
                $updateData['theme'] = $validated['theme'];
            }
            if (isset($validated['font_settings'])) {
                $fontSettings = json_decode((string) $validated['font_settings'], true);
                if (is_array($fontSettings)) {
                    $contentStructured = is_array($resume->content_structured) ? $resume->content_structured : [];
                    $contentStructured['font_settings'] = $fontSettings;
                    $updateData['content_structured'] = $contentStructured;
                }
            }
            $targetJobFromModules = $this->extractTargetJobFromModules($validated['modules']);
            if ($targetJobFromModules !== null) {
                $updateData['target_job'] = $targetJobFromModules;
            }

            $rawText = $this->modulesToRawText($resume->fresh()->modules()->orderBy('sort_order')->get());
            $updateData['content_raw'] = $rawText;

            // 内容有变化时，先保存旧版本快照（在 update 之前，确保快照数据一致）
            $contentChanged = ! empty($updateData) && isset($updateData['content_raw']) && $updateData['content_raw'] !== (string) $resume->content_raw;

            if (! empty($updateData)) {
                $resume->update($updateData);
            }

            // 更新后再创建快照，此时 resume 属性已刷新，快照保存的是更新后的完整数据
            if ($contentChanged) {
                $resume->refresh();
                app(ResumeVersionService::class)->createSnapshot($resume, 'auto_save', '编辑器自动保存');
            }

            if ($this->expectsAsyncResponse($request)) {
                return $this->respondSuccessPayload([
                    'message' => '保存成功',
                    'modules' => $resume->fresh()->modules()->orderBy('sort_order')->get()->toArray(),
                ]);
            }

            $afterSaveAction = $validated['after_save_action'] ?? 'save';
            if ($afterSaveAction === 'optimize') {
                try {
                    $targetJob = $targetJobFromModules !== null && $targetJobFromModules !== ''
                        ? $targetJobFromModules
                        : (string) ($resume->target_job ?? '');
                    $this->resumeService->optimize($resume->fresh(), ['target_job' => $targetJob]);

                    return redirect()->route('user.resumes.editor', $resume)
                        ->with('optimized_just_done', true)
                        ->with('success', $targetJob !== ''
                            ? "已保存并按目标岗位「{$targetJob}」完成 AI 优化。"
                            : '已保存并完成 AI 优化。');
                } catch (\Throwable $exception) {
                    $this->logUserFacingException('resume_editor_save_optimize_failed', $exception, [
                        'resume_id' => $resume->id,
                        'user_id' => $request->user()?->id,
                    ]);

                    return redirect()->route('user.resumes.editor', $resume)
                        ->with('warning', '简历已保存，但本次 AI 优化暂未完成，请稍后重试。');
                }
            }

            return redirect()->route('user.resumes.show', $resume)
                ->with('success', '简历模块化内容已保存。');
        } finally {
            $lock->release();
        }
    }

    public function uploadAvatar(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.config('ui.upload.image_max_kb', 2048)],
        ]);

        $file = $validated['image'];
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $filename = $resume->id.'_'.Str::random(16).'.'.$ext;
        $path = $file->storeAs('avatars', $filename, 'private');

        $url = URL::temporarySignedRoute(
            'private.avatar',
            now()->addHours(2),
            ['filename' => $filename]
        );

        return $this->respondSuccessPayload(['url' => $url, 'path' => $path]);
    }
}
