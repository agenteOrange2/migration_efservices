<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected StatisticsService $statisticsService,
    ) {}

    public function index(Request $request): Response
    {
        try {
            $statistics = $this->statisticsService->getDashboardStats();
        } catch (\Exception $e) {
            $statistics = [
                'carriers' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'pending' => 0, 'monthly_growth_rate' => 0],
                'drivers' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'monthly_growth_rate' => 0],
                'vehicles' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'monthly_growth_rate' => 0],
                'users' => ['total' => 0, 'active' => 0, 'monthly_growth_rate' => 0],
                'revenue' => ['total_active_revenue' => 0, 'total_active_subscribers' => 0],
                'growth' => ['monthly_data' => [], 'growth_rates' => [], 'trends' => []],
                'activity' => ['daily_activity' => [], 'weekly_activity' => [], 'monthly_activity' => []],
                'alerts' => [],
            ];
        }

        $recentRecords = [
            'carriers' => $this->statisticsService->getRecentCarriers(5),
            'drivers' => $this->statisticsService->getRecentDrivers(5),
            'vehicles' => $this->statisticsService->getRecentVehicles(5),
        ];

        $systemAlerts = $this->statisticsService->getSystemAlerts();

        return Inertia::render('admin/Dashboard', [
            'statistics' => $statistics,
            'recentRecords' => $recentRecords,
            'systemAlerts' => $systemAlerts,
        ]);
    }
}
