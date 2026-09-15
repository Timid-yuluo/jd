<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        $dashboardData = $this->dashboardService->getDashboardData();

        return view('admin.dashboard', $dashboardData);
    }
}
