<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\VisitorAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class VisitorAnalyticsController extends Controller
{
    public function __construct(
        private readonly VisitorAnalyticsService $analyticsService,
    ) {}

    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from') ?: now()->subDays(29)->format('Y-m-d');
        $dateTo = $request->input('date_to') ?: now()->format('Y-m-d');

        $data = $this->analyticsService->getDashboardData($dateFrom, $dateTo);

        return view('admin.visitor-analytics.index', array_merge($data, [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]));
    }

    public function detail(Request $request): View
    {
        $visits = $this->analyticsService->getVisitDetails(
            $request->input('search'),
            $request->input('event_type'),
            $request->input('user_id') ? (int) $request->input('user_id') : null,
            $request->input('date_from'),
            $request->input('date_to'),
            $request->input('device_type'),
        );

        $eventTypes = \App\Models\PageVisit::query()
            ->distinct('event_type')
            ->pluck('event_type')
            ->sort()
            ->values();

        return view('admin.visitor-analytics.detail', compact('visits', 'eventTypes'));
    }

    public function export(Request $request): Response
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $csv = $this->analyticsService->exportCsv($dateFrom, $dateTo);

        $filename = 'page_visits_'.($dateFrom ?: 'all').'_'.($dateTo ?: 'all').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
