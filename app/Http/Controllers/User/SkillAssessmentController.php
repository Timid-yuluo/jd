<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SkillAssessmentRequest;
use App\Models\SkillAssessment;
use App\Services\SkillLearningPathService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 技能自评控制器
 *
 * 关联文档：docs/features-development-plan.md §4.4
 */
final class SkillAssessmentController extends Controller
{
    public function __construct(
        private readonly SkillLearningPathService $pathService,
    ) {}

    /**
     * 技能清单 + 自评表单
     */
    public function index(Request $request): View
    {
        $skills = SkillAssessment::where('user_id', $request->user()->id)
            ->orderBy('skill_category')
            ->orderByDesc('proficiency')
            ->get();

        $hardSkills = $skills->where('skill_category', SkillAssessment::CATEGORY_HARD);
        $softSkills = $skills->where('skill_category', SkillAssessment::CATEGORY_SOFT);

        return view('user.skills.index', compact('skills', 'hardSkills', 'softSkills'));
    }

    /**
     * 技能雷达图数据
     */
    public function radar(Request $request): View|JsonResponse
    {
        $data = $this->pathService->getRadarData($request->user());

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('user.skills.radar', $data);
    }

    /**
     * 新增技能
     */
    public function store(SkillAssessmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        // 检查是否已存在同名技能
        $exists = SkillAssessment::where('user_id', $validated['user_id'])
            ->where('skill_name', $validated['skill_name'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('user.skills.index')
                ->with('error', '该技能已存在，请直接编辑')
                ->withInput();
        }

        SkillAssessment::create($validated);

        return redirect()
            ->route('user.skills.index')
            ->with('success', '技能已添加');
    }

    /**
     * 更新技能
     */
    public function update(SkillAssessmentRequest $request, SkillAssessment $skill): RedirectResponse
    {
        $this->authorizeSkill($request->user()->id, $skill);

        $skill->update($request->validated());

        return redirect()
            ->route('user.skills.index')
            ->with('success', '技能已更新');
    }

    /**
     * 删除技能
     */
    public function destroy(Request $request, SkillAssessment $skill): RedirectResponse
    {
        $this->authorizeSkill($request->user()->id, $skill);

        $skill->delete();

        return redirect()
            ->route('user.skills.index')
            ->with('success', '技能已删除');
    }

    /**
     * 鉴权
     */
    private function authorizeSkill(int $userId, SkillAssessment $skill): void
    {
        if ($skill->user_id !== $userId) {
            abort(403, '无权操作此技能');
        }
    }
}
