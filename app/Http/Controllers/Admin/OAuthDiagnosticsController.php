<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSettingAuditLog;
use App\Services\Auth\OAuthDiagnosticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

final class OAuthDiagnosticsController extends Controller
{
    public function index(Request $request, OAuthDiagnosticsService $diagnostics): View
    {
        $hours = max(1, min(720, (int) $request->integer('hours', 24)));
        $report = $diagnostics->diagnose($hours);

        $alertMinFailures = max(1, (int) $request->integer('alert_min_failures', 5));
        $alertFailureRate = max(1.0, min(100.0, (float) $request->input('alert_failure_rate', 80)));
        $recentMitigations = SystemSettingAuditLog::query()
            ->where('user_agent', 'oauth:diagnose --auto-mitigate')
            ->latest('id')
            ->limit((int) config('ui.limit.oauth_diag_list', 20))
            ->get();

        return view('admin.system-ops.oauth-diagnostics', [
            'report' => $report,
            'hours' => $hours,
            'alertMinFailures' => $alertMinFailures,
            'alertFailureRate' => $alertFailureRate,
            'recentMitigations' => $recentMitigations,
            'mitigationOutput' => session('mitigation_output'),
        ]);
    }

    public function autoMitigate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hours' => 'nullable|integer|min:1|max:720',
            'alert_min_failures' => 'nullable|integer|min:1|max:10000',
            'alert_failure_rate' => 'nullable|numeric|min:1|max:100',
        ]);

        $hours = (int) ($validated['hours'] ?? 24);
        $alertMinFailures = (int) ($validated['alert_min_failures'] ?? 5);
        $alertFailureRate = (float) ($validated['alert_failure_rate'] ?? 80);

        Artisan::call('oauth:diagnose', [
            '--hours' => $hours,
            '--alert-min-failures' => $alertMinFailures,
            '--alert-failure-rate' => $alertFailureRate,
            '--auto-mitigate' => true,
        ]);
        $output = trim((string) Artisan::output());

        return redirect()->route('admin.system-ops.oauth-diagnostics', [
            'hours' => $hours,
            'alert_min_failures' => $alertMinFailures,
            'alert_failure_rate' => $alertFailureRate,
        ])->with('success', '已执行 OAuth 自动处置任务。')
            ->with('mitigation_output', $output);
    }
}
