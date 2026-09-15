<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SalaryNegotiationRequest;
use App\Http\Requests\User\SalaryReportRequest;
use App\Models\SalaryNegotiationSession;
use App\Models\SalarySurvey;
use App\Services\SalaryNegotiationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 薪资谈判助手控制器
 *
 * 关联文档：docs/features-development-plan.md §2.5
 */
final class SalaryController extends Controller
{
    public function __construct(
        private readonly SalaryNegotiationService $negotiationService,
    ) {}

    /**
     * 薪资查询首页
     */
    public function index(Request $request): View
    {
        $jobTitle = $request->string('job_title')->toString();
        $city = $request->string('city')->toString();
        $experienceLevel = $request->string('experience_level')->toString();

        $stats = null;
        if ($jobTitle !== '') {
            $stats = $this->negotiationService->getSalaryStats($jobTitle, $city ?: null, $experienceLevel ?: null);
        }

        // 热门查询岗位
        $hotJobs = SalarySurvey::query()
            ->select('job_title')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('job_title')
            ->orderByDesc('count')
            ->limit(8)
            ->pluck('count', 'job_title');

        return view('user.salary.index', compact('stats', 'jobTitle', 'city', 'experienceLevel', 'hotJobs'));
    }

    /**
     * 图表数据 JSON
     */
    public function chartData(Request $request): JsonResponse
    {
        $jobTitle = $request->string('job_title')->toString();
        if ($jobTitle === '') {
            return response()->json(['error' => '岗位名称必填'], 422);
        }

        $stats = $this->negotiationService->getSalaryStats(
            $jobTitle,
            $request->string('city')->toString() ?: null,
            $request->string('experience_level')->toString() ?: null,
        );

        return response()->json($stats);
    }

    /**
     * 用户上报薪资数据
     */
    public function report(SalaryReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $validated['source'] = 'user_report';
        $validated['source_hash'] = hash('sha256', implode('|', [
            $validated['job_title'],
            $validated['company'] ?? '',
            $validated['salary_min'],
            $validated['salary_max'],
            $request->user()->id,
        ]));

        SalarySurvey::create($validated);

        return redirect()
            ->route('user.salary.index', ['job_title' => $validated['job_title']])
            ->with('success', '薪资数据已上报，感谢你的贡献！');
    }

    /**
     * 谈判助手输入页
     */
    public function negotiate(Request $request): View
    {
        $resumes = $request->user()->resumes()
            ->select(['id', 'title', 'target_job_title'])
            ->latest()
            ->limit(5)
            ->get();

        return view('user.salary.negotiate', compact('resumes'));
    }

    /**
     * 提交谈判参数，调用 AI，存储结果
     */
    public function startNegotiation(SalaryNegotiationRequest $request): RedirectResponse
    {
        $session = $this->negotiationService->startNegotiation($request->user(), $request->validated());

        if ($session->ai_strategy === null) {
            return redirect()
                ->route('user.salary.negotiate')
                ->with('error', 'AI 分析失败，请稍后重试')
                ->withInput();
        }

        return redirect()
            ->route('user.salary.negotiate.result', $session)
            ->with('success', '谈判分析已生成');
    }

    /**
     * 查看某次谈判结果详情
     */
    public function negotiationResult(Request $request, SalaryNegotiationSession $session): View
    {
        $this->authorizeSession($request->user()->id, $session);

        $session->load('user');

        return view('user.salary.negotiate-result', compact('session'));
    }

    /**
     * 谈判历史列表
     */
    public function negotiationHistory(Request $request): View
    {
        $sessions = SalaryNegotiationSession::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('user.salary.negotiate-history', compact('sessions'));
    }

    /**
     * 鉴权：确保会话属于当前用户
     */
    private function authorizeSession(int $userId, SalaryNegotiationSession $session): void
    {
        if ($session->user_id !== $userId) {
            abort(403, '无权查看此谈判记录');
        }
    }
}
