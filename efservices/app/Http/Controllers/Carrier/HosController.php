<?php

namespace App\Http\Controllers\Carrier;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosDailyLog;
use App\Services\Hos\HosService;
use App\Services\Hos\HosCalculationService;
use App\Services\Hos\HosAlertService;
use App\Services\Hos\HosConfigurationService;
use App\Services\Hos\HosReportService;
use Illuminate\Support\Facades\Auth;

class HosController extends Controller
{
    protected HosService $hosService;
    protected HosCalculationService $calculationService;
    protected HosAlertService $alertService;
    protected HosConfigurationService $configService;
    protected HosReportService $reportService;

    public function __construct(
        HosService $hosService,
        HosCalculationService $calculationService,
        HosAlertService $alertService,
        HosConfigurationService $configService,
        HosReportService $reportService
    ) {
        $this->hosService = $hosService;
        $this->calculationService = $calculationService;
        $this->alertService = $alertService;
        $this->configService = $configService;
        $this->reportService = $reportService;
    }

    /**
     * Display the HOS dashboard for the carrier.
     */
    public function index()
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        if (!$carrier) {
            return redirect()->back()->with('error', 'Carrier not found.');
        }

        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->active()
            ->get();

        // Get today's summary for each driver
        $today = Carbon::today();
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

        return view('carrier.hos.dashboard', [
            'carrier' => $carrier,
            'driverSummaries' => $driverSummaries,
            'config' => $config,
        ]);
    }

    /**
     * Display a driver's HOS log.
     */
    public function driverLog(Request $request, $driverId)
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        $driver = UserDriverDetail::where('id', $driverId)
            ->where('carrier_id', $carrier->id)
            ->with('user')
            ->firstOrFail();

        $startDate = $request->get('start_date', Carbon::today()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $entries = $this->hosService->getDriverEntriesForDateRange(
            $driver->id,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        $dailyLogs = HosDailyLog::forDriver($driver->id)
            ->forDateRange(Carbon::parse($startDate), Carbon::parse($endDate))
            ->orderBy('date', 'desc')
            ->get();

        return view('carrier.hos.driver-log', [
            'driver' => $driver,
            'entries' => $entries,
            'dailyLogs' => $dailyLogs,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Update an HOS entry.
     */
    public function updateEntry(Request $request, $entryId)
    {
        $request->validate([
            'status' => 'nullable|in:on_duty_not_driving,on_duty_driving,off_duty',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        $entry = HosEntry::where('id', $entryId)
            ->where('carrier_id', $carrier->id)
            ->firstOrFail();

        try {
            $updateData = array_filter([
                'status' => $request->status,
                'start_time' => $request->start_time ? Carbon::parse($request->start_time) : null,
                'end_time' => $request->end_time ? Carbon::parse($request->end_time) : null,
            ]);

            $updatedEntry = $this->hosService->updateEntry(
                $entry,
                $updateData,
                $user->id,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Entry updated successfully.',
                'entry' => $updatedEntry,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Create a manual HOS entry.
     */
    public function createManualEntry(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:user_driver_details,id',
            'status' => 'required|in:on_duty_not_driving,on_duty_driving,off_duty',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'reason' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        // Verify driver belongs to carrier
        $driver = UserDriverDetail::where('id', $request->driver_id)
            ->where('carrier_id', $carrier->id)
            ->firstOrFail();

        try {
            $location = null;
            if ($request->has('latitude') && $request->has('longitude')) {
                $location = [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'address' => $request->address ?? null,
                ];
            }

            $entry = $this->hosService->createManualEntry(
                $driver->id,
                $request->status,
                Carbon::parse($request->start_time),
                $request->end_time ? Carbon::parse($request->end_time) : null,
                $location,
                $user->id,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Manual entry created successfully.',
                'entry' => $entry,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Display HOS configuration.
     */
    public function configuration()
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        if (!$carrier) {
            return redirect()->back()->with('error', 'Carrier not found.');
        }

        $config = $this->configService->getConfiguration($carrier->id);
        $defaults = $this->configService->getDefaults();

        return view('carrier.hos.configuration', [
            'carrier' => $carrier,
            'config' => $config,
            'defaults' => $defaults,
        ]);
    }

    /**
     * Update HOS configuration.
     */
    public function updateConfiguration(Request $request)
    {
        $request->validate([
            'max_driving_hours' => 'required|numeric|min:1|max:24',
            'max_duty_hours' => 'required|numeric|min:1|max:24',
            'warning_threshold_minutes' => 'required|integer|min:0|max:180',
        ]);

        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        try {
            $config = $this->configService->updateConfiguration($carrier->id, [
                'max_driving_hours' => $request->max_driving_hours,
                'max_duty_hours' => $request->max_duty_hours,
                'warning_threshold_minutes' => $request->warning_threshold_minutes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully.',
                'config' => $config,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Generate daily report.
     */
    public function dailyReport(Request $request, $driverId)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        $driver = UserDriverDetail::where('id', $driverId)
            ->where('carrier_id', $carrier->id)
            ->firstOrFail();

        try {
            $path = $this->reportService->generateDailyReport(
                $driver->id,
                Carbon::parse($request->date)
            );

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
     * Generate monthly report.
     */
    public function monthlyReport(Request $request, $driverId)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        $driver = UserDriverDetail::where('id', $driverId)
            ->where('carrier_id', $carrier->id)
            ->firstOrFail();

        try {
            $path = $this->reportService->generateMonthlyReport(
                $driver->id,
                $request->year,
                $request->month
            );

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
     * Get violations for the carrier.
     */
    public function violations(Request $request)
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        $startDate = $request->get('start_date', Carbon::today()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $violations = $this->alertService->getCarrierViolations(
            $carrier->id,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        return view('carrier.hos.violations', [
            'carrier' => $carrier,
            'violations' => $violations,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Update a HOS entry (form-based).
     */
    public function updateEntryForm(Request $request, HosEntry $entry)
    {
        $request->validate([
            'status' => 'required|in:on_duty_driving,on_duty_not_driving,off_duty',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'formatted_address' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        // Verify entry belongs to carrier
        if ($entry->carrier_id != $carrier->id) {
            abort(403, 'Unauthorized access to this entry.');
        }

        $entry->update([
            'status' => $request->status,
            'start_time' => Carbon::parse($request->start_time),
            'end_time' => $request->end_time ? Carbon::parse($request->end_time) : null,
            'formatted_address' => $request->formatted_address,
            'is_manual_entry' => true,
        ]);

        return redirect()->route('carrier.hos.driver.log', $entry->user_driver_detail_id)
            ->with('success', 'HOS entry updated successfully.');
    }

    /**
     * Delete a HOS entry.
     */
    public function deleteEntry(HosEntry $entry)
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        // Verify entry belongs to carrier
        if ($entry->carrier_id != $carrier->id) {
            abort(403, 'Unauthorized access to this entry.');
        }

        $driverId = $entry->user_driver_detail_id;
        $entry->delete();

        return redirect()->route('carrier.hos.driver.log', $driverId)
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

        $user = Auth::user();
        $carrier = $user->carrierDetails?->carrier ?? $user->carriers->first();

        // Verify all entries belong to carrier
        $entries = HosEntry::whereIn('id', $request->entry_ids)->get();
        foreach ($entries as $entry) {
            if ($entry->carrier_id != $carrier->id) {
                abort(403, 'Unauthorized access to one or more entries.');
            }
        }

        HosEntry::whereIn('id', $request->entry_ids)->delete();

        return redirect()->route('carrier.hos.driver.log', $request->driver_id)
            ->with('success', count($request->entry_ids) . ' HOS entries deleted successfully.');
    }
}
