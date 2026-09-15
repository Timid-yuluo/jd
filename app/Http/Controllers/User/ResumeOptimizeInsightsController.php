<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Models\Resume;
use App\Services\Resume\ResumeOptimizeGainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ResumeOptimizeInsightsController extends Controller
{
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly ResumeOptimizeGainService $gainService,
    ) {}

    public function strategyGain(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $limit = (int) $request->integer('limit', 20);
        $rows = $this->gainService->summarizeByStrategy($resume, (int) $request->user()->id, $limit);

        return $this->respondSuccessPayload([
            'items' => $rows,
            'strategy_version' => (string) config('resume_prompt_strategies.version', 'v1'),
        ]);
    }
}
