<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AssessmentSubmitRequest;
use App\Models\CareerAssessment;
use App\Services\CareerAssessmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 职业测评控制器
 *
 * 关联文档：docs/features-development-plan.md §3.5
 */
final class AssessmentController extends Controller
{
    public function __construct(
        private readonly CareerAssessmentService $assessmentService,
    ) {}

    /**
     * 测评中心首页
     */
    public function index(): View
    {
        $types = $this->assessmentService->getAvailableTypes();

        return view('user.assessments.index', compact('types'));
    }

    /**
     * 开始测评
     */
    public function start(Request $request, string $type): View|RedirectResponse
    {
        if (!$this->assessmentService->isValidType($type)) {
            return redirect()
                ->route('user.assessments.index')
                ->with('error', '不支持的测评类型');
        }

        $config = $this->assessmentService->getConfig($type);

        return view('user.assessments.test', compact('type', 'config'));
    }

    /**
     * 获取题目（JSON 分页）
     */
    public function questions(Request $request, string $type): JsonResponse
    {
        if (!$this->assessmentService->isValidType($type)) {
            return response()->json(['error' => '不支持的测评类型'], 404);
        }

        $questions = $this->assessmentService->getQuestions($type);
        $perPage = 5;
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($questions, $offset, $perPage);

        return response()->json([
            'data' => $paged,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => count($questions),
            'last_page' => (int) ceil(count($questions) / $perPage),
            'has_more' => $offset + $perPage < count($questions),
        ]);
    }

    /**
     * 提交答案，计算结果，调用 AI
     */
    public function submit(AssessmentSubmitRequest $request, string $type): RedirectResponse
    {
        if (!$this->assessmentService->isValidType($type)) {
            return redirect()
                ->route('user.assessments.index')
                ->with('error', '不支持的测评类型');
        }

        $assessment = $this->assessmentService->submitAssessment(
            $request->user(),
            $type,
            $request->input('answers'),
        );

        return redirect()
            ->route('user.assessments.result', $assessment)
            ->with('success', '测评完成，已生成报告');
    }

    /**
     * 查看测评报告
     */
    public function result(Request $request, CareerAssessment $assessment): View
    {
        $this->authorizeAssessment($request->user()->id, $assessment);

        $config = $this->assessmentService->getConfig($assessment->test_type);

        return view('user.assessments.result', compact('assessment', 'config'));
    }

    /**
     * 历史测评记录
     */
    public function history(Request $request): View
    {
        $assessments = CareerAssessment::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('user.assessments.history', compact('assessments'));
    }

    /**
     * 鉴权：确保测评记录属于当前用户
     */
    private function authorizeAssessment(int $userId, CareerAssessment $assessment): void
    {
        if ($assessment->user_id !== $userId) {
            abort(403, '无权查看此测评记录');
        }
    }
}
