<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosViolation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class HosReportService
{
    protected HosCalculationService $calculationService;
    protected HosAlertService $alertService;

    public function __construct(
        HosCalculationService $calculationService,
        HosAlertService $alertService
    ) {
        $this->calculationService = $calculationService;
        $this->alertService = $alertService;
    }

    /**
     * Generate daily report PDF.
     *
     * @param int $driverId
     * @param Carbon|string $date
     * @return string Path to generated PDF
     */
    public function generateDailyReport(int $driverId, $date): string
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $driver = UserDriverDetail::with(['user', 'carrier'])->findOrFail($driverId);
        
        // Get entries for the day
        $entries = HosEntry::forDriver($driverId)
            ->forDate($date)
            ->with('vehicle')
            ->orderBy('start_time')
            ->get();

        // Get daily totals
        $totals = $this->calculationService->calculateDailyTotals($driverId, $date);

        // Get violations for the day
        $violations = HosViolation::forDriver($driverId)
            ->whereDate('violation_date', $date)
            ->get();

        // Get daily log for signature
        $dailyLog = HosDailyLog::forDriver($driverId)
            ->whereDate('date', $date)
            ->first();

        // Get vehicle info from first entry
        $vehicle = $entries->first()?->vehicle;

        // Format entries for report
        $formattedEntries = $entries->map(function ($entry) {
            return [
                'start_time' => $entry->start_time->format('H:i'),
                'end_time' => $entry->end_time ? $entry->end_time->format('H:i') : 'Current',
                'status' => $entry->status_name,
                'duration' => $entry->formatted_duration,
                'location' => $this->formatLocationForReport(
                    $entry->latitude,
                    $entry->longitude,
                    $entry->formatted_address
                ),
                'is_manual' => $entry->is_manual_entry,
            ];
        });

        $data = [
            'driver' => $driver,
            'date' => $date,
            'vehicle' => $vehicle,
            'entries' => $formattedEntries,
            'totals' => $totals,
            'violations' => $violations,
            'signature' => $dailyLog?->driver_signature,
            'signed_at' => $dailyLog?->signed_at,
            'generated_at' => Carbon::now(),
        ];

        $pdf = Pdf::loadView('reports.hos.daily', $data);
        
        $filename = "hos_daily_{$driverId}_{$date->format('Y-m-d')}.pdf";
        $path = "hos-reports/daily/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate monthly report PDF.
     *
     * @param int $driverId
     * @param int $year
     * @param int $month
     * @return string Path to generated PDF
     */
    public function generateMonthlyReport(int $driverId, int $year, int $month): string
    {
        $driver = UserDriverDetail::with(['user', 'carrier'])->findOrFail($driverId);
        
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // Get monthly totals
        $monthlyTotals = $this->calculationService->calculateMonthlyTotals($driverId, $year, $month);

        // Get daily logs for the month
        $dailyLogs = HosDailyLog::forDriver($driverId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('date')
            ->get();

        // Get violations for the month
        $violations = HosViolation::forDriver($driverId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('violation_date')
            ->get();

        // Get vehicle info (most used vehicle in the month)
        $vehicleId = HosEntry::forDriver($driverId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('vehicle_id, COUNT(*) as count')
            ->groupBy('vehicle_id')
            ->orderByDesc('count')
            ->first()?->vehicle_id;

        $vehicle = $vehicleId ? \App\Models\Admin\Vehicle\Vehicle::find($vehicleId) : null;

        // Format daily breakdown
        $dailyBreakdown = $dailyLogs->map(function ($log) {
            return [
                'date' => $log->date->format('Y-m-d'),
                'day_name' => $log->date->format('l'),
                'driving' => $log->formatted_driving_time,
                'on_duty' => $log->formatted_on_duty_time,
                'off_duty' => $log->formatted_off_duty_time,
                'has_violations' => $log->has_violations,
            ];
        });

        // Format violations
        $violationDays = $violations->groupBy(function ($v) {
            return $v->violation_date->format('Y-m-d');
        })->map(function ($dayViolations, $date) {
            return [
                'date' => $date,
                'violations' => $dayViolations->map(function ($v) {
                    return [
                        'type' => $v->violation_type_name,
                        'exceeded' => $v->formatted_hours_exceeded,
                    ];
                }),
            ];
        });

        $data = [
            'driver' => $driver,
            'vehicle' => $vehicle,
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->format('F'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'totals' => $monthlyTotals,
            'daily_breakdown' => $dailyBreakdown,
            'violation_days' => $violationDays,
            'generated_at' => Carbon::now(),
        ];

        $pdf = Pdf::loadView('reports.hos.monthly', $data);
        
        $filename = "hos_monthly_{$driverId}_{$year}_{$month}.pdf";
        $path = "hos-reports/monthly/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Format location for report display.
     *
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string|null $address
     * @return string
     */
    public function formatLocationForReport(?float $latitude, ?float $longitude, ?string $address): string
    {
        if ($address) {
            return $address;
        }

        if ($latitude && $longitude) {
            return sprintf('%.6f, %.6f', $latitude, $longitude);
        }

        return 'Location unavailable';
    }

    /**
     * Get report data for daily report (without generating PDF).
     *
     * @param int $driverId
     * @param Carbon|string $date
     * @return array
     */
    public function getDailyReportData(int $driverId, $date): array
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $driver = UserDriverDetail::with(['user', 'carrier'])->findOrFail($driverId);
        
        $entries = HosEntry::forDriver($driverId)
            ->forDate($date)
            ->with('vehicle')
            ->orderBy('start_time')
            ->get();

        $totals = $this->calculationService->calculateDailyTotals($driverId, $date);

        $violations = HosViolation::forDriver($driverId)
            ->whereDate('violation_date', $date)
            ->get();

        $dailyLog = HosDailyLog::forDriver($driverId)
            ->whereDate('date', $date)
            ->first();

        return [
            'driver' => $driver,
            'date' => $date->format('Y-m-d'),
            'entries' => $entries,
            'totals' => $totals,
            'violations' => $violations,
            'daily_log' => $dailyLog,
        ];
    }

    /**
     * Get report data for monthly report (without generating PDF).
     *
     * @param int $driverId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getMonthlyReportData(int $driverId, int $year, int $month): array
    {
        $driver = UserDriverDetail::with(['user', 'carrier'])->findOrFail($driverId);
        
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        $monthlyTotals = $this->calculationService->calculateMonthlyTotals($driverId, $year, $month);

        $dailyLogs = HosDailyLog::forDriver($driverId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('date')
            ->get();

        $violations = HosViolation::forDriver($driverId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('violation_date')
            ->get();

        return [
            'driver' => $driver,
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'totals' => $monthlyTotals,
            'daily_logs' => $dailyLogs,
            'violations' => $violations,
        ];
    }
}
