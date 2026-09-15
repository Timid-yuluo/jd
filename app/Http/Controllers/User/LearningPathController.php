<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LearningPathAnalyzeRequest;
use App\Models\SkillLearningPath;
use App\Services\SkillLearningPathService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 学习路径控制器
 *
 * 关联文档：docs/features-development-plan.md §4.4
 */
final class LearningPathController extends Controller
{
    public function __construct(
        private readonly SkillLearningPathService $pathService,
    ) {}

    /**
     * 学习路径入口（分析表单）
     */
    public function index(Request $request): View
    {
        $activePath = SkillLearningPath::where('user_id', $request->user()->id)
            ->active()
            ->latest()
            ->first();

        $skillCount = $request->user()->skillAssessments()->count();

        return view('user.skills.learn.index', compact('activePath', 'skillCount'));
    }

    /**
     * 执行差距分析并生成学习路径
     */
    public function analyze(LearningPathAnalyzeRequest $request): RedirectResponse
    {
        $skillCount = $request->user()->skillAssessments()->count();
        if ($skillCount === 0) {
            return redirect()
                ->route('user.skills.index')
                ->with('error', '请先添加至少一项技能自评');
        }

        $path = $this->pathService->analyzeAndCreatePath($request->user(), $request->validated());

        if (empty($path->ai_path)) {
            return redirect()
                ->route('user.skills.learn.index')
                ->with('error', 'AI 分析失败，请稍后重试')
                ->withInput();
        }

        return redirect()
            ->route('user.skills.learn.show', $path)
            ->with('success', '学习路径已生成');
    }

    /**
     * 学习路径详情
     */
    public function show(Request $request, SkillLearningPath $path): View
    {
        $this->authorizePath($request->user()->id, $path);

        return view('user.skills.learn.path', compact('path'));
    }

    /**
     * 历史路径
     */
    public function history(Request $request): View
    {
        $paths = SkillLearningPath::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('user.skills.learn.history', compact('paths'));
    }

    /**
     * 鉴权
     */
    private function authorizePath(int $userId, SkillLearningPath $path): void
    {
        if ($path->user_id !== $userId) {
            abort(403, '无权查看此学习路径');
        }
    }
}
