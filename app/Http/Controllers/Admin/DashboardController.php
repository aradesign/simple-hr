<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\Dashboard\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => $this->dashboardService->getStats(),
            'todayInterviews' => $this->dashboardService->getTodayInterviews(),
            'recentApplications' => $this->dashboardService->getRecentApplications(),
            'assignments' => Assignment::query()
                ->where('user_id', auth()->id())
                ->with('assignable')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
