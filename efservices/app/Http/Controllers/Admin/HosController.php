<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosViolation;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosEntry;
use App\Services\Hos\HosService;
use App\Services\Hos\HosCalculationService;
use App\Services\Hos\HosAlertService;
use App\Services\Hos\HosConfigurationService;
use App\Services\Hos\HosReportService;
use App\Services\Hos\HosViolationForgivenessService;
use App\Http\Requests\Hos\ForgiveViolationRequest;
use Illuminate\Validation\ValidationException;

class HosController extends Controller
{
    protected HosService $hosService;
    protected HosCalculationService $calculationService;
    protected HosAlertService $alertService;
    protected HosConfigurationService $configService;
    protected HosReportService $reportService;
    protected HosViolationForgivenessService $forgivenessService;

    public function __construct(
        HosService $hosService,
        HosCalculationService $calculationService,
        HosAlertService $alertService,
        HosConfigurationService $configService,
        HosReportService $reportService,
        HosViolationForgivenessService $forgivenessService
    ) {
        $this->hosService = $hosService;
        $this->calculationService = $calculationService;
        $this->alertService = $alertService;
        $this->configService = $configService;
        $this->reportService = $reportService;
        $this->forgivenessService = $forgivenessService;
    }

    /**
     * Display the HOS dashboard for admin (all carriers).
     */
    public function index()
    {
        $today = Carbon::today();

        // Get all carriers with HOS activity summary
        $carriers = Carrier::withCount(['userDrivers' => function ($query) {
            $query->active();
        }])->get();

        $carrierSummaries = $carriers->map(function ($carrier) use ($today) {
            $activeDrivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->active()
                ->count();

            $todayViolations = HosViolation::forCarrier($carrier->id)
                ->whereDate('violation_date', $today)
                ->count();

            $monthViolations = HosViolation::forCarrier($carrier->id)
                ->whereMonth('violation_date', $today->month)
                ->whereYear('violation_date', $today->year)
                ->count();

            $config = $this->configService->getConfiguration($carrier->id);

            return [
                'carrier' => $carrier,
                'active_drivers' => $activeDrivers,
                'today_violations' => $todayViolations,
                'month_violations' => $monthViolations,
                'config' => $config,
            ];
        });

        // Get total violations today across all carriers
        $totalTodayViolations = HosViolation::whereDate('violation_date', $today)->count();

        return view('admin.hos.dashboard', [
            'carrierSummaries' => $carrierSummaries,
            'totalTodayViolations' => $totalTodayViolations,
        ]);
    }

    /**
     * Display HOS details for a specific carrier.
     */
    public function carrierDetail($carrierId)
    {
        $carrier = Carrier::findOrFail($carrierId);
        $today = Carbon::today();

        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->active()
            ->get();

        // Get today's summary for each driver
        $driverSummaries = $drivers->map(function ($driver) use ($today) {
            $currentEntry = $this->hosService->getDriverCurrentStatus($driver->id);
            $totals = $this->calculationService->calculateDailyTotals($driver->id, $today);
            $remaining = $this->calculationService->calculateRemainingHours($driver->id, $today);

            return [
                'driver' => $driver,
                'current_status' => $currentEntry?->status_name ?? 'No status',
                'totals' => $totals,
                'remaining' => $remaining,
            ];
        });

        $config = $this->configService->getConfiguration($carrier->id);

        return view('admin.hos.carrier-detail', [
            'carrier' => $carrier,
            'driverSummaries' => $driverSummaries,
            'config' => $config,
        ]);
    }

    /**
     * Display violations across all carriers.
     */
    public function violations(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::today()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));
        $carrierId = $request->get('carrier_id');

        $query = HosViolation::with(['driver.user', 'carrier', 'vehicle'])
            ->forDateRange(Carbon::parse($startDate), Carbon::parse($endDate))
            ->orderBy('violation_date', 'desc');

        if ($carrierId) {
            $query->forCarrier($carrierId);
        }

        $violations = $query->paginate(50);

        $carriers = Carrier::orderBy('name')->get();

        // Summary statistics
        $totalViolations = HosViolation::forDateRange(
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        )->count();

        $drivingViolations = HosViolation::forDateRange(
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        )->where('violation_type', HosViolation::TYPE_DRIVING_LIMIT_EXCEEDED)->count();

        $dutyViolations = HosViolation::forDateRange(
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        )->where('violation_type', HosViolation::TYPE_DUTY_LIMIT_EXCEEDED)->count();

        return view('admin.hos.violations', [
            'violations' => $violations,
            'carriers' => $carriers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCarrierId' => $carrierId,
            'totalViolations' => $totalViolations,
            'drivingViolations' => $drivingViolations,
            'dutyViolations' => $dutyViolations,
        ]);
    }

    /**
     * View driver's HOS log (admin access).
     */
    public function driverLog(Request $request, $driverId)
    {
        $driver = UserDriverDetail::with(['user', 'carrier'])->findOrFail($driverId);

        $startDate = $request->get('start_date', Carbon::today()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $entries = $this->hosService->getDriverEntriesForDateRange(
            $driver->id,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        // Recalculate daily logs for the date range to ensure accuracy (covers imported entries)
        $current = Carbon::parse($startDate)->copy();
        $end = Carbon::parse($endDate);
        while ($current->lte($end)) {
            $this->calculationService->recalculateDailyLog($driver->id, $current);
            $current->addDay();
        }

        $dailyLogs = HosDailyLog::forDriver($driver->id)
            ->forDateRange(Carbon::parse($startDate), Carbon::parse($endDate))
            ->orderBy('date', 'desc')
            ->get();

        // Get all HOS documents for this driver
        $hosDocuments = collect()
            ->merge($driver->getMedia('daily_logs'))
            ->merge($driver->getMedia('monthly_summaries'))
            ->sortByDesc(function ($doc) {
                return $doc->created_at;
            })
            ->values();

        return view('admin.hos.driver-log', [
            'driver' => $driver,
            'entries' => $entries,
            'dailyLogs' => $dailyLogs,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'hosDocuments' => $hosDocuments,
        ]);
    }

    /**
     * Generate reports (admin access).
     */
    public function generateReport(Request $request, $driverId)
    {
        $request->validate([
            'type' => 'required|in:daily,monthly',
            'date' => 'required_if:type,daily|date',
            'year' => 'required_if:type,monthly|integer',
            'month' => 'required_if:type,monthly|integer|min:1|max:12',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);

        try {
            if ($request->type === 'daily') {
                $path = $this->reportService->generateDailyReport(
                    $driver->id,
                    Carbon::parse($request->date)
                );
            } else {
                $path = $this->reportService->generateMonthlyReport(
                    $driver->id,
                    $request->year,
                    $request->month
                );
            }

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => asset('storage/' . $path),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate report.'], 500);
        }
    }

    /**
     * Show violation details.
     */
    public function violationShow(HosViolation $violation)
    {
        $violation->load(['driver.user', 'driver.carrier', 'trip', 'carrier']);

        return view('admin.hos.violations-show', compact('violation'));
    }

    /**
     * Acknowledge a violation.
     */
    public function violationAcknowledge(HosViolation $violation)
    {
        $violation->update([
            'acknowledged' => true,
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        return back()->with('success', 'Violation acknowledged successfully.');
    }

    /**
     * Show forgiveness form for a violation.
     */
    public function violationForgiveForm(HosViolation $violation)
    {
        $violation->load(['driver.user', 'driver.carrier', 'trip', 'carrier', 'vehicle']);

        // Check if violation can be forgiven
        if ($violation->isForgiven()) {
            return redirect()
                ->route('admin.hos.violations.show', $violation)
                ->with('error', 'This violation has already been forgiven.');
        }

        return view('admin.hos.violations-forgive', compact('violation'));
    }

    /**
     * Process violation forgiveness.
     */
    public function violationForgive(ForgiveViolationRequest $request, HosViolation $violation)
    {
        try {
            $adjustedEndTime = $request->filled('adjusted_end_time')
                ? Carbon::parse($request->adjusted_end_time)
                : null;

            $this->forgivenessService->forgiveViolation(
                $violation,
                auth()->id(),
                $request->forgiveness_reason,
                $adjustedEndTime
            );

            // Generate violation report PDF
            $pdfService = app(\App\Services\Hos\HosPdfService::class);
            $pdfService->generateViolationReport($violation->fresh());

            return redirect()
                ->route('admin.hos.violations.show', $violation)
                ->with('success', 'Violation has been forgiven successfully and a report has been generated. The driver can now drive again.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to forgive violation: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update a HOS entry.
     */
    public function updateEntry(Request $request, HosEntry $entry)
    {
        $request->validate([
            'status' => 'required|in:on_duty_driving,on_duty_not_driving,off_duty',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'formatted_address' => 'nullable|string|max:255',
        ]);

        $entry->update([
            'status' => $request->status,
            'start_time' => Carbon::parse($request->start_time),
            'end_time' => $request->end_time ? Carbon::parse($request->end_time) : null,
            'formatted_address' => $request->formatted_address,
            'is_manual_entry' => true,
        ]);

        return redirect()->route('admin.hos.driver.log', $entry->user_driver_detail_id)
            ->with('success', 'HOS entry updated successfully.');
    }

    /**
     * Delete a HOS entry.
     */
    public function deleteEntry(HosEntry $entry)
    {
        $driverId = $entry->user_driver_detail_id;
        
        $entry->delete();

        return redirect()->route('admin.hos.driver.log', $driverId)
            ->with('success', 'HOS entry deleted successfully.');
    }

    /**
     * Delete multiple HOS entries.
     */
    public function bulkDeleteEntries(Request $request)
    {
        $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'exists:hos_entries,id',
            'driver_id' => 'required|exists:user_driver_details,id',
        ]);

        HosEntry::whereIn('id', $request->entry_ids)->delete();

        return redirect()->route('admin.hos.driver.log', $request->driver_id)
            ->with('success', count($request->entry_ids) . ' HOS entries deleted successfully.');
    }
}
