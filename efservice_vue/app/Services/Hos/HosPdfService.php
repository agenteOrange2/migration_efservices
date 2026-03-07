<?php

namespace App\Services\Hos;

use App\Models\Trip;
use App\Models\UserDriverDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class HosPdfService
{
    /**
     * Generate all trip reports (Trip Summary, Pre-Trip Inspection, Post-Trip Inspection).
     * 
     * @return array Array of generated PDF paths
     */
    public function generateTripReport(Trip $trip, ?string $signatureData = null): string
    {
        $trip->load(['driver.user', 'vehicle', 'carrier', 'hosEntries', 'gpsPoints', 'violations']);

        // Generate all 3 PDFs
        $this->generateTripSummaryPdf($trip, $signatureData);
        $this->generatePreTripInspectionPdf($trip, $signatureData);
        $this->generatePostTripInspectionPdf($trip, $signatureData);

        \Log::info('All trip reports generated', [
            'trip_id' => $trip->id,
            'driver_id' => $trip->driver->id,
            'has_signature' => !empty($signatureData),
        ]);

        // Return the trip summary path for backward compatibility
        $tripSummary = $trip->driver->getTripReportPdf($trip->id);
        return $tripSummary ? $tripSummary->getPath() : '';
    }

    /**
     * Generate Trip Summary PDF.
     */
    protected function generateTripSummaryPdf(Trip $trip, ?string $signatureData = null): string
    {
        // Calculate GPS statistics if available
        $gpsStats = null;
        if ($trip->gpsPoints && $trip->gpsPoints->isNotEmpty()) {
            $gpsService = app(\App\Services\Trip\TripGpsTrackingService::class);
            $gpsStats = $gpsService->getTripStatistics($trip);
        }

        // Prepare HOS entries data
        $hosEntries = $trip->hosEntries ? $trip->hosEntries->map(function ($entry) {
            return [
                'status' => $entry->status_name,
                'start_time' => $entry->start_time->format('M d, Y H:i'),
                'end_time' => $entry->end_time ? $entry->end_time->format('H:i') : 'Ongoing',
                'duration' => $entry->formatted_duration,
                'location' => $entry->location_display ?? 'N/A',
            ];
        }) : collect();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.trip-report', [
            'trip' => $trip,
            'hosEntries' => $hosEntries,
            'gpsStats' => $gpsStats,
            'signatureData' => $signatureData,
            'generatedAt' => now(),
        ]);

        // Generate filename
        $filename = sprintf(
            'trip_summary_%s_%s.pdf',
            $trip->trip_number ?? $trip->id,
            now()->format('YmdHis')
        );

        // Create temporary file
        $tempPath = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $pdf->output());

        // Attach to driver using Media Library
        $media = $trip->driver->addMedia($tempPath)
            ->withCustomProperties([
                'trip_id' => $trip->id,
                'report_type' => 'trip_summary',
                'document_date' => $trip->completed_at ? $trip->completed_at->format('Y-m-d') : now()->format('Y-m-d'),
                'carrier_id' => $trip->carrier_id,
                'signed_at' => $signatureData ? now()->toDateTimeString() : null,
            ])
            ->toMediaCollection('trip_reports');
        
        @unlink($tempPath);
        
        return $media->getPath();
    }

    /**
     * Generate Pre-Trip Inspection PDF.
     */
    protected function generatePreTripInspectionPdf(Trip $trip, ?string $signatureData = null): string
    {
        $driver = $trip->driver;
        $driverName = implode(' ', array_filter([
            $driver->user->name ?? 'Driver',
            $driver->middle_name ?? '',
            $driver->last_name ?? ''
        ]));

        $inspectionData = $trip->pre_trip_inspection_data ?? [];
        if (is_string($inspectionData)) {
            $inspectionData = json_decode($inspectionData, true) ?? [];
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.pre-trip-inspection', [
            'trip' => $trip,
            'driverName' => $driverName,
            'inspectionData' => $inspectionData,
            'signatureData' => $signatureData,
            'signedAt' => now(),
            'generatedAt' => now(),
        ]);

        // Generate filename
        $filename = sprintf(
            'pre_trip_inspection_%s_%s.pdf',
            $trip->trip_number ?? $trip->id,
            now()->format('YmdHis')
        );

        // Create temporary file
        $tempPath = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $pdf->output());

        // Attach to driver using Media Library
        $media = $trip->driver->addMedia($tempPath)
            ->withCustomProperties([
                'trip_id' => $trip->id,
                'report_type' => 'pre_trip_inspection',
                'document_date' => $trip->actual_start_time ? $trip->actual_start_time->format('Y-m-d') : now()->format('Y-m-d'),
                'carrier_id' => $trip->carrier_id,
                'signed_at' => $signatureData ? now()->toDateTimeString() : null,
            ])
            ->toMediaCollection('inspection_reports');
        
        @unlink($tempPath);
        
        \Log::info('Pre-Trip Inspection PDF generated', ['media_id' => $media->id, 'trip_id' => $trip->id]);
        
        return $media->getPath();
    }

    /**
     * Generate Post-Trip Inspection PDF.
     */
    protected function generatePostTripInspectionPdf(Trip $trip, ?string $signatureData = null): string
    {
        $driver = $trip->driver;
        $driverName = implode(' ', array_filter([
            $driver->user->name ?? 'Driver',
            $driver->middle_name ?? '',
            $driver->last_name ?? ''
        ]));

        $inspectionData = $trip->post_trip_inspection_data ?? [];
        if (is_string($inspectionData)) {
            $inspectionData = json_decode($inspectionData, true) ?? [];
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.post-trip-inspection', [
            'trip' => $trip,
            'driverName' => $driverName,
            'inspectionData' => $inspectionData,
            'signatureData' => $signatureData,
            'signedAt' => now(),
            'generatedAt' => now(),
        ]);

        // Generate filename
        $filename = sprintf(
            'post_trip_inspection_%s_%s.pdf',
            $trip->trip_number ?? $trip->id,
            now()->format('YmdHis')
        );

        // Create temporary file
        $tempPath = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $pdf->output());

        // Attach to driver using Media Library
        $media = $trip->driver->addMedia($tempPath)
            ->withCustomProperties([
                'trip_id' => $trip->id,
                'report_type' => 'post_trip_inspection',
                'document_date' => $trip->actual_end_time ? $trip->actual_end_time->format('Y-m-d') : now()->format('Y-m-d'),
                'carrier_id' => $trip->carrier_id,
                'signed_at' => $signatureData ? now()->toDateTimeString() : null,
            ])
            ->toMediaCollection('inspection_reports');
        
        @unlink($tempPath);
        
        \Log::info('Post-Trip Inspection PDF generated', ['media_id' => $media->id, 'trip_id' => $trip->id]);
        
        return $media->getPath();
    }

    /**
     * Get or generate trip PDF.
     */
    public function getOrGenerateTripPdf(Trip $trip): string
    {
        // Check if PDF already exists
        $existingPdf = $trip->driver->getTripReportPdf($trip->id);

        if ($existingPdf) {
            return $existingPdf->getPath();
        }

        // Generate new PDF
        return $this->generateTripReport($trip);
    }

    /**
     * Generate daily log PDF.
     */
    public function generateDailyLog(int $driverId, Carbon $date, ?string $signatureData = null): string
    {
        $driver = UserDriverDetail::with(['user', 'carrier', 'primaryLicense'])->findOrFail($driverId);

        
        // Get or create daily log
        $dailyLog = \App\Models\Hos\HosDailyLog::getOrCreateForDate(
            $driverId,
            $driver->carrier_id,
            null,
            $date
        );

        // Get all HOS entries for this date
        $hosEntries = \App\Models\Hos\HosEntry::forDriver($driverId)
            ->forDate($date)
            ->orderBy('start_time')
            ->get();

        // Calculate totals
        $totals = [
            'driving' => 0,
            'on_duty_not_driving' => 0,
            'off_duty' => 0,
        ];

        foreach ($hosEntries as $entry) {
            $minutes = $entry->duration_minutes;
            if ($entry->status === \App\Models\Hos\HosEntry::STATUS_ON_DUTY_DRIVING) {
                $totals['driving'] += $minutes;
            } elseif ($entry->status === \App\Models\Hos\HosEntry::STATUS_ON_DUTY_NOT_DRIVING) {
                $totals['on_duty_not_driving'] += $minutes;
            } else {
                $totals['off_duty'] += $minutes;
            }
        }

        // Get violations for this date
        $violations = \App\Models\Hos\HosViolation::where('user_driver_detail_id', $driverId)
            ->whereDate('violation_date', $date)
            ->get();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.daily-log', [
            'driver' => $driver,
            'date' => $date,
            'dailyLog' => $dailyLog,
            'hosEntries' => $hosEntries,
            'totals' => $totals,
            'fmcsaStatus' => null, // Removed getDailyStatus call - method doesn't exist
            'violations' => $violations,
            'signatureData' => $signatureData,
            'generatedAt' => now(),
        ]);


        // Generate filename
        $filename = sprintf(
            'daily_log_%s_%s.pdf',
            $date->format('Y-m-d'),
            now()->format('YmdHis')
        );

        // Create temporary file
        $tempPath = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $pdf->output());

        // Attach to driver using Media Library (CustomPathGenerator will handle the path)
        $media = $driver->addMedia($tempPath)
            ->withCustomProperties([
                'document_date' => $date->format('Y-m-d'),
                'carrier_id' => $driver->carrier_id,
                'signed_at' => $signatureData ? now()->toDateTimeString() : null,
            ])
            ->toMediaCollection('daily_logs');

        // Delete temporary file
        @unlink($tempPath);

        return $media->getPath();
    }

    /**
     * Generate monthly summary PDF.
     */
    public function generateMonthlySummary(int $driverId, int $year, int $month): string
    {
        $driver = UserDriverDetail::with(['user', 'carrier', 'primaryLicense'])->findOrFail($driverId);
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get driver's signature from their most recent completed trip
        $signatureData = null;
        $completedTrip = Trip::where('user_driver_detail_id', $driverId)
            ->whereNotNull('post_trip_driver_signature')
            ->orderBy('updated_at', 'desc')
            ->first();
        
        if ($completedTrip && $completedTrip->post_trip_driver_signature) {
            $signatureData = $completedTrip->post_trip_driver_signature;
        }

        // Recalculate daily logs from HosEntry data to ensure accuracy
        $calculationService = app(HosCalculationService::class);
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $calculationService->recalculateDailyLog($driverId, $current);
            $current->addDay();
        }

        // Get all daily logs for the month (now freshly recalculated)
        $dailyLogs = \App\Models\Hos\HosDailyLog::forDriver($driverId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('date')
            ->get();

        // Calculate monthly totals
        $monthlyTotals = [
            'total_driving_minutes' => $dailyLogs->sum('total_driving_minutes'),
            'total_on_duty_minutes' => $dailyLogs->sum('total_on_duty_minutes'),
            'total_off_duty_minutes' => $dailyLogs->sum('total_off_duty_minutes'),
            'days_worked' => $dailyLogs->where('total_driving_minutes', '>', 0)->count(),
            'days_with_violations' => $dailyLogs->where('has_violations', true)->count(),
        ];

        // Get all violations for the month
        $violations = \App\Models\Hos\HosViolation::where('user_driver_detail_id', $driverId)
            ->whereBetween('violation_date', [$startDate, $endDate])
            ->orderBy('violation_date')
            ->get();

        // Get weekly cycle service for breakdown
        $weeklyCycleService = app(\App\Services\Hos\HosWeeklyCycleService::class);
        $weeklyBreakdown = [];
        
        // Calculate weekly totals (group by week)
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();
            
            $weekLogs = $dailyLogs->filter(function ($log) use ($weekStart, $weekEnd) {
                return $log->date >= $weekStart && $log->date <= $weekEnd;
            });
            
            $weeklyBreakdown[] = [
                'week_start' => $weekStart->format('M d'),
                'week_end' => $weekEnd->format('M d'),
                'total_driving_minutes' => $weekLogs->sum('total_driving_minutes'),
                'total_duty_minutes' => $weekLogs->sum('total_driving_minutes') + $weekLogs->sum('total_on_duty_minutes'),
            ];
            
            $currentDate->addWeek();
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.monthly-summary', [
            'driver' => $driver,
            'year' => $year,
            'month' => $month,
            'monthName' => $startDate->format('F Y'),
            'dailyLogs' => $dailyLogs,
            'monthlyTotals' => $monthlyTotals,
            'weeklyBreakdown' => $weeklyBreakdown,
            'violations' => $violations,
            'signatureData' => $signatureData,
            'generatedAt' => now(),
        ]);


        // Generate filename
        $filename = sprintf(
            'monthly_summary_%04d-%02d_%s.pdf',
            $year,
            $month,
            now()->format('YmdHis')
        );

        // Create temporary file
        $tempPath = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $pdf->output());

        // Attach to driver using Media Library (CustomPathGenerator will handle the path)
        $media = $driver->addMedia($tempPath)
            ->withCustomProperties([
                'year_month' => sprintf('%04d-%02d', $year, $month),
                'carrier_id' => $driver->carrier_id,
            ])
            ->toMediaCollection('monthly_summaries');

        // Delete temporary file
        @unlink($tempPath);

        return $media->getPath();
    }

    /**
     * Get or generate daily log PDF.
     */
    public function getOrGenerateDailyLogPdf(int $driverId, Carbon $date): string
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        
        // Check if PDF already exists
        $existingPdf = $driver->getDailyLogPdf($date);

        if ($existingPdf) {
            return $existingPdf->getPath();
        }

        // Generate new PDF
        return $this->generateDailyLog($driverId, $date);
    }

    /**
     * Generate Document Monthly PDF (FMCSA Intermittent Driver format).
     * 
     * This format matches the paper form for drivers who operate within
     * 100/150 air-mile radius and return to headquarters daily.
     */
    public function generateDocumentMonthly(int $driverId, int $year, int $month): string
    {
        $driver = UserDriverDetail::with(['user', 'carrier', 'primaryLicense'])->findOrFail($driverId);
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $endDate->day;

        // Get headquarters from carrier
        $carrier = $driver->carrier;
        $headquarters = '';
        if ($carrier) {
            // Use headquarters field if available, otherwise extract from address
            if (!empty($carrier->headquarters)) {
                $headquarters = $carrier->headquarters;
            } else {
                $city = $this->extractCityFromAddress($carrier->address ?? '');
                $state = $carrier->state ?? '';
                $headquarters = trim("{$city}, {$state}", ', ');
            }
        }

        // Get all HOS entries for the month
        $hosEntries = \App\Models\Hos\HosEntry::where('user_driver_detail_id', $driverId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('vehicle')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Build daily data array
        $dailyData = [];
        $totalDrivingMinutes = 0;
        $totalOnDutyMinutes = 0;
        $totalOffDutyMinutes = 0;
        $daysWorked = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dayEntries = $hosEntries->filter(function ($entry) use ($currentDate) {
                return $entry->date->isSameDay($currentDate);
            });

            if ($dayEntries->isEmpty()) {
                continue;
            }

            $daysWorked++;
            
            // Calculate times and hours for the day
            $dayDrivingMinutes = 0;
            $dayOnDutyMinutes = 0;
            $dayOffDutyMinutes = 0;
            $earliestStart = null;
            $latestOnDutyEnd = null;
            $truckNumbers = [];

            foreach ($dayEntries as $entry) {
                $minutes = $entry->duration_minutes;
                
                if ($entry->status === \App\Models\Hos\HosEntry::STATUS_ON_DUTY_DRIVING) {
                    $dayDrivingMinutes += $minutes;
                    $dayOnDutyMinutes += $minutes;
                } elseif ($entry->status === \App\Models\Hos\HosEntry::STATUS_ON_DUTY_NOT_DRIVING) {
                    $dayOnDutyMinutes += $minutes;
                } else {
                    $dayOffDutyMinutes += $minutes;
                }

                // Track earliest start and latest end for on-duty statuses (not off_duty)
                if ($entry->status !== 'off_duty') {
                    if ($earliestStart === null || $entry->start_time->lt($earliestStart)) {
                        $earliestStart = $entry->start_time;
                    }
                    $entryEnd = $entry->end_time ?? now();
                    if ($latestOnDutyEnd === null || $entryEnd->gt($latestOnDutyEnd)) {
                        $latestOnDutyEnd = $entryEnd;
                    }
                }

                // Track vehicle numbers
                if ($entry->vehicle && $entry->vehicle->company_unit_number) {
                    $truckNumbers[$entry->vehicle->company_unit_number] = true;
                }
            }

            $totalDrivingMinutes += $dayDrivingMinutes;
            $totalOnDutyMinutes += $dayOnDutyMinutes;
            $totalOffDutyMinutes += $dayOffDutyMinutes;

            $dailyData[$day] = [
                'start_time' => $earliestStart ? $earliestStart->format('H:i') : '',
                'end_time' => $latestOnDutyEnd ? $latestOnDutyEnd->format('H:i') : '',
                'total_hours' => $this->formatMinutesToHours($dayOnDutyMinutes),
                'driving_hours' => $this->formatMinutesToHours($dayDrivingMinutes),
                'off_duty_hours' => $this->formatMinutesToHours($dayOffDutyMinutes),
                'truck_number' => implode(', ', array_keys($truckNumbers)),
                'headquarters' => $headquarters,
            ];
        }

        // Calculate totals
        $totals = [
            'total_hours' => $this->formatMinutesToHours($totalOnDutyMinutes),
            'driving_hours' => $this->formatMinutesToHours($totalDrivingMinutes),
            'off_duty_hours' => $this->formatMinutesToHours($totalOffDutyMinutes),
        ];

        // Calculate summary
        $summary = [
            'days_worked' => $daysWorked,
            'total_driving_formatted' => $this->formatMinutesToHoursLong($totalDrivingMinutes),
            'total_on_duty_formatted' => $this->formatMinutesToHoursLong($totalOnDutyMinutes),
            'total_off_duty_formatted' => $this->formatMinutesToHoursLong($totalOffDutyMinutes),
            'avg_daily_hours' => $daysWorked > 0 
                ? $this->formatMinutesToHoursLong(intval($totalOnDutyMinutes / $daysWorked))
                : '0h 0m',
        ];

        // Get driver's signature from their most recent completed trip
        $signature = null;
        $signedAt = null;
        $completedTrip = Trip::where('user_driver_detail_id', $driverId)
            ->whereNotNull('post_trip_driver_signature')
            ->orderBy('updated_at', 'desc')
            ->first();
        
        if ($completedTrip && $completedTrip->post_trip_driver_signature) {
            $signature = $completedTrip->post_trip_driver_signature;
            $signedAt = $completedTrip->updated_at->format('M d, Y H:i');
        }


        // Generate PDF
        $pdf = Pdf::loadView('pdf.document-monthly', [
            'driver' => $driver,
            'year' => $year,
            'month' => $month,
            'monthName' => $startDate->format('F Y'),
            'daysInMonth' => $daysInMonth,
            'dailyData' => $dailyData,
            'totals' => $totals,
            'summary' => $summary,
            'signatureData' => $signature, // Template expects signatureData
            'signedAt' => $signedAt,
            'generatedAt' => now(),
        ]);


        // Generate filename
        $filename = sprintf(
            'monthly_summary_%04d-%02d_%s.pdf',
            $year,
            $month,
            now()->format('YmdHis')
        );

        // Create temporary file
        $tempPath = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $pdf->output());

        // Attach to driver using Media Library
        $media = $driver->addMedia($tempPath)
            ->withCustomProperties([
                'year_month' => sprintf('%04d-%02d', $year, $month),
                'carrier_id' => $driver->carrier_id,
                'document_type' => 'fmcsa_monthly',
            ])
            ->toMediaCollection('monthly_summaries');

        // Delete temporary file
        @unlink($tempPath);

        return $media->getPath();
    }

    /**
     * Generate a violation report PDF.
     */
    public function generateViolationReport(\App\Models\Hos\HosViolation $violation): string
    {
        $violation->load(['driver.user', 'driver.carrier', 'trip', 'carrier', 'vehicle', 'forgivenByUser']);

        $driver = $violation->driver;
        $carrier = $violation->carrier ?? $driver->carrier;

        $driverName = implode(' ', array_filter([
            $driver->user->name ?? '',
            $driver->middle_name ?? '',
            $driver->last_name ?? '',
        ]));

        $forgivenByName = $violation->forgivenByUser
            ? ($violation->forgivenByUser->name ?? 'Unknown')
            : 'N/A';

        $pdf = Pdf::loadView('pdf.violation-report', [
            'violation' => $violation,
            'driver' => $driver,
            'carrier' => $carrier,
            'driverName' => $driverName,
            'forgivenByName' => $forgivenByName,
            'generatedAt' => now(),
        ]);

        $filename = sprintf(
            'violation_report_%d_%s.pdf',
            $violation->id,
            now()->format('YmdHis')
        );

        $tempPath = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $pdf->output());

        $media = $driver->addMedia($tempPath)
            ->withCustomProperties([
                'violation_id' => $violation->id,
                'document_type' => 'violation_report',
                'carrier_id' => $carrier->id ?? null,
            ])
            ->toMediaCollection('violation_reports');

        @unlink($tempPath);

        return $media->getPath();
    }

    /**
     * Extract city from address string.
     */
    protected function extractCityFromAddress(string $address): string
    {
        // Try to get the city part from the address
        // Common format: "123 Main St, City, State ZIP"
        $parts = explode(',', $address);
        
        if (count($parts) >= 2) {
            // Return the second-to-last part (usually city)
            return trim($parts[count($parts) - 2]);
        }
        
        return trim($address);
    }

    /**
     * Format minutes to short hours format (e.g., "8:30").
     */
    protected function formatMinutesToHours(int $minutes): string
    {
        if ($minutes === 0) {
            return '';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        return sprintf('%d:%02d', $hours, $mins);
    }

    /**
     * Format minutes to long hours format (e.g., "8h 30m").
     */
    protected function formatMinutesToHoursLong(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        return "{$hours}h {$mins}m";
    }
}

