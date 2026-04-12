<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\Hos\HosDailyLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;

/**
 * HOS Report Service
 * 
 * Handles all HOS-related report data retrieval and processing including:
 * - Trip reports with filters and statistics
 * - HOS daily logs reports
 * - Violations reports
 * - PDF exports for all report types
 * 
 * All methods support carrier data isolation for security.
 */
class HosReportService
{
    /**
     * Get trip report data with filters
     * 
     * @param array $filters ['carrier_id', 'driver_id', 'status', 'date_from', 'date_to', 'per_page']
     * @param int|null $carrierId Restrict to specific carrier (for carrier users)
     * @return array ['trips' => LengthAwarePaginator, 'stats' => array, 'carriers' => Collection, 'drivers' => Collection]
     */
    public function getTripReport(array $filters, ?int $carrierId = null): array
    {
        try {
            $query = Trip::with(['carrier', 'driver.user', 'vehicle', 'violations'])
                ->select('trips.*');

            // Apply carrier restriction for carrier users
            if ($carrierId !== null) {
                $query->where('carrier_id', $carrierId);
            } elseif (!empty($filters['carrier_id'])) {
                $query->where('carrier_id', $filters['carrier_id']);
            }

            // Apply driver filter
            if (!empty($filters['driver_id'])) {
                $query->where('user_driver_detail_id', $filters['driver_id']);
            }

            // Apply status filter
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply date range filter
            if (!empty($filters['date_from'])) {
                $query->whereDate('scheduled_start_date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('scheduled_start_date', '<=', $filters['date_to']);
            }

            // Get paginated results
            $perPage = $filters['per_page'] ?? 15;
            $trips = $query->orderBy('scheduled_start_date', 'desc')->paginate($perPage);

            // Calculate statistics
            $stats = $this->calculateTripStatistics($query->clone());

            // Get filter options
            $carriers = $carrierId === null ? Carrier::orderBy('name')->get() : collect();
            $drivers = $this->getDriversForFilter($carrierId, $filters['carrier_id'] ?? null);

            return [
                'trips' => $trips,
                'stats' => $stats,
                'carriers' => $carriers,
                'drivers' => $drivers,
                'filters' => $filters,
            ];
        } catch (Exception $e) {
            Log::error('Error getting trip report', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'carrier_id' => $carrierId,
            ]);
            throw new Exception('Error loading trip report');
        }
    }

    /**
     * Get trip details including HOS entries and violations
     * 
     * @param int $tripId
     * @param int|null $carrierId For authorization check
     * @return Trip|null
     */
    public function getTripDetails(int $tripId, ?int $carrierId = null): ?Trip
    {
        $query = Trip::with([
            'carrier',
            'driver.user',
            'vehicle',
            'hosEntries' => function ($q) {
                $q->orderBy('start_time', 'desc');
            },
            'violations' => function ($q) {
                $q->orderBy('violation_date', 'desc');
            },
            'gpsPoints' => function ($q) {
                $q->orderBy('recorded_at', 'desc')->limit(100);
            },
        ]);

        if ($carrierId !== null) {
            $query->where('carrier_id', $carrierId);
        }

        return $query->find($tripId);
    }

    /**
     * Export trip report to PDF
     * 
     * @param array $filters
     * @param int|null $carrierId
     * @return Response
     */
    public function exportTripReportPdf(array $filters, ?int $carrierId = null): Response
    {
        try {
            // Get all trips without pagination for PDF
            $query = Trip::with(['carrier', 'driver.user', 'vehicle'])
                ->select('trips.*');

            if ($carrierId !== null) {
                $query->where('carrier_id', $carrierId);
            } elseif (!empty($filters['carrier_id'])) {
                $query->where('carrier_id', $filters['carrier_id']);
            }

            if (!empty($filters['driver_id'])) {
                $query->where('user_driver_detail_id', $filters['driver_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('scheduled_start_date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('scheduled_start_date', '<=', $filters['date_to']);
            }

            $trips = $query->orderBy('scheduled_start_date', 'desc')->get();
            $stats = $this->calculateTripStatistics($query->clone());

            $carrier = null;
            if ($carrierId !== null) {
                $carrier = Carrier::find($carrierId);
            } elseif (!empty($filters['carrier_id'])) {
                $carrier = Carrier::find($filters['carrier_id']);
            }

            $viewName = $carrierId !== null ? 'carrier.reports.pdf.trips-pdf' : 'admin.reports.pdf.trips-pdf';

            $pdf = PDF::loadView($viewName, [
                'trips' => $trips,
                'stats' => $stats,
                'carrier' => $carrier,
                'appliedFilters' => implode(', ', $this->formatFiltersForPdf($filters)),
                'date' => now()->format('m/d/Y H:i'),
                'generatedAt' => now()->format('m/d/Y H:i:s'),
            ]);

            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

            return $pdf->download('trip_report_' . now()->format('Y-m-d_H-i') . '.pdf');
        } catch (Exception $e) {
            Log::error('Error exporting trip report PDF', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);
            throw new Exception('Error generating PDF report');
        }
    }


    /**
     * Get HOS summary report grouped by driver
     * 
     * @param array $filters ['carrier_id', 'driver_id', 'date_from', 'date_to', 'has_violations', 'per_page']
     * @param int|null $carrierId Restrict to specific carrier
     * @return array ['driverSummaries' => LengthAwarePaginator, 'stats' => array, ...]
     */
    public function getHosReport(array $filters, ?int $carrierId = null): array
    {
        try {
            // Base query with filters for statistics
            $baseQuery = HosDailyLog::query();

            if ($carrierId !== null) {
                $baseQuery->where('carrier_id', $carrierId);
            } elseif (!empty($filters['carrier_id'])) {
                $baseQuery->where('carrier_id', $filters['carrier_id']);
            }

            if (!empty($filters['driver_id'])) {
                $baseQuery->where('user_driver_detail_id', $filters['driver_id']);
            }

            if (!empty($filters['date_from'])) {
                $baseQuery->whereDate('date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $baseQuery->whereDate('date', '<=', $filters['date_to']);
            }

            if (isset($filters['has_violations'])) {
                if ($filters['has_violations'] === 'yes' || $filters['has_violations'] === true) {
                    $baseQuery->where('has_violations', true);
                } elseif ($filters['has_violations'] === 'no' || $filters['has_violations'] === false) {
                    $baseQuery->where('has_violations', false);
                }
            }

            // Calculate overall statistics
            $stats = $this->calculateHosStatistics($baseQuery->clone());

            // Build aggregated query grouped by driver
            $summaryQuery = HosDailyLog::query()
                ->select(
                    'user_driver_detail_id',
                    'carrier_id',
                    DB::raw('COUNT(*) as total_days'),
                    DB::raw('SUM(total_driving_minutes) as total_driving_minutes'),
                    DB::raw('SUM(total_on_duty_minutes) as total_on_duty_minutes'),
                    DB::raw('SUM(total_off_duty_minutes) as total_off_duty_minutes'),
                    DB::raw('SUM(CASE WHEN has_violations = 1 THEN 1 ELSE 0 END) as days_with_violations'),
                    DB::raw('MIN(date) as first_log_date'),
                    DB::raw('MAX(date) as last_log_date'),
                    DB::raw('AVG(total_driving_minutes) as avg_driving_minutes'),
                    DB::raw('AVG(total_on_duty_minutes) as avg_on_duty_minutes')
                )
                ->groupBy('user_driver_detail_id', 'carrier_id');

            // Apply same filters to summary query
            if ($carrierId !== null) {
                $summaryQuery->where('carrier_id', $carrierId);
            } elseif (!empty($filters['carrier_id'])) {
                $summaryQuery->where('carrier_id', $filters['carrier_id']);
            }

            if (!empty($filters['driver_id'])) {
                $summaryQuery->where('user_driver_detail_id', $filters['driver_id']);
            }

            if (!empty($filters['date_from'])) {
                $summaryQuery->whereDate('date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $summaryQuery->whereDate('date', '<=', $filters['date_to']);
            }

            if (isset($filters['has_violations'])) {
                if ($filters['has_violations'] === 'yes' || $filters['has_violations'] === true) {
                    $summaryQuery->where('has_violations', true);
                } elseif ($filters['has_violations'] === 'no' || $filters['has_violations'] === false) {
                    $summaryQuery->where('has_violations', false);
                }
            }

            $summaryQuery->orderBy('total_driving_minutes', 'desc');

            $perPage = $filters['per_page'] ?? 15;
            $summaries = $summaryQuery->paginate($perPage);

            // Eager load driver and carrier relationships
            $driverIds = $summaries->pluck('user_driver_detail_id')->unique();
            $carrierIds = $summaries->pluck('carrier_id')->unique();

            $driversMap = UserDriverDetail::with('user')
                ->whereIn('id', $driverIds)
                ->get()
                ->keyBy('id');

            $carriersMap = Carrier::whereIn('id', $carrierIds)
                ->get()
                ->keyBy('id');

            // Attach relationships to each summary
            $summaries->getCollection()->transform(function ($summary) use ($driversMap, $carriersMap) {
                $summary->driver = $driversMap->get($summary->user_driver_detail_id);
                $summary->carrier = $carriersMap->get($summary->carrier_id);
                return $summary;
            });

            // Get filter options
            $carriers = $carrierId === null ? Carrier::orderBy('name')->get() : collect();
            $drivers = $this->getDriversForFilter($carrierId, $filters['carrier_id'] ?? null);

            // Determine date range label
            $dateRangeLabel = $this->getDateRangeLabel($filters);

            return [
                'driverSummaries' => $summaries,
                'stats' => $stats,
                'carriers' => $carriers,
                'drivers' => $drivers,
                'filters' => $filters,
                'dateRangeLabel' => $dateRangeLabel,
            ];
        } catch (Exception $e) {
            Log::error('Error getting HOS report', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'carrier_id' => $carrierId,
            ]);
            throw new Exception('Error loading HOS report');
        }
    }

    /**
     * Get a human-readable date range label from filters
     */
    protected function getDateRangeLabel(array $filters): string
    {
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            return Carbon::parse($filters['date_from'])->format('M d, Y') . ' - ' . Carbon::parse($filters['date_to'])->format('M d, Y');
        } elseif (!empty($filters['date_from'])) {
            return 'From ' . Carbon::parse($filters['date_from'])->format('M d, Y');
        } elseif (!empty($filters['date_to'])) {
            return 'Until ' . Carbon::parse($filters['date_to'])->format('M d, Y');
        }
        return 'All Time';
    }

    /**
     * Get HOS entries for a specific daily log
     * 
     * @param int $driverId
     * @param string $date
     * @param int|null $carrierId For authorization check
     * @return Collection
     */
    public function getHosEntriesForDate(int $driverId, string $date, ?int $carrierId = null): Collection
    {
        $query = HosEntry::with(['driver.user', 'vehicle', 'trip'])
            ->where('user_driver_detail_id', $driverId)
            ->whereDate('date', $date);

        if ($carrierId !== null) {
            $query->where('carrier_id', $carrierId);
        }

        return $query->orderBy('start_time', 'asc')->get();
    }

    /**
     * Export HOS summary report to PDF
     * 
     * @param array $filters
     * @param int|null $carrierId
     * @return Response
     */
    public function exportHosReportPdf(array $filters, ?int $carrierId = null): Response
    {
        try {
            // Get the same summary data as the index
            $report = $this->getHosReport(array_merge($filters, ['per_page' => 9999]), $carrierId);

            $viewName = $carrierId !== null ? 'carrier.reports.pdf.hos-pdf' : 'admin.reports.pdf.hos-pdf';

            $viewData = [
                'driverSummaries' => $report['driverSummaries'],
                'stats' => $report['stats'],
                'filters' => $this->formatFiltersForPdf($filters),
                'date' => now()->format('m/d/Y H:i'),
                'generatedAt' => now()->format('m/d/Y H:i:s'),
                'dateRangeLabel' => $report['dateRangeLabel'],
            ];

            // Add carrier info for carrier-specific PDF
            if ($carrierId !== null) {
                $viewData['carrier'] = Carrier::find($carrierId);
                $viewData['appliedFilters'] = implode(', ', $this->formatFiltersForPdf($filters));
            }

            $pdf = PDF::loadView($viewName, $viewData);

            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

            return $pdf->download('hos_report_' . now()->format('Y-m-d_H-i') . '.pdf');
        } catch (Exception $e) {
            Log::error('Error exporting HOS report PDF', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);
            throw new Exception('Error generating PDF report');
        }
    }

    /**
     * Get violations report with filters
     * 
     * @param array $filters ['carrier_id', 'driver_id', 'violation_type', 'severity', 'date_from', 'date_to', 'acknowledged', 'per_page']
     * @param int|null $carrierId Restrict to specific carrier
     * @return array ['violations' => LengthAwarePaginator, 'stats' => array]
     */
    public function getViolationsReport(array $filters, ?int $carrierId = null): array
    {
        try {
            $query = HosViolation::with(['driver.user', 'carrier', 'trip', 'vehicle'])
                ->select('hos_violations.*');

            // Apply carrier restriction
            if ($carrierId !== null) {
                $query->where('carrier_id', $carrierId);
            } elseif (!empty($filters['carrier_id'])) {
                $query->where('carrier_id', $filters['carrier_id']);
            }

            // Apply driver filter
            if (!empty($filters['driver_id'])) {
                $query->where('user_driver_detail_id', $filters['driver_id']);
            }

            // Apply violation type filter
            if (!empty($filters['violation_type'])) {
                $query->where('violation_type', $filters['violation_type']);
            }

            // Apply severity filter
            if (!empty($filters['severity'])) {
                $query->where('violation_severity', $filters['severity']);
            }

            // Apply date range filter
            if (!empty($filters['date_from'])) {
                $query->whereDate('violation_date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('violation_date', '<=', $filters['date_to']);
            }

            // Apply acknowledgment status filter
            if (isset($filters['acknowledged'])) {
                if ($filters['acknowledged'] === 'yes' || $filters['acknowledged'] === true) {
                    $query->where('acknowledged', true);
                } elseif ($filters['acknowledged'] === 'no' || $filters['acknowledged'] === false) {
                    $query->where('acknowledged', false);
                }
            }

            // Get paginated results
            $perPage = $filters['per_page'] ?? 15;
            $violations = $query->orderBy('violation_date', 'desc')->paginate($perPage);

            // Calculate statistics
            $stats = $this->calculateViolationsStatistics($query->clone());

            // Get filter options
            $carriers = $carrierId === null ? Carrier::orderBy('name')->get() : collect();
            $drivers = $this->getDriversForFilter($carrierId, $filters['carrier_id'] ?? null);

            return [
                'violations' => $violations,
                'stats' => $stats,
                'carriers' => $carriers,
                'drivers' => $drivers,
                'filters' => $filters,
                'violationTypes' => HosViolation::VIOLATION_TYPES,
                'severities' => HosViolation::SEVERITIES,
            ];
        } catch (Exception $e) {
            Log::error('Error getting violations report', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'carrier_id' => $carrierId,
            ]);
            throw new Exception('Error loading violations report');
        }
    }

    /**
     * Get violation details
     * 
     * @param int $violationId
     * @param int|null $carrierId For authorization check
     * @return HosViolation|null
     */
    public function getViolationDetails(int $violationId, ?int $carrierId = null): ?HosViolation
    {
        $query = HosViolation::with([
            'driver.user',
            'carrier',
            'vehicle',
            'trip',
            'entry',
            'acknowledgedByUser',
        ]);

        if ($carrierId !== null) {
            $query->where('carrier_id', $carrierId);
        }

        return $query->find($violationId);
    }

    /**
     * Export violations report to PDF
     * 
     * @param array $filters
     * @param int|null $carrierId
     * @return Response
     */
    public function exportViolationsReportPdf(array $filters, ?int $carrierId = null): Response
    {
        try {
            $query = HosViolation::with(['driver.user', 'carrier', 'trip'])
                ->select('hos_violations.*');

            if ($carrierId !== null) {
                $query->where('carrier_id', $carrierId);
            } elseif (!empty($filters['carrier_id'])) {
                $query->where('carrier_id', $filters['carrier_id']);
            }

            if (!empty($filters['driver_id'])) {
                $query->where('user_driver_detail_id', $filters['driver_id']);
            }

            if (!empty($filters['violation_type'])) {
                $query->where('violation_type', $filters['violation_type']);
            }

            if (!empty($filters['severity'])) {
                $query->where('violation_severity', $filters['severity']);
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('violation_date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('violation_date', '<=', $filters['date_to']);
            }

            if (isset($filters['acknowledged'])) {
                if ($filters['acknowledged'] === 'yes' || $filters['acknowledged'] === true) {
                    $query->where('acknowledged', true);
                } elseif ($filters['acknowledged'] === 'no' || $filters['acknowledged'] === false) {
                    $query->where('acknowledged', false);
                }
            }

            $violations = $query->orderBy('violation_date', 'desc')->get();
            $stats = $this->calculateViolationsStatistics($query->clone());

            $viewName = $carrierId !== null ? 'carrier.reports.pdf.violations-pdf' : 'admin.reports.pdf.violations-pdf';

            $viewData = [
                'violations' => $violations,
                'stats' => $stats,
                'filters' => $this->formatFiltersForPdf($filters),
                'date' => now()->format('m/d/Y H:i'),
                'generatedAt' => now()->format('m/d/Y H:i:s'),
            ];

            if ($carrierId !== null) {
                $viewData['carrier'] = Carrier::find($carrierId);
            }

            $pdf = PDF::loadView($viewName, $viewData);

            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

            return $pdf->download('violations_report_' . now()->format('Y-m-d_H-i') . '.pdf');
        } catch (Exception $e) {
            Log::error('Error exporting violations report PDF', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);
            throw new Exception('Error generating PDF report');
        }
    }


    /**
     * Calculate trip statistics from query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    protected function calculateTripStatistics($query): array
    {
        $trips = $query->get();
        
        $totalTrips = $trips->count();
        $completedTrips = $trips->where('status', Trip::STATUS_COMPLETED)->count();
        $cancelledTrips = $trips->where('status', Trip::STATUS_CANCELLED)->count();
        $inProgressTrips = $trips->where('status', Trip::STATUS_IN_PROGRESS)->count();
        $tripsWithViolations = $trips->where('has_violations', true)->count();
        
        // Calculate average duration for completed trips
        $completedWithDuration = $trips->where('status', Trip::STATUS_COMPLETED)
            ->whereNotNull('actual_duration_minutes');
        $averageDuration = $completedWithDuration->count() > 0 
            ? $completedWithDuration->avg('actual_duration_minutes') 
            : 0;

        return [
            'total_trips' => $totalTrips,
            'completed_trips' => $completedTrips,
            'cancelled_trips' => $cancelledTrips,
            'in_progress_trips' => $inProgressTrips,
            'trips_with_violations' => $tripsWithViolations,
            'average_duration_minutes' => round($averageDuration, 2),
            'completion_rate' => $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 1) : 0,
        ];
    }

    /**
     * Calculate HOS statistics from query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    protected function calculateHosStatistics($query): array
    {
        $logs = $query->get();
        
        $totalLogs = $logs->count();
        $logsWithViolations = $logs->where('has_violations', true)->count();
        $compliancePercentage = $totalLogs > 0 
            ? round((($totalLogs - $logsWithViolations) / $totalLogs) * 100, 1) 
            : 100;
        
        $averageDrivingMinutes = $logs->avg('total_driving_minutes') ?? 0;
        $averageOnDutyMinutes = $logs->avg('total_on_duty_minutes') ?? 0;

        return [
            'total_logs' => $totalLogs,
            'logs_with_violations' => $logsWithViolations,
            'compliance_percentage' => $compliancePercentage,
            'average_driving_hours' => round($averageDrivingMinutes / 60, 2),
            'average_on_duty_hours' => round($averageOnDutyMinutes / 60, 2),
        ];
    }

    /**
     * Calculate violations statistics from query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    protected function calculateViolationsStatistics($query): array
    {
        $violations = $query->get();
        
        $totalViolations = $violations->count();
        $acknowledgedCount = $violations->where('acknowledged', true)->count();
        $unacknowledgedCount = $totalViolations - $acknowledgedCount;
        
        // Count by type
        $byType = [];
        foreach (HosViolation::VIOLATION_TYPES as $type) {
            $byType[$type] = $violations->where('violation_type', $type)->count();
        }
        
        // Count by severity
        $bySeverity = [];
        foreach (HosViolation::SEVERITIES as $severity) {
            $bySeverity[$severity] = $violations->where('violation_severity', $severity)->count();
        }
        
        $acknowledgmentRate = $totalViolations > 0 
            ? round(($acknowledgedCount / $totalViolations) * 100, 1) 
            : 0;

        return [
            'total_violations' => $totalViolations,
            'by_type' => $byType,
            'by_severity' => $bySeverity,
            'acknowledged_count' => $acknowledgedCount,
            'unacknowledged_count' => $unacknowledgedCount,
            'acknowledgment_rate' => $acknowledgmentRate,
        ];
    }

    /**
     * Get drivers for filter dropdown
     * 
     * @param int|null $carrierId
     * @param int|null $filterCarrierId
     * @return Collection
     */
    protected function getDriversForFilter(?int $carrierId, string|int|null $filterCarrierId): Collection
    {
        $filterCarrierId = ($filterCarrierId !== null && $filterCarrierId !== '') ? (int) $filterCarrierId : null;

        $query = UserDriverDetail::with('user')
            ->where('status', UserDriverDetail::STATUS_ACTIVE);

        if ($carrierId !== null) {
            $query->where('carrier_id', $carrierId);
        } elseif ($filterCarrierId !== null) {
            $query->where('carrier_id', $filterCarrierId);
        }

        return $query->get()->map(function ($driver) {
            return [
                'id' => $driver->id,
                'name' => $driver->full_name ?? 'Unknown',
            ];
        });
    }

    /**
     * Format filters for PDF display
     * 
     * @param array $filters
     * @return array
     */
    protected function formatFiltersForPdf(array $filters): array
    {
        $formatted = [];

        if (!empty($filters['carrier_id'])) {
            $carrier = Carrier::find($filters['carrier_id']);
            $formatted[] = 'Carrier: ' . ($carrier ? $carrier->name : 'Unknown');
        }

        if (!empty($filters['driver_id'])) {
            $driver = UserDriverDetail::with('user')->find($filters['driver_id']);
            $formatted[] = 'Driver: ' . ($driver ? $driver->full_name : 'Unknown');
        }

        if (!empty($filters['status'])) {
            $formatted[] = 'Status: ' . ucfirst(str_replace('_', ' ', $filters['status']));
        }

        if (!empty($filters['violation_type'])) {
            $formatted[] = 'Violation Type: ' . ucfirst(str_replace('_', ' ', $filters['violation_type']));
        }

        if (!empty($filters['severity'])) {
            $formatted[] = 'Severity: ' . ucfirst($filters['severity']);
        }

        if (!empty($filters['date_from'])) {
            $formatted[] = 'From: ' . Carbon::parse($filters['date_from'])->format('m/d/Y');
        }

        if (!empty($filters['date_to'])) {
            $formatted[] = 'To: ' . Carbon::parse($filters['date_to'])->format('m/d/Y');
        }

        if (isset($filters['has_violations'])) {
            $formatted[] = 'Compliance: ' . ($filters['has_violations'] === 'yes' ? 'With Violations' : 'Compliant');
        }

        if (isset($filters['acknowledged'])) {
            $formatted[] = 'Acknowledged: ' . ($filters['acknowledged'] === 'yes' ? 'Yes' : 'No');
        }

        return $formatted;
    }
}
