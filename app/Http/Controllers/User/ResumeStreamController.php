<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Models\Resume;
use App\Services\Resume\ResumeOptimizeStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 简历流式优化控制器 — 流式输出委托 ResumeOptimizeStreamService
 */
final class ResumeStreamController extends Controller
{
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeOptimizeStreamService $streamService,
    ) {}

    public function prewarm(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        return $this->respondSuccessPayload([
            'prewarm' => true,
        ]);
    }

    public function optimizeStream(Request $request, Resume $resume): StreamedResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'prewarm' => ['nullable', 'boolean'],
            'target_job' => ['nullable', 'string', 'max:255'],
            'target_company' => ['nullable', 'string', 'max:120'],
            'target_job_title' => ['nullable', 'string', 'max:120'],
            'target_job_description' => ['nullable', 'string', 'max:5000'],
            'prompt_strategy_template' => ['nullable', 'string', 'max:80'],
            'optimize_goals' => ['nullable', 'array'],
            'optimize_goals.*' => ['string', 'in:ats_keywords,structure,quantified,skill_match,language,highlights,tailor_job,concise,authenticity,readability,industry_fit,career_pivot,leadership,i18n_expression,project_impact,tech_depth,cross_cultural,certification,innovation,data_driven'],
            'optimize_mode' => ['nullable', 'string', 'in:quick,balanced,deep'],
            'focus_keywords' => ['nullable', 'array'],
            'focus_keywords.*' => ['string', 'max:64'],
            'resume_profile' => ['nullable', 'array'],
            'resume_profile.keyword_missing' => ['nullable', 'array'],
            'resume_profile.keyword_missing.*' => ['string', 'max:64'],
            'resume_profile.weak_modules' => ['nullable', 'array'],
            'resume_profile.weak_modules.*' => ['string', 'max:64'],
            'modules' => ['nullable', 'array'],
        ]);

        $allowCreditOverride = $request->attributes->get('quota_source') === 'credit'
            && $request->attributes->get('quota_key') === 'optimize_full';

        Log::info('resume_optimize_stream_access_resolved', [
            'resume_id' => $resume->id,
            'user_id' => $request->user()?->id,
            'requested_mode' => (string) ($validated['optimize_mode'] ?? 'balanced'),
            'requested_template' => (string) ($validated['prompt_strategy_template'] ?? 'general'),
            'quota_source' => $request->attributes->get('quota_source'),
            'quota_key' => $request->attributes->get('quota_key'),
            'credit_id' => $request->attributes->get('credit_id'),
            'allow_credit_override' => $allowCreditOverride,
        ]);

        return $this->streamService->stream($resume, $validated, $allowCreditOverride, function () use ($request): void {
            $this->markQuotaConsumptionSuccess($request);
        }, $request->headers->get('Last-Event-ID'));
    }
}
