<?php

namespace App\Http\Controllers\Driver;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Hos\HosService;
use App\Services\Hos\HosCalculationService;
use App\Services\Hos\HosAlertService;
use App\Models\Hos\HosEntry;
use Illuminate\Support\Facades\Auth;

class HosController extends Controller
{
    protected HosService $hosService;
    protected HosCalculationService $calculationService;
    protected HosAlertService $alertService;

    public function __construct(
        HosService $hosService,
        HosCalculationService $calculationService,
        HosAlertService $alertService
    ) {
        $this->hosService = $hosService;
        $this->calculationService = $calculationService;
        $this->alertService = $alertService;
    }

    /**
     * Display the HOS dashboard for the driver.
     */
    public function index()
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->route('driver.dashboard')->with('error', 'Driver profile not found.');
        }

        // The Livewire component handles all the dashboard data loading
        return view('driver.hos.dashboard');
    }

    /**
     * Change the driver's HOS status.
     */
    public function changeStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:on_duty_not_driving,on_duty_driving,off_duty',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return response()->json(['error' => 'Driver profile not found.'], 404);
        }

        try {
            $location = null;
            if ($request->has('latitude') && $request->has('longitude')) {
                $location = [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'address' => $request->address,
                ];
            }

            $entry = $this->hosService->createEntry(
                $driver->id,
                $request->status,
                $location,
                $user->id
            );

            // Get updated dashboard data
            $dashboardData = $this->hosService->getDriverDashboard($driver->id);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'entry' => $entry,
                'dashboard' => $dashboardData,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update status.'], 500);
        }
    }

    /**
     * Display the driver's HOS history.
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->route('driver.dashboard')->with('error', 'Driver profile not found.');
        }

        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($date);

        try {
            $entries = $this->hosService->getDriverEntriesForDate($driver->id, $date);
            $totals = $this->calculationService->calculateDailyTotals($driver->id, $date);
        } catch (\Exception $e) {
            \Log::error('HOS History error: ' . $e->getMessage());
            $entries = collect();
            $totals = [
                'driving_formatted' => '0h 0m',
                'on_duty_formatted' => '0h 0m',
                'off_duty_formatted' => '0h 0m',
            ];
        }

        return view('driver.hos.history', [
            'driver' => $driver,
            'date' => $date,
            'entries' => $entries,
            'totals' => $totals,
        ]);
    }

    /**
     * Get entries for a specific date (AJAX).
     */
    public function getEntriesForDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return response()->json(['error' => 'Driver profile not found.'], 404);
        }

        $date = Carbon::parse($request->date);
        $entries = $this->hosService->getDriverEntriesForDate($driver->id, $date);
        $totals = $this->calculationService->calculateDailyTotals($driver->id, $date);

        return response()->json([
            'entries' => $entries,
            'totals' => $totals,
        ]);
    }

    /**
     * Request a correction for an HOS entry.
     */
    public function requestCorrection(Request $request)
    {
        $request->validate([
            'entry_id' => 'required|exists:hos_entries,id',
            'reason' => 'required|string|max:500',
            'requested_changes' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return response()->json(['error' => 'Driver profile not found.'], 404);
        }

        // TODO: Implement correction request system
        // This could create a notification/request for the carrier to review

        return response()->json([
            'success' => true,
            'message' => 'Correction request submitted successfully.',
        ]);
    }

    /**
     * Get current status (AJAX).
     */
    public function getCurrentStatus()
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return response()->json(['error' => 'Driver profile not found.'], 404);
        }

        $currentEntry = $this->hosService->getDriverCurrentStatus($driver->id);
        $today = Carbon::today();
        $totals = $this->calculationService->calculateDailyTotals($driver->id, $today);
        $remaining = $this->calculationService->calculateRemainingHours($driver->id, $today);
        $alerts = $this->alertService->getActiveAlerts($driver->id);

        return response()->json([
            'current_status' => $currentEntry,
            'totals' => $totals,
            'remaining' => $remaining,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Generate and download daily report PDF.
     */
    public function dailyReport(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->back()->with('error', 'Driver profile not found.');
        }

        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($date);

        try {
            $reportService = app(\App\Services\Hos\HosReportService::class);
            $pdfPath = $reportService->generateDailyReport($driver->id, $date);

            return response()->download(storage_path('app/public/' . $pdfPath));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Generate and download monthly report PDF.
     */
    public function monthlyReport(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->back()->with('error', 'Driver profile not found.');
        }

        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        try {
            $reportService = app(\App\Services\Hos\HosReportService::class);
            $pdfPath = $reportService->generateMonthlyReport($driver->id, $year, $month);

            return response()->download(storage_path('app/public/' . $pdfPath));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate report: ' . $e->getMessage());
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

        $user = Auth::user();
        $driver = $user->driverDetails;

        // Verify entry belongs to this driver
        if (!$driver || $entry->user_driver_detail_id != $driver->id) {
            abort(403, 'Unauthorized access to this entry.');
        }

        $entry->update([
            'status' => $request->status,
            'start_time' => Carbon::parse($request->start_time),
            'end_time' => $request->end_time ? Carbon::parse($request->end_time) : null,
            'formatted_address' => $request->formatted_address,
            'is_manual_entry' => true,
        ]);

        return redirect()->route('driver.hos.history')
            ->with('success', 'HOS entry updated successfully.');
    }

    /**
     * Delete a HOS entry.
     */
    public function deleteEntry(HosEntry $entry)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        // Verify entry belongs to this driver
        if (!$driver || $entry->user_driver_detail_id != $driver->id) {
            abort(403, 'Unauthorized access to this entry.');
        }

        $entry->delete();

        return redirect()->route('driver.hos.history')
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
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        // Verify all entries belong to this driver
        $entries = HosEntry::whereIn('id', $request->entry_ids)->get();
        foreach ($entries as $entry) {
            if ($entry->user_driver_detail_id != $driver->id) {
                abort(403, 'Unauthorized access to one or more entries.');
            }
        }

        HosEntry::whereIn('id', $request->entry_ids)->delete();

        return redirect()->route('driver.hos.history')
            ->with('success', count($request->entry_ids) . ' HOS entries deleted successfully.');
    }
}
