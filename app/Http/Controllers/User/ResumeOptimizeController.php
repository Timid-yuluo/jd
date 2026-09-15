<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Exceptions\AiServiceUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\HandlesResumeControllerLogging;
use App\Http\Controllers\User\Traits\HandlesResumeModuleDisplay;
use App\Http\Controllers\User\Traits\InteractsWithAsyncResponses;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Http\Requests\User\ResumeOptimizeRequest;
use App\Models\Resume;
use App\Services\Api\V1\ResumeService;
use App\Services\Resume\ResumeModuleDisplayService;
use App\Services\Resume\ResumeModuleSortSuggestionService;
use App\Services\Resume\ResumeOptimizedContentApplyService;
use App\Services\Resume\ResumeOptimizeUiService;
use App\Services\Resume\ResumeSectionAiService;
use App\Services\Resume\ResumeTranslationService;
use App\Services\Resume\Support\ResumeOptimizationPayloadSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ResumeOptimizeController extends Controller
{
    use HandlesResumeControllerLogging;
    use HandlesResumeModuleDisplay;
    use InteractsWithAsyncResponses;
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeService $resumeService,
        private readonly ResumeOptimizedContentApplyService $resumeOptimizedContentApplyService,
        private readonly ResumeSectionAiService $resumeSectionAiService,
        private readonly ResumeOptimizationPayloadSanitizer $resumeOptimizationPayloadSanitizer,
        private readonly ResumeOptimizeUiService $resumeOptimizeUiService,
        private readonly ResumeModuleDisplayService $resumeModuleDisplayService,
        private readonly ResumeModuleSortSuggestionService $moduleSortSuggestionService,
        private readonly ResumeTranslationService $translationService,
    ) {}

    public function optimize(ResumeOptimizeRequest $request, Resume $resume): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $resume);

        try {
            $payload = $request->optimizePayload([
                'target_job' => $resume->target_job,
                'target_company' => $resume->target_company,
                'target_job_title' => $resume->target_job_title,
                'target_job_description' => $resume->target_job_description,
            ]);
            $payload['optimize_goals'] = $this->resumeOptimizationPayloadSanitizer->sanitizeOptimizeGoals($payload['optimize_goals'] ?? null);

            $this->resumeService->optimize($resume, $payload);

            $message = $this->resumeOptimizeUiService->buildOptimizeSuccessMessage((string) $payload['target_job']);
            $this->markQuotaConsumptionSuccess($request);

            if ($this->expectsAsyncResponse($request)) {
                return $this->respondSuccessPayload(['message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            throw new AiServiceUnavailableException(
                '简历优化暂时不可用，请稍后重试。',
                'resume_optimize_failed',
                ['resume_id' => $resume->id, 'user_id' => $request->user()?->id],
            );
        }
    }

    public function optimizeView(Request $request, Resume $resume): View
    {
        $this->authorize('view', $resume);

        $viewData = $this->resumeOptimizeUiService->resolveViewData($request->user());
        $advancedModelEnabled = $viewData['advancedModelEnabled'];
        $priorityQueueEnabled = $viewData['priorityQueueEnabled'];

        return view('user.resumes.optimize', compact('resume', 'advancedModelEnabled', 'priorityQueueEnabled'));
    }

    public function applyOptimized(Request $request, Resume $resume): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'redirect_to' => ['nullable', 'string', 'in:show,edit,editor,optimize'],
            'selected_module_types' => ['nullable', 'array'],
            'selected_module_types.*' => ['string', Rule::in($this->allowedModuleTypes())],
        ]);

        if (! filled($resume->optimized_text)) {
            if ($this->expectsAsyncResponse($request)) {
                return $this->fail('当前没有可应用的优化结果。');
            }

            return redirect()->back()->with('error', '当前没有可应用的优化结果。');
        }

        $selectedModuleTypes = collect($validated['selected_module_types'] ?? [])
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->map(fn (string $type) => trim($type))
            ->values()
            ->unique()
            ->all();
        $result = $this->resumeOptimizedContentApplyService->apply($resume, $selectedModuleTypes);
        $isPartialApply = (bool) ($result['is_partial_apply'] ?? false);

        $routeName = $this->resumeOptimizeUiService->resolveApplyRedirectRoute($validated['redirect_to'] ?? 'edit');
        $message = $this->resumeOptimizeUiService->buildApplySuccessMessage($isPartialApply);

        if ($this->expectsAsyncResponse($request)) {
            return $this->respondSuccessPayload(['message' => $message]);
        }

        return redirect()
            ->route($routeName, $resume)
            ->with('success', $message);
    }

    public function optimizeSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section_type' => ['required', 'string', 'in:personal,education,experience,project,skill,certificate,objective,summary'],
            'content' => ['required', 'string', 'max:5000'],
            'target_job' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $sectionType = (string) $validated['section_type'];
            $result = $this->resumeSectionAiService->optimizeSection(
                $sectionType,
                (string) $validated['content'],
                (string) ($validated['target_job'] ?? '')
            );
            $this->markQuotaConsumptionSuccess($request);

            return $this->respondSuccessPayload([
                'optimized_text' => $result['optimized_text'] ?? '',
                'suggestions' => is_array($result['suggestions'] ?? null) ? $result['suggestions'] : [],
                'score_before' => (int) ($result['score_before'] ?? 0),
                'score_after' => (int) ($result['score_after'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            throw new AiServiceUnavailableException(
                'AI 优化暂时不可用，请稍后重试。',
                'resume_optimize_section_failed',
                ['user_id' => $request->user()?->id, 'section_type' => $validated['section_type'] ?? null],
            );
        }
    }

    public function generateSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section_type' => ['required', 'string', 'in:personal,education,experience,project,skill,certificate,objective,summary'],
            'brief' => ['required', 'string', 'max:1000'],
            'target_job' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $sectionType = (string) $validated['section_type'];
            $result = $this->resumeSectionAiService->generateSection(
                $sectionType,
                (string) $validated['brief'],
                (string) ($validated['target_job'] ?? '')
            );
            $this->markQuotaConsumptionSuccess($request);

            return $this->respondSuccessPayload([
                'generated_text' => $result['generated_text'] ?? '',
                'suggestions' => is_array($result['suggestions'] ?? null) ? $result['suggestions'] : [],
            ]);
        } catch (\Throwable $e) {
            throw new AiServiceUnavailableException(
                'AI 生成功能暂时不可用，请稍后重试。',
                'resume_generate_section_failed',
                ['user_id' => $request->user()?->id, 'section_type' => $validated['section_type'] ?? null],
            );
        }
    }

    /**
     * AI 模块排序建议
     */
    public function suggestModuleOrder(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('view', $resume);

        try {
            $result = $this->moduleSortSuggestionService->suggestOrder($resume);

            return $this->respondSuccessPayload($result);
        } catch (\Throwable $e) {
            throw new AiServiceUnavailableException(
                '排序建议暂时不可用，请稍后重试。',
                'resume_module_sort_suggestion_failed',
                ['resume_id' => $resume->id, 'user_id' => $request->user()?->id],
            );
        }
    }

    /**
     * 一键翻译简历
     */
    public function translate(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'direction' => ['required', 'string', 'in:zh_to_en,en_to_zh'],
        ]);

        try {
            $result = $this->translationService->translate($resume, (string) $validated['direction']);
            $this->markQuotaConsumptionSuccess($request);

            return $this->respondSuccessPayload($result);
        } catch (\Throwable $e) {
            throw new AiServiceUnavailableException(
                '翻译功能暂时不可用，请稍后重试。',
                'resume_translate_failed',
                ['resume_id' => $resume->id, 'user_id' => $request->user()?->id, 'direction' => $validated['direction'] ?? null],
            );
        }
    }
}
