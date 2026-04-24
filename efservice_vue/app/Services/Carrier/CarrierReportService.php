<?php

namespace App\Services\Carrier;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\EmergencyRepair;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Carrier Report Service
 * 
 * Handles report generation, filtering, and caching for carrier-specific reports.
 * All queries are automatically filtered by carrier_id to ensure data isolation.
 */
class CarrierReportService
{
    /**
     * Cache time-to-live in seconds (10 minutes)
     */
    private const CACHE_TTL = 600;
    
    /**
     * Cache key prefix for carrier reports
     */
    private const CACHE_PREFIX = 'carrier_reports:';
    
    /**
     * Default date range in days when no dates are specified
     */
    private const DEFAULT_DATE_RANGE_DAYS = 30;

    /**
     * Get dashboard metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    public function getDashboardMetrics(int $carrierId): array
    {
        $cacheKey = self::CACHE_PREFIX . "dashboard:{$carrierId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($carrierId) {
            try {
                return [
                    'drivers' => $this->getDriverMetrics($carrierId),
                    'vehicles' => $this->getVehicleMetrics($carrierId),
                    'accidents' => $this->getAccidentMetrics($carrierId),
                    'medical_records' => $this->getMedicalRecordMetrics($carrierId),
                    'licenses' => $this->getLicenseMetrics($carrierId),
                    'maintenance' => $this->getMaintenanceMetrics($carrierId),
                    'repairs' => $this->getRepairMetrics($carrierId),
                ];
            } catch (Exception $e) {
                Log::error('Error generating dashboard metrics', [
                    'carrier_id' => $carrierId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new Exception('Error generating dashboard metrics: ' . $e->getMessage());
            }
        });
    }

    /**
     * Apply date range filter to a query
     * 
     * Applies date filtering based on provided filters:
     * - If no dates provided: defaults to last 30 days
     * - If only date_from: from that date to now
     * - If only date_to: from beginning to that date
     * - If both: within the specified range (inclusive)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param string $dateColumn The column name to filter on (default: 'created_at')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyDateRangeFilter($query, array $filters, string $dateColumn = 'created_at')
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        // If no date range is specified, default to last 30 days
        if (empty($dateFrom) && empty($dateTo)) {
            $query->where($dateColumn, '>=', Carbon::now()->subDays(self::DEFAULT_DATE_RANGE_DAYS));
        } else {
            // Apply start date filter if provided
            if (!empty($dateFrom)) {
                $query->whereDate($dateColumn, '>=', $dateFrom);
            }
            
            // Apply end date filter if provided
            if (!empty($dateTo)) {
                $query->whereDate($dateColumn, '<=', $dateTo);
            }
        }
        
        return $query;
    }

    /**
     * Cache report data with a specific key and TTL
     * 
     * @param string $key
     * @param mixed $data
     * @param int|null $ttl Time-to-live in seconds (null uses default)
     * @return mixed
     */
    protected function cacheReport(string $key, $data, ?int $ttl = null)
    {
        $ttl = $ttl ?? self::CACHE_TTL;
        $cacheKey = self::CACHE_PREFIX . $key;
        
        Cache::put($cacheKey, $data, $ttl);
        
        return $data;
    }

    /**
     * Invalidate cache for a specific carrier
     * 
     * @param int $carrierId
     * @return void
     */
    public function invalidateCarrierCache(int $carrierId): void
    {
        try {
            // Clear dashboard cache
            Cache::forget(self::CACHE_PREFIX . "dashboard:{$carrierId}");
            
            Log::info('Carrier cache invalidated', [
                'carrier_id' => $carrierId
            ]);
        } catch (Exception $e) {
            Log::error('Error invalidating carrier cache', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get driver metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    protected function getDriverMetrics(int $carrierId): array
    {
        try {
            $total = UserDriverDetail::where('carrier_id', $carrierId)->count();
            $active = UserDriverDetail::where('carrier_id', $carrierId)
                ->where('status', UserDriverDetail::STATUS_ACTIVE)
                ->count();
            $recent = UserDriverDetail::where('carrier_id', $carrierId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();

            return [
                'total' => $total,
                'active' => $active,
                'inactive' => $total - $active,
                'recent' => $recent,
                'percentage_active' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            ];
        } catch (Exception $e) {
            Log::error('Error getting driver metrics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'recent' => 0,
                'percentage_active' => 0,
            ];
        }
    }

    /**
     * Get vehicle metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    protected function getVehicleMetrics(int $carrierId): array
    {
        try {
            $total = Vehicle::where('carrier_id', $carrierId)->count();
            $active = Vehicle::where('carrier_id', $carrierId)
                ->where('status', 'active')
                ->count();
            $recent = Vehicle::where('carrier_id', $carrierId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();

            return [
                'total' => $total,
                'active' => $active,
                'out_of_service' => $total - $active,
                'recent' => $recent,
                'percentage_active' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            ];
        } catch (Exception $e) {
            Log::error('Error getting vehicle metrics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'active' => 0,
                'out_of_service' => 0,
                'recent' => 0,
                'percentage_active' => 0,
            ];
        }
    }

    /**
     * Get accident metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    protected function getAccidentMetrics(int $carrierId): array
    {
        try {
            $total = DriverAccident::whereHas('driver', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->count();
            
            $recent = DriverAccident::whereHas('driver', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->where('accident_date', '>=', Carbon::now()->subDays(30))
            ->count();

            return [
                'total' => $total,
                'recent' => $recent,
            ];
        } catch (Exception $e) {
            Log::error('Error getting accident metrics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'recent' => 0,
            ];
        }
    }

    /**
     * Get license metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    protected function getLicenseMetrics(int $carrierId): array
    {
        try {
            $total = DriverLicense::whereHas('driverDetail', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->count();
            
            $expiringSoon = DriverLicense::whereHas('driverDetail', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->whereBetween('expiration_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->count();
            
            $expired = DriverLicense::whereHas('driverDetail', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->where('expiration_date', '<', Carbon::now())
            ->count();
            
            $valid = $total - $expired;
            $percentageValid = $total > 0 ? round(($valid / $total) * 100, 1) : 0;

            return [
                'total' => $total,
                'expiring_soon' => $expiringSoon,
                'expired' => $expired,
                'valid' => $valid,
                'percentage_valid' => $percentageValid,
            ];
        } catch (Exception $e) {
            Log::error('Error getting license metrics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'expiring_soon' => 0,
                'expired' => 0,
                'valid' => 0,
                'percentage_valid' => 0,
            ];
        }
    }

    /**
     * Get maintenance metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    protected function getMaintenanceMetrics(int $carrierId): array
    {
        try {
            $total = VehicleMaintenance::where('carrier_id', $carrierId)->count();
            $recent = VehicleMaintenance::where('carrier_id', $carrierId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
            $totalCost = VehicleMaintenance::where('carrier_id', $carrierId)
                ->sum('cost') ?? 0;

            return [
                'total' => $total,
                'recent' => $recent,
                'total_cost' => $totalCost,
                'average_cost' => $total > 0 ? round($totalCost / $total, 2) : 0,
            ];
        } catch (Exception $e) {
            Log::error('Error getting maintenance metrics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'recent' => 0,
                'total_cost' => 0,
                'average_cost' => 0,
            ];
        }
    }

    /**
     * Get repair metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    protected function getRepairMetrics(int $carrierId): array
    {
        try {
            $total = EmergencyRepair::where('carrier_id', $carrierId)->count();
            $recent = EmergencyRepair::where('carrier_id', $carrierId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
            $totalCost = EmergencyRepair::where('carrier_id', $carrierId)
                ->sum('cost') ?? 0;

            return [
                'total' => $total,
                'recent' => $recent,
                'total_cost' => $totalCost,
                'average_cost' => $total > 0 ? round($totalCost / $total, 2) : 0,
            ];
        } catch (Exception $e) {
            Log::error('Error getting repair metrics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'recent' => 0,
                'total_cost' => 0,
                'average_cost' => 0,
            ];
        }
    }

    /**
     * Get driver report with filters
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getDriverReport(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            $query = UserDriverDetail::with(['user', 'primaryLicense', 'application'])
                ->where('carrier_id', $carrierId);
            
            // Apply filters
            $query = $this->applyDriverFilters($query, $filters);
            
            // Only apply date filter if explicitly provided (don't use default 30 days for drivers)
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }
            
            $perPage = $filters['per_page'] ?? 10;
            $drivers = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            // Add expiring license indicator to each driver
            $drivers->getCollection()->transform(function ($driver) {
                $driver->has_expiring_license = $this->hasExpiringLicense($driver);
                return $driver;
            });
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in driver report', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Driver report generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'result_count' => $drivers->total(),
            ]);
            
            return [
                'drivers' => $drivers,
                'filters' => $filters,
                'stats' => $this->getDriverMetrics($carrierId),
            ];
        } catch (Exception $e) {
            Log::error('Error generating driver report', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating driver report: ' . $e->getMessage());
        }
    }

    /**
     * Apply driver-specific filters to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyDriverFilters($query, array $filters)
    {
        // Search filter (name, email, phone)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('middle_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $statusMap = [
                'active' => UserDriverDetail::STATUS_ACTIVE,
                'inactive' => UserDriverDetail::STATUS_INACTIVE,
                'pending' => UserDriverDetail::STATUS_PENDING,
            ];
            
            if (isset($statusMap[$filters['status']])) {
                $query->where('status', $statusMap[$filters['status']]);
            }
        }
        
        return $query;
    }

    /**
     * Check if a driver has a license expiring within 30 days
     * 
     * @param UserDriverDetail $driver
     * @return bool
     */
    protected function hasExpiringLicense(UserDriverDetail $driver): bool
    {
        if (!$driver->primaryLicense) {
            return false;
        }
        
        $expirationDate = $driver->primaryLicense->expiration_date;
        if (!$expirationDate) {
            return false;
        }
        
        $now = Carbon::now();
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        
        return $expirationDate->between($now, $thirtyDaysFromNow);
    }

    /**
     * Export driver report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportDriverReportPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Build query with filters
            $query = UserDriverDetail::with(['user', 'primaryLicense', 'application'])
                ->where('carrier_id', $carrierId);
            
            // Apply filters
            $query = $this->applyDriverFilters($query, $filters);
            
            // Only apply date filter if explicitly provided (don't use default 30-day filter)
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }
            
            // Get all drivers (no pagination for PDF)
            $drivers = $query->orderBy('created_at', 'desc')->get();
            
            // Add expiring license indicator to each driver
            $drivers->transform(function ($driver) {
                $driver->has_expiring_license = $this->hasExpiringLicense($driver);
                return $driver;
            });
            
            // Get statistics
            $stats = $this->getDriverMetrics($carrierId);
            
            // Prepare data for PDF
            $data = [
                'drivers' => $drivers,
                'carrier' => $carrier,
                'filters' => $filters,
                'stats' => $stats,
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'total_drivers' => $drivers->count(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.drivers', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                    'driver_count' => $drivers->count(),
                ]);
            }
            
            Log::info('Driver report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'driver_count' => $drivers->count(),
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "drivers_report_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting driver report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting driver report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get vehicle report with filters
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getVehicleReport(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            $query = Vehicle::with(['driver', 'vehicleMake', 'vehicleType', 'currentDriverAssignment'])
                ->where('carrier_id', $carrierId);

            // Apply filters
            $query = $this->applyVehicleFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }

            $perPage = $filters['per_page'] ?? 10;
            $vehicles = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            // Add expiring registration indicator to each vehicle
            $vehicles->getCollection()->transform(function ($vehicle) {
                $vehicle->has_expiring_registration = $this->hasExpiringRegistration($vehicle);
                return $vehicle;
            });
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in vehicle report', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Vehicle report generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'result_count' => $vehicles->total(),
            ]);
            
            return [
                'vehicles' => $vehicles,
                'filters' => $filters,
                'stats' => $this->getVehicleMetrics($carrierId),
            ];
        } catch (Exception $e) {
            Log::error('Error generating vehicle report', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating vehicle report: ' . $e->getMessage());
        }
    }

    /**
     * Apply vehicle-specific filters to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyVehicleFilters($query, array $filters)
    {
        // Search filter (VIN, unit number, make, model)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                  ->orWhere('company_unit_number', 'like', "%{$search}%")
                  ->orWhere('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            
            if ($status === 'active') {
                $query->where('out_of_service', false)
                      ->where('suspended', false);
            } elseif ($status === 'out_of_service') {
                $query->where('out_of_service', true);
            } elseif ($status === 'suspended') {
                $query->where('suspended', true);
            }
        }
        
        return $query;
    }

    /**
     * Check if a vehicle has registration expiring within 30 days
     * 
     * @param Vehicle $vehicle
     * @return bool
     */
    protected function hasExpiringRegistration(Vehicle $vehicle): bool
    {
        if (!$vehicle->registration_expiration_date) {
            return false;
        }
        
        $now = Carbon::now();
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        
        return $vehicle->registration_expiration_date->between($now, $thirtyDaysFromNow);
    }

    /**
     * Export vehicle report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportVehicleReportPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Build query with filters
            $query = Vehicle::with(['driver', 'vehicleMake', 'vehicleType', 'currentDriverAssignment'])
                ->where('carrier_id', $carrierId);

            // Apply filters
            $query = $this->applyVehicleFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }

            // Get all vehicles (no pagination for PDF)
            $vehicles = $query->orderBy('created_at', 'desc')->get();
            
            // Add expiring registration indicator to each vehicle
            $vehicles->transform(function ($vehicle) {
                $vehicle->has_expiring_registration = $this->hasExpiringRegistration($vehicle);
                return $vehicle;
            });
            
            // Get statistics
            $stats = $this->getVehicleMetrics($carrierId);
            
            // Prepare data for PDF
            $data = [
                'vehicles' => $vehicles,
                'carrier' => $carrier,
                'filters' => $filters,
                'stats' => $stats,
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'total_vehicles' => $vehicles->count(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.vehicles', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                    'vehicle_count' => $vehicles->count(),
                ]);
            }
            
            Log::info('Vehicle report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'vehicle_count' => $vehicles->count(),
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "vehicles_report_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting vehicle report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting vehicle report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get accident report with filters
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getAccidentReport(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            $query = DriverAccident::with(['userDriverDetail.user'])
                ->whereHas('userDriverDetail', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });

            // Apply filters
            $query = $this->applyAccidentFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters, 'accident_date');
            }

            $perPage = $filters['per_page'] ?? 10;
            $accidents = $query->orderBy('accident_date', 'desc')->paginate($perPage);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in accident report', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Accident report generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'result_count' => $accidents->total(),
            ]);
            
            return [
                'accidents' => $accidents,
                'filters' => $filters,
                'stats' => $this->getAccidentStatistics($carrierId, $filters),
            ];
        } catch (Exception $e) {
            Log::error('Error generating accident report', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating accident report: ' . $e->getMessage());
        }
    }

    /**
     * Apply accident-specific filters to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyAccidentFilters($query, array $filters)
    {
        // Search filter (nature of accident, comments)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nature_of_accident', 'like', "%{$search}%")
                  ->orWhere('comments', 'like', "%{$search}%");
            });
        }
        
        // Driver filter
        if (!empty($filters['driver'])) {
            $query->where('user_driver_detail_id', $filters['driver']);
        }
        
        return $query;
    }

    /**
     * Get accident statistics for a carrier
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    protected function getAccidentStatistics(int $carrierId, array $filters): array
    {
        try {
            // Base query for carrier's accidents
            $baseQuery = DriverAccident::whereHas('userDriverDetail', function ($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            });
            
            // Apply the same filters as the main report
            $filteredQuery = clone $baseQuery;
            $filteredQuery = $this->applyAccidentFilters($filteredQuery, $filters);
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $filteredQuery = $this->applyDateRangeFilter($filteredQuery, $filters, 'accident_date');
            }
            
            // Total accidents (with filters applied)
            $total = $filteredQuery->count();
            
            // Recent accidents (last 30 days, with filters applied)
            $recentQuery = clone $filteredQuery;
            $recent = $recentQuery->where('accident_date', '>=', Carbon::now()->subDays(30))->count();
            
            // Accidents by severity (with filters applied)
            $withFatalities = (clone $filteredQuery)->where('had_fatalities', true)->count();
            $withInjuries = (clone $filteredQuery)
                ->where('had_injuries', true)
                ->where('had_fatalities', false)
                ->count();
            $withoutInjuries = $total - $withFatalities - $withInjuries;
            
            return [
                'total' => $total,
                'recent' => $recent,
                'with_fatalities' => $withFatalities,
                'with_injuries' => $withInjuries,
                'without_injuries' => $withoutInjuries,
            ];
        } catch (Exception $e) {
            Log::error('Error calculating accident statistics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'recent' => 0,
                'with_fatalities' => 0,
                'with_injuries' => 0,
                'without_injuries' => 0,
            ];
        }
    }

    /**
     * Export accident report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportAccidentReportPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Build query with filters
            $query = DriverAccident::with(['userDriverDetail.user'])
                ->whereHas('userDriverDetail', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });

            // Apply filters
            $query = $this->applyAccidentFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters, 'accident_date');
            }

            // Get all accidents (no pagination for PDF)
            $accidents = $query->orderBy('accident_date', 'desc')->get();
            
            // Get statistics
            $stats = $this->getAccidentStatistics($carrierId, $filters);
            
            // Prepare data for PDF
            $data = [
                'accidents' => $accidents,
                'carrier' => $carrier,
                'filters' => $filters,
                'stats' => $stats,
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'total_accidents' => $accidents->count(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.accidents', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                    'accident_count' => $accidents->count(),
                ]);
            }
            
            Log::info('Accident report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'accident_count' => $accidents->count(),
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "accidents_report_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting accident report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting accident report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get medical record metrics for a carrier
     * 
     * @param int $carrierId
     * @return array
     */
    protected function getMedicalRecordMetrics(int $carrierId): array
    {
        try {
            $total = DriverMedicalQualification::whereHas('userDriverDetail', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->count();
            
            $expiringSoon = DriverMedicalQualification::whereHas('userDriverDetail', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->whereBetween('medical_card_expiration_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->count();
            
            $expired = DriverMedicalQualification::whereHas('userDriverDetail', function ($query) use ($carrierId) {
                $query->where('carrier_id', $carrierId);
            })->where('medical_card_expiration_date', '<', Carbon::now())
            ->count();

            $valid = $total - $expired;
            $percentageValid = $total > 0 ? round(($valid / $total) * 100, 1) : 0;
            
            return [
                'total' => $total,
                'expiring_soon' => $expiringSoon,
                'expired' => $expired,
                'valid' => $valid,
                'percentage_valid' => $percentageValid,
            ];
        } catch (Exception $e) {
            Log::error('Error getting medical record metrics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'expiring_soon' => 0,
                'expired' => 0,
                'valid' => 0,
                'percentage_valid' => 0,
            ];
        }
    }

    /**
     * Get medical records report with filters
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getMedicalRecordsReport(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            $query = DriverMedicalQualification::with(['userDriverDetail.user'])
                ->whereHas('userDriverDetail', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });
            
            // Apply filters
            $query = $this->applyMedicalRecordFilters($query, $filters);
            
            // Only apply date filter if explicitly provided (don't use default 30-day filter for medical records)
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }
            
            $perPage = $filters['per_page'] ?? 10;
            $medicalRecords = $query->orderBy('medical_card_expiration_date', 'desc')->paginate($perPage);
            
            // Add expiring indicator to each medical record
            $medicalRecords->getCollection()->transform(function ($record) {
                $record->is_expiring = $this->isMedicalCardExpiring($record);
                $record->is_expired = $this->isMedicalCardExpired($record);
                return $record;
            });
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in medical records report', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Medical records report generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'result_count' => $medicalRecords->total(),
            ]);
            
            return [
                'medicalRecords' => $medicalRecords,
                'filters' => $filters,
                'stats' => $this->getMedicalRecordMetrics($carrierId),
            ];
        } catch (Exception $e) {
            Log::error('Error generating medical records report', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating medical records report: ' . $e->getMessage());
        }
    }

    /**
     * Apply medical record-specific filters to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyMedicalRecordFilters($query, array $filters)
    {
        // Search filter (medical examiner name, registry number)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('medical_examiner_name', 'like', "%{$search}%")
                  ->orWhere('medical_examiner_registry_number', 'like', "%{$search}%")
                  ->orWhereHas('userDriverDetail.user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Driver filter
        if (!empty($filters['driver'])) {
            $query->where('user_driver_detail_id', $filters['driver']);
        }
        
        // Expiration status filter
        if (!empty($filters['expiration_status'])) {
            $status = $filters['expiration_status'];
            
            if ($status === 'valid') {
                // Valid: expiration date is in the future (more than 30 days)
                $query->where('medical_card_expiration_date', '>', Carbon::now()->addDays(30));
            } elseif ($status === 'expiring_soon') {
                // Expiring soon: within 30 days
                $query->whereBetween('medical_card_expiration_date', [Carbon::now(), Carbon::now()->addDays(30)]);
            } elseif ($status === 'expired') {
                // Expired: expiration date is in the past
                $query->where('medical_card_expiration_date', '<', Carbon::now());
            }
        }
        
        return $query;
    }

    /**
     * Check if a medical card is expiring within 30 days
     * 
     * @param DriverMedicalQualification $record
     * @return bool
     */
    protected function isMedicalCardExpiring(DriverMedicalQualification $record): bool
    {
        if (!$record->medical_card_expiration_date) {
            return false;
        }
        
        $now = Carbon::now();
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        
        return $record->medical_card_expiration_date->between($now, $thirtyDaysFromNow);
    }

    /**
     * Check if a medical card is expired
     * 
     * @param DriverMedicalQualification $record
     * @return bool
     */
    protected function isMedicalCardExpired(DriverMedicalQualification $record): bool
    {
        if (!$record->medical_card_expiration_date) {
            return false;
        }
        
        return $record->medical_card_expiration_date < Carbon::now();
    }

    /**
     * Export medical records report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportMedicalRecordsReportPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Build query with filters
            $query = DriverMedicalQualification::with(['userDriverDetail.user'])
                ->whereHas('userDriverDetail', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });
            
            // Apply filters
            $query = $this->applyMedicalRecordFilters($query, $filters);
            
            // Only apply date filter if explicitly provided (don't use default 30-day filter for medical records)
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }
            
            // Get all medical records (no pagination for PDF)
            $medicalRecords = $query->orderBy('medical_card_expiration_date', 'desc')->get();
            
            // Add expiring and expired indicators to each medical record
            $medicalRecords->transform(function ($record) {
                $record->is_expiring = $this->isMedicalCardExpiring($record);
                $record->is_expired = $this->isMedicalCardExpired($record);
                return $record;
            });
            
            // Get statistics
            $stats = $this->getMedicalRecordMetrics($carrierId);
            
            // Prepare data for PDF
            $data = [
                'medicalRecords' => $medicalRecords,
                'carrier' => $carrier,
                'filters' => $filters,
                'stats' => $stats,
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'total_records' => $medicalRecords->count(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.medical-records', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                    'record_count' => $medicalRecords->count(),
                ]);
            }
            
            Log::info('Medical records report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'record_count' => $medicalRecords->count(),
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "medical_records_report_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting medical records report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting medical records report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get license report with filters
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getLicenseReport(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            $query = DriverLicense::with(['driverDetail.user', 'endorsements'])
                ->whereHas('driverDetail', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });
            
            // Apply filters
            $query = $this->applyLicenseFilters($query, $filters);
            
            // Only apply date filter if explicitly provided (don't use default 30-day filter for licenses)
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }
            
            $perPage = $filters['per_page'] ?? 10;
            $licenses = $query->orderBy('expiration_date', 'desc')->paginate($perPage);
            
            // Add expiring indicator to each license
            $licenses->getCollection()->transform(function ($license) {
                $license->is_expiring = $this->isLicenseExpiring($license);
                $license->is_expired = $this->isLicenseExpired($license);
                return $license;
            });
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in license report', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('License report generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'result_count' => $licenses->total(),
            ]);
            
            return [
                'licenses' => $licenses,
                'filters' => $filters,
                'stats' => $this->getLicenseMetrics($carrierId),
            ];
        } catch (Exception $e) {
            Log::error('Error generating license report', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating license report: ' . $e->getMessage());
        }
    }

    /**
     * Apply license-specific filters to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyLicenseFilters($query, array $filters)
    {
        // Search filter (license number, driver name)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('license_number', 'like', "%{$search}%")
                  ->orWhere('state_of_issue', 'like', "%{$search}%")
                  ->orWhereHas('driverDetail.user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Driver filter
        if (!empty($filters['driver'])) {
            $query->where('user_driver_detail_id', $filters['driver']);
        }
        
        // License type filter
        if (!empty($filters['license_type'])) {
            $type = $filters['license_type'];
            
            if ($type === 'cdl') {
                $query->where('is_cdl', true);
            } elseif ($type === 'non_cdl') {
                $query->where('is_cdl', false);
            } elseif ($type === 'primary') {
                $query->where('is_primary', true);
            }
        }
        
        // Expiration status filter
        if (!empty($filters['expiration_status'])) {
            $status = $filters['expiration_status'];
            
            if ($status === 'valid') {
                // Valid: expiration date is in the future (more than 30 days)
                $query->where('expiration_date', '>', Carbon::now()->addDays(30));
            } elseif ($status === 'expiring_soon') {
                // Expiring soon: within 30 days
                $query->whereBetween('expiration_date', [Carbon::now(), Carbon::now()->addDays(30)]);
            } elseif ($status === 'expired') {
                // Expired: expiration date is in the past
                $query->where('expiration_date', '<', Carbon::now());
            }
        }
        
        return $query;
    }

    /**
     * Check if a license is expiring within 30 days
     * 
     * @param DriverLicense $license
     * @return bool
     */
    protected function isLicenseExpiring(DriverLicense $license): bool
    {
        if (!$license->expiration_date) {
            return false;
        }
        
        $now = Carbon::now();
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        
        return $license->expiration_date->between($now, $thirtyDaysFromNow);
    }

    /**
     * Check if a license is expired
     * 
     * @param DriverLicense $license
     * @return bool
     */
    protected function isLicenseExpired(DriverLicense $license): bool
    {
        if (!$license->expiration_date) {
            return false;
        }
        
        return $license->expiration_date < Carbon::now();
    }

    /**
     * Export license report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportLicenseReportPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Build query with filters
            $query = DriverLicense::with(['driverDetail.user', 'endorsements'])
                ->whereHas('driverDetail', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });
            
            // Apply filters
            $query = $this->applyLicenseFilters($query, $filters);
            
            // Only apply date filter if explicitly provided (don't use default 30-day filter for licenses)
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters);
            }
            
            // Get all licenses (no pagination for PDF)
            $licenses = $query->orderBy('expiration_date', 'desc')->get();
            
            // Add expiring and expired indicators to each license
            $licenses->transform(function ($license) {
                $license->is_expiring = $this->isLicenseExpiring($license);
                $license->is_expired = $this->isLicenseExpired($license);
                return $license;
            });
            
            // Get statistics
            $stats = $this->getLicenseMetrics($carrierId);
            
            // Prepare data for PDF
            $data = [
                'licenses' => $licenses,
                'carrier' => $carrier,
                'filters' => $filters,
                'stats' => $stats,
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'total_licenses' => $licenses->count(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.licenses', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                    'license_count' => $licenses->count(),
                ]);
            }
            
            Log::info('License report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'license_count' => $licenses->count(),
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "licenses_report_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting license report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting license report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get maintenance report with filters
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getMaintenanceReport(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            $query = VehicleMaintenance::with(['vehicle'])
                ->whereHas('vehicle', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });

            // Apply filters
            $query = $this->applyMaintenanceFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters, 'service_date');
            }

            $perPage = $filters['per_page'] ?? 10;
            $maintenanceRecords = $query->orderBy('service_date', 'desc')->paginate($perPage);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in maintenance report', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Maintenance report generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'result_count' => $maintenanceRecords->total(),
            ]);
            
            return [
                'maintenanceRecords' => $maintenanceRecords,
                'filters' => $filters,
                'stats' => $this->getMaintenanceStatistics($carrierId, $filters),
            ];
        } catch (Exception $e) {
            Log::error('Error generating maintenance report', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating maintenance report: ' . $e->getMessage());
        }
    }

    /**
     * Apply maintenance-specific filters to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyMaintenanceFilters($query, array $filters)
    {
        // Search filter (service tasks, vendor/mechanic, description)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('service_tasks', 'like', "%{$search}%")
                  ->orWhere('vendor_mechanic', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                      $vehicleQuery->where('company_unit_number', 'like', "%{$search}%")
                          ->orWhere('vin', 'like', "%{$search}%");
                  });
            });
        }
        
        // Vehicle filter
        if (!empty($filters['vehicle'])) {
            $query->where('vehicle_id', $filters['vehicle']);
        }
        
        // Maintenance type filter (service_tasks)
        if (!empty($filters['type'])) {
            $query->where('service_tasks', 'like', "%{$filters['type']}%");
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            
            if ($status === 'completed') {
                $query->where('status', true);
            } elseif ($status === 'pending') {
                $query->where('status', false);
            }
        }
        
        return $query;
    }

    /**
     * Get maintenance statistics for a carrier
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    protected function getMaintenanceStatistics(int $carrierId, array $filters): array
    {
        try {
            // Base query for carrier's maintenance records
            $baseQuery = VehicleMaintenance::whereHas('vehicle', function ($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            });
            
            // Apply the same filters as the main report
            $filteredQuery = clone $baseQuery;
            $filteredQuery = $this->applyMaintenanceFilters($filteredQuery, $filters);
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $filteredQuery = $this->applyDateRangeFilter($filteredQuery, $filters, 'service_date');
            }
            
            // Total maintenance count (with filters applied)
            $count = $filteredQuery->count();
            
            // Total cost (with filters applied)
            $totalCost = (clone $filteredQuery)->sum('cost') ?? 0;
            
            // Average cost (with filters applied)
            $averageCost = $count > 0 ? round($totalCost / $count, 2) : 0;
            
            // Completed vs pending (with filters applied)
            $completed = (clone $filteredQuery)->where('status', true)->count();
            $pending = (clone $filteredQuery)->where('status', false)->count();
            
            return [
                'count' => $count,
                'total_cost' => $totalCost,
                'average_cost' => $averageCost,
                'completed' => $completed,
                'pending' => $pending,
            ];
        } catch (Exception $e) {
            Log::error('Error calculating maintenance statistics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'count' => 0,
                'total_cost' => 0,
                'average_cost' => 0,
                'completed' => 0,
                'pending' => 0,
            ];
        }
    }

    /**
     * Export maintenance report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportMaintenanceReportPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Build query with filters
            $query = VehicleMaintenance::with(['vehicle'])
                ->whereHas('vehicle', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });

            // Apply filters
            $query = $this->applyMaintenanceFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters, 'service_date');
            }

            // Get all maintenance records (no pagination for PDF)
            $maintenanceRecords = $query->orderBy('service_date', 'desc')->get();
            
            // Get statistics
            $stats = $this->getMaintenanceStatistics($carrierId, $filters);
            
            // Prepare data for PDF
            $data = [
                'maintenanceRecords' => $maintenanceRecords,
                'carrier' => $carrier,
                'filters' => $filters,
                'stats' => $stats,
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'total_records' => $maintenanceRecords->count(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.maintenance', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                    'record_count' => $maintenanceRecords->count(),
                ]);
            }
            
            Log::info('Maintenance report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'record_count' => $maintenanceRecords->count(),
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "maintenance_report_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting maintenance report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting maintenance report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get repair report with filters
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getRepairReport(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            $query = EmergencyRepair::with(['vehicle'])
                ->whereHas('vehicle', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });

            // Apply filters
            $query = $this->applyRepairFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters, 'repair_date');
            }

            $perPage = $filters['per_page'] ?? 10;
            $repairRecords = $query->orderBy('repair_date', 'desc')->paginate($perPage);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in repair report', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Repair report generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'result_count' => $repairRecords->total(),
            ]);
            
            return [
                'repairRecords' => $repairRecords,
                'filters' => $filters,
                'stats' => $this->getRepairStatistics($carrierId, $filters),
            ];
        } catch (Exception $e) {
            Log::error('Error generating repair report', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating repair report: ' . $e->getMessage());
        }
    }

    /**
     * Apply repair-specific filters to query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyRepairFilters($query, array $filters)
    {
        // Search filter (repair name, description, notes)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('repair_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                      $vehicleQuery->where('company_unit_number', 'like', "%{$search}%")
                          ->orWhere('vin', 'like', "%{$search}%");
                  });
            });
        }
        
        // Vehicle filter
        if (!empty($filters['vehicle'])) {
            $query->where('vehicle_id', $filters['vehicle']);
        }
        
        // Repair type filter (repair_name)
        if (!empty($filters['repair_type'])) {
            $query->where('repair_name', 'like', "%{$filters['repair_type']}%");
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            
            if ($status === 'completed') {
                $query->where('status', 'completed');
            } elseif ($status === 'pending') {
                $query->where('status', 'pending');
            } elseif ($status === 'in_progress') {
                $query->where('status', 'in_progress');
            }
        }
        
        return $query;
    }

    /**
     * Get repair statistics for a carrier
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    protected function getRepairStatistics(int $carrierId, array $filters): array
    {
        try {
            // Base query for carrier's repair records
            $baseQuery = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            });
            
            // Apply the same filters as the main report
            $filteredQuery = clone $baseQuery;
            $filteredQuery = $this->applyRepairFilters($filteredQuery, $filters);
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $filteredQuery = $this->applyDateRangeFilter($filteredQuery, $filters, 'repair_date');
            }
            
            // Total repair count (with filters applied)
            $count = $filteredQuery->count();
            
            // Total cost (with filters applied)
            $totalCost = (clone $filteredQuery)->sum('cost') ?? 0;
            
            // Average cost (with filters applied)
            $averageCost = $count > 0 ? round($totalCost / $count, 2) : 0;
            
            // Status breakdown (with filters applied)
            $completed = (clone $filteredQuery)->where('status', 'completed')->count();
            $pending = (clone $filteredQuery)->where('status', 'pending')->count();
            $inProgress = (clone $filteredQuery)->where('status', 'in_progress')->count();
            
            return [
                'count' => $count,
                'total_cost' => $totalCost,
                'average_cost' => $averageCost,
                'completed' => $completed,
                'pending' => $pending,
                'in_progress' => $inProgress,
            ];
        } catch (Exception $e) {
            Log::error('Error calculating repair statistics', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage()
            ]);
            return [
                'count' => 0,
                'total_cost' => 0,
                'average_cost' => 0,
                'completed' => 0,
                'pending' => 0,
                'in_progress' => 0,
            ];
        }
    }

    /**
     * Export repair report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportRepairReportPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Build query with filters
            $query = EmergencyRepair::with(['vehicle'])
                ->whereHas('vehicle', function ($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });

            // Apply filters
            $query = $this->applyRepairFilters($query, $filters);

            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $query = $this->applyDateRangeFilter($query, $filters, 'repair_date');
            }

            // Get all repair records (no pagination for PDF)
            $repairRecords = $query->orderBy('repair_date', 'desc')->get();
            
            // Get statistics
            $stats = $this->getRepairStatistics($carrierId, $filters);
            
            // Prepare data for PDF
            $data = [
                'repairs' => $repairRecords,
                'repairRecords' => $repairRecords, // Keep for backwards compatibility
                'carrier' => $carrier,
                'filters' => $filters,
                'stats' => $stats,
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'total_records' => $repairRecords->count(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.repairs', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                    'record_count' => $repairRecords->count(),
                ]);
            }
            
            Log::info('Repair report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'record_count' => $repairRecords->count(),
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "repairs_report_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting repair report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting repair report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get monthly summary report with aggregated data
     * 
     * @param int $carrierId
     * @param array $filters
     * @return array
     */
    public function getMonthlySummary(int $carrierId, array $filters): array
    {
        try {
            $startTime = microtime(true);
            
            // Determine date range (default to last 12 months)
            $endDate = !empty($filters['date_to']) 
                ? Carbon::parse($filters['date_to']) 
                : Carbon::now();
            
            $startDate = !empty($filters['date_from']) 
                ? Carbon::parse($filters['date_from']) 
                : Carbon::now()->subMonths(12);
            
            // Generate monthly data
            $monthlyData = [];
            $currentDate = $startDate->copy()->startOfMonth();
            
            while ($currentDate <= $endDate) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();
                
                $monthlyData[] = [
                    'month' => $currentDate->format('Y-m'),
                    'month_name' => $currentDate->format('F Y'),
                    'drivers' => $this->getMonthlyDriverCount($carrierId, $monthStart, $monthEnd),
                    'vehicles' => $this->getMonthlyVehicleCount($carrierId, $monthStart, $monthEnd),
                    'accidents' => $this->getMonthlyAccidentCount($carrierId, $monthStart, $monthEnd),
                    'maintenance' => $this->getMonthlyMaintenanceData($carrierId, $monthStart, $monthEnd),
                    'repairs' => $this->getMonthlyRepairData($carrierId, $monthStart, $monthEnd),
                ];
                
                $currentDate->addMonth();
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if query is slow
            if ($executionTime > 2000) {
                Log::warning('Slow query detected in monthly summary', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Monthly summary generated', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
                'months_count' => count($monthlyData),
            ]);
            
            return [
                'monthlyData' => $monthlyData,
                'filters' => $filters,
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ];
        } catch (Exception $e) {
            Log::error('Error generating monthly summary', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error generating monthly summary: ' . $e->getMessage());
        }
    }

    /**
     * Get monthly driver count (new drivers added in the month)
     * 
     * @param int $carrierId
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
     * @return int
     */
    protected function getMonthlyDriverCount(int $carrierId, Carbon $monthStart, Carbon $monthEnd): int
    {
        return UserDriverDetail::where('carrier_id', $carrierId)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
    }

    /**
     * Get monthly vehicle count (new vehicles added in the month)
     * 
     * @param int $carrierId
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
     * @return int
     */
    protected function getMonthlyVehicleCount(int $carrierId, Carbon $monthStart, Carbon $monthEnd): int
    {
        return Vehicle::where('carrier_id', $carrierId)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
    }

    /**
     * Get monthly accident count
     * 
     * @param int $carrierId
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
     * @return int
     */
    protected function getMonthlyAccidentCount(int $carrierId, Carbon $monthStart, Carbon $monthEnd): int
    {
        return DriverAccident::whereHas('userDriverDetail', function ($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->whereBetween('accident_date', [$monthStart, $monthEnd])
        ->count();
    }

    /**
     * Get monthly maintenance data (count and cost)
     * 
     * @param int $carrierId
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
     * @return array
     */
    protected function getMonthlyMaintenanceData(int $carrierId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $query = VehicleMaintenance::whereHas('vehicle', function ($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->whereBetween('service_date', [$monthStart, $monthEnd]);
        
        $count = $query->count();
        $totalCost = $query->sum('cost') ?? 0;
        
        return [
            'count' => $count,
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Get monthly repair data (count and cost)
     * 
     * @param int $carrierId
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
     * @return array
     */
    protected function getMonthlyRepairData(int $carrierId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $query = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->whereBetween('repair_date', [$monthStart, $monthEnd]);
        
        $count = $query->count();
        $totalCost = $query->sum('cost') ?? 0;
        
        return [
            'count' => $count,
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Export monthly summary report to PDF
     * 
     * @param int $carrierId
     * @param array $filters
     * @return Response
     */
    public function exportMonthlySummaryPdf(int $carrierId, array $filters): Response
    {
        try {
            $startTime = microtime(true);
            
            // Get carrier information
            $carrier = Carrier::findOrFail($carrierId);
            
            // Get monthly summary data
            $summaryData = $this->getMonthlySummary($carrierId, $filters);
            
            // Prepare data for PDF
            $data = [
                'monthlyData' => $summaryData['monthlyData'],
                'carrier' => $carrier,
                'filters' => $filters,
                'startDate' => $summaryData['startDate'],
                'endDate' => $summaryData['endDate'],
                'generated_at' => Carbon::now()->format('m/d/Y H:i:s'),
                'totalDrivers' => collect($summaryData['monthlyData'])->sum('drivers'),
                'totalVehicles' => collect($summaryData['monthlyData'])->sum('vehicles'),
                'totalAccidents' => collect($summaryData['monthlyData'])->sum('accidents'),
                'totalMaintenanceCost' => collect($summaryData['monthlyData'])->sum('maintenance.total_cost'),
                'totalRepairCost' => collect($summaryData['monthlyData'])->sum('repairs.total_cost'),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('carrier.reports.pdf.monthly', $data);
            
            // Configure PDF
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance warning if PDF generation is slow
            if ($executionTime > 2000) {
                Log::warning('Slow PDF generation detected', [
                    'carrier_id' => $carrierId,
                    'execution_time_ms' => $executionTime,
                    'filters' => $filters,
                ]);
            }
            
            Log::info('Monthly summary report PDF exported', [
                'carrier_id' => $carrierId,
                'execution_time_ms' => $executionTime,
                'filters' => $filters,
            ]);
            
            // Generate filename with carrier slug and date
            $filename = "monthly_summary_{$carrier->slug}_" . Carbon::now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (Exception $e) {
            Log::error('Error exporting monthly summary report PDF', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Error exporting monthly summary PDF: ' . $e->getMessage());
        }
    }
}
