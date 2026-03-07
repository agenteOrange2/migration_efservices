<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;

class ApprovedDriversController extends Controller
{
    /**
     * Display a listing of approved drivers.
     */
    public function index(Request $request)
    {
        $query = UserDriverDetail::with([
            'user',
            'carrier',
            'primaryLicense',
            'medicalQualification'
        ])->where('status', UserDriverDetail::STATUS_ACTIVE);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhereHas('primaryLicense', function($licenseQuery) use ($search) {
                    $licenseQuery->where('license_number', 'like', "%{$search}%");
                });
            });
        }

        // Carrier filter
        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('hire_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('hire_date', '<=', $request->date_to);
        }

        $drivers = $query->orderBy('hire_date', 'desc')->paginate(15);
        
        // Get carriers for filter dropdown
        $carriers = \App\Models\Carrier::orderBy('name')->get();

        return view('admin.drivers.approved.index', compact('drivers', 'carriers'));
    }

    /**
     * Display the specified driver with all documents.
     */
    public function show(UserDriverDetail $driver)
    {
        // Verify driver is approved
        if ($driver->status !== UserDriverDetail::STATUS_ACTIVE) {
            return redirect()->route('admin.drivers.approved.index')
                ->with('error', 'This driver is not approved.');
        }

        // Load all relationships
        $driver->load([
            'user',
            'carrier',
            'application.addresses',
            'licenses.endorsements',
            'medicalQualification',
            'workHistories',
            'trainingSchools',
            'accidents',
            'trafficConvictions',
            'experiences',
            'certification',
            'assignedVehicle',
            'activeVehicleAssignment.vehicle',
            'testings',
            'inspections',
            'driverTrainings'
        ]);

        // Load HOS data for the driver
        $hosData = $this->getDriverHosData($driver);

        return view('admin.drivers.approved.show', compact('driver', 'hosData'));
    }

    /**
     * Get HOS data for a driver including statistics, entries, violations, and documents.
     * 
     * @param UserDriverDetail $driver
     * @return array
     */
    private function getDriverHosData(UserDriverDetail $driver): array
    {
        $today = \Carbon\Carbon::today();
        
        // Calculate current day statistics using HosCalculationService
        $calculationService = app(\App\Services\Hos\HosCalculationService::class);
        $totals = $calculationService->calculateDailyTotals($driver->id, $today);
        $remaining = $calculationService->calculateRemainingHours($driver->id, $today);
        
        // Get recent HOS entries (last 10, ordered by start_time desc)
        $recentEntries = \App\Models\Hos\HosEntry::forDriver($driver->id)
            ->with(['vehicle'])
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();
        
        // Get active violations (not forgiven, ordered by violation_date desc)
        $activeViolations = \App\Models\Hos\HosViolation::forDriver($driver->id)
            ->notForgiven()
            ->orderBy('violation_date', 'desc')
            ->get();
        
        // Get forgiven violations count
        $forgivenViolationsCount = \App\Models\Hos\HosViolation::forDriver($driver->id)
            ->forgiven()
            ->count();
        
        // Get recent documents from media collections (daily_logs, monthly_summaries)
        $recentDocuments = collect();
        $recentDocuments = $recentDocuments
            ->merge($driver->getMedia('daily_logs'))
            ->merge($driver->getMedia('monthly_summaries'))
            ->sortByDesc('created_at')
            ->take(10);
        
        // Return structured array with statistics, entries, violations, documents
        return [
            'statistics' => [
                'current_day_driving_minutes' => $totals['driving_minutes'] ?? 0,
                'current_day_on_duty_minutes' => $totals['on_duty_minutes'] ?? 0,
                'remaining_driving_minutes' => $remaining['remaining_driving_minutes'] ?? 0,
                'remaining_on_duty_minutes' => $remaining['remaining_duty_minutes'] ?? 0,
                'active_violations_count' => $activeViolations->count(),
                'forgiven_violations_count' => $forgivenViolationsCount,
            ],
            'entries' => $recentEntries,
            'violations' => $activeViolations,
            'documents' => $recentDocuments,
        ];
    }
}
