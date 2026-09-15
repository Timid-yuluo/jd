<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConfigHistory;
use App\Services\Admin\AiBenchmarkService;
use App\Services\Admin\AiConfigDashboardService;
use App\Services\Admin\AiConfigUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AiConfigController extends Controller
{
    public function __construct(
        private readonly AiConfigDashboardService $dashboardService,
        private readonly AiConfigUpdateService $updateService,
        private readonly AiBenchmarkService $benchmarkService,
    ) {}

    public function index(): View
    {
        $config = config('ai');
        $currentProvider = config('ai.default');
        $optimizePrimaryChannel = (string) config('resume.editor.optimize_primary_channel', 'session');

        $providers = [];
        foreach ($config['providers'] ?? [] as $key => $provider) {
            $providers[$key] = [
                'key' => $key,
                'name' => $provider['name'] ?? $key,
                'enabled' => (bool) ($provider['enabled'] ?? true),
                'model' => $provider['model'] ?? '-',
                'base_url' => $provider['base_url'] ?? '-',
                'has_api_key' => ! empty($provider['api_key']),
                'is_active' => $key === $currentProvider,
            ];
        }

        $monitor = $this->dashboardService->buildMonitor();
        $costEstimate = $this->dashboardService->calculateCostEstimate();

        $histories = AiConfigHistory::query()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit((int) config('ui.limit.ai_config_history', 5))
            ->get();

        $volcanoEndpointOptions = $this->collectVolcanoEndpointOptions(
            $providers['volcano']['model'] ?? null,
            AiConfigHistory::query()
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
        );

        return view('admin.ai-config.index', compact(
            'providers',
            'currentProvider',
            'optimizePrimaryChannel',
            'monitor',
            'costEstimate',
            'histories',
            'volcanoEndpointOptions'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $scope = (string) $request->input('save_scope', 'all');

        return match ($scope) {
            'global' => $this->updateGlobal($request),
            'provider' => $this->updateProvider($request),
            default => redirect()->route('admin.ai-config.index')->with('error', '未知的保存范围，请刷新页面后重试。'),
        };
    }

    private function updateGlobal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'save_scope' => 'required|string|in:global',
            'default_provider' => 'required|string|in:deepseek,volcano,zhipu',
            'resume_optimize_primary_channel' => 'required|string|in:session',
        ]);

        $result = $this->updateService->updateGlobal($validated);

        if (! $result['success']) {
            return redirect()->route('admin.ai-config.index')->with('error', $result['message']);
        }

        return redirect()->route('admin.ai-config.index')
            ->with('success', $result['message'])
            ->with('saved_scope', 'global');
    }

    private function updateProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'save_scope' => 'required|string|in:provider',
            'provider' => 'required|string|in:deepseek,volcano,zhipu',
            'deepseek_api_key' => 'nullable|string',
            'deepseek_model' => 'nullable|string',
            'deepseek_enabled' => 'nullable|boolean',
            'volcano_api_key' => 'nullable|string',
            'volcano_model' => 'nullable|string',
            'volcano_enabled' => 'nullable|boolean',
            'zhipu_api_key' => 'nullable|string',
            'zhipu_model' => 'nullable|string',
            'zhipu_enabled' => 'nullable|boolean',
        ]);

        $result = $this->updateService->updateProvider($validated);

        if (! $result['success']) {
            return redirect()->route('admin.ai-config.index')->with('error', $result['message']);
        }

        return redirect()->route('admin.ai-config.index')
            ->with('success', $result['message'])
            ->with('saved_scope', 'provider')
            ->with('saved_provider', $result['provider'] ?? null);
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:deepseek,volcano,zhipu',
            'test_type' => 'required|string|in:chat,resume_optimize,resume_score,interview_question,interview_evaluate',
        ]);

        $result = $this->benchmarkService->testProvider($validated['provider'], $validated['test_type']);

        return response()->json($result, $result['success'] ? 200 : ($result['error'] ?? '' ? 502 : 400));
    }

    public function history(): View
    {
        $histories = AiConfigHistory::query()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate((int) config('ui.pagination.admin_table', 20));

        return view('admin.ai-config.history', compact('histories'));
    }

    public function benchmark(): JsonResponse
    {
        return response()->json($this->benchmarkService->run());
    }

    /**
     * @param  Collection<int, AiConfigHistory>  $histories
     * @return array<int, string>
     */
    private function collectVolcanoEndpointOptions(?string $currentValue, Collection $histories): array
    {
        $options = [];
        $push = static function (?string $value) use (&$options): void {
            $candidate = trim((string) $value);
            if ($candidate === '' || ! str_starts_with($candidate, 'ep-') || in_array($candidate, $options, true)) {
                return;
            }

            $options[] = $candidate;
        };

        $push($currentValue);

        foreach ($histories as $history) {
            $changes = is_array($history->changes ?? null) ? $history->changes : [];
            $change = $changes['volcano_model'] ?? null;
            if (! is_array($change)) {
                continue;
            }

            $push(isset($change['new']) ? (string) $change['new'] : null);
            $push(isset($change['old']) ? (string) $change['old'] : null);
        }

        return $options;
    }
}
