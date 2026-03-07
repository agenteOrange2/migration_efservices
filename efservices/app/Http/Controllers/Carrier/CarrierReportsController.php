<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Services\Carrier\CarrierReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\Response;

/**
 * Carrier Reports Controller
 * 
 * Handles all report-related requests for carriers including:
 * - Dashboard overview with metrics
 * - Individual report pages (drivers, vehicles, accidents, etc.)
 * - PDF exports for all report types
 * - Monthly summaries
 * 
 * All methods enforce carrier authentication and data isolation.
 */
class CarrierReportsController extends Controller
{
    /**
     * @var CarrierReportService
     */
    protected CarrierReportService $reportService;
    
    /**
     * Constructor
     * 
     * @param CarrierReportService $reportService
     */
    public function __construct(CarrierReportService $reportService)
    {
        // Ensure user is authenticated
        $this->middleware('auth');
        
        // Ensure user has carrier role
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !Auth::user()->hasRole('user_carrier')) {
                Log::warning('Unauthorized access attempt to carrier reports', [
                    'user_id' => Auth::id(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                abort(403, 'Unauthorized access');
            }
            
            return $next($request);
        });
        
        $this->reportService = $reportService;
    }
    
    /**
     * Get the authenticated carrier ID
     * 
     * @return int
     */
    protected function getCarrierId(): int
    {
        $user = Auth::user();
        
        if (!$user->carrierDetails || !$user->carrierDetails->carrier_id) {
            Log::error('User accessing reports without carrier details', [
                'user_id' => $user->id,
            ]);
            abort(403, 'No carrier associated with this user');
        }
        
        return $user->carrierDetails->carrier_id;
    }
    
    /**
     * Display the main reports dashboard
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $stats = $this->reportService->getDashboardMetrics($carrierId);
            
            Log::info('Reports dashboard accessed', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
            ]);
            
            return view('carrier.reports.index', compact('stats'));
        } catch (\Exception $e) {
            Log::error('Error loading reports dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->with('error', 'Error loading dashboard. Please try again.');
        }
    }
    
    /**
     * Display driver reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function drivers(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'status', 'date_from', 'date_to', 'per_page']);
            
            $report = $this->reportService->getDriverReport($carrierId, $filters);
            
            Log::info('Driver report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.drivers', $report);
        } catch (\Exception $e) {
            Log::error('Error loading driver report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading driver report. Please try again.');
        }
    }
    
    /**
     * Export driver report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function driversExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
            
            return $this->reportService->exportDriverReportPdf($carrierId, $filters);
            
        } catch (\Exception $e) {
            Log::error('Error exporting driver report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display vehicle reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function vehicles(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'status', 'date_from', 'date_to', 'per_page']);
            
            $report = $this->reportService->getVehicleReport($carrierId, $filters);
            
            Log::info('Vehicle report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.vehicles', $report);
        } catch (\Exception $e) {
            Log::error('Error loading vehicle report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading vehicle report. Please try again.');
        }
    }
    
    /**
     * Export vehicle report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function vehiclesExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
            
            return $this->reportService->exportVehicleReportPdf($carrierId, $filters);
        } catch (\Exception $e) {
            Log::error('Error exporting vehicle report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display accident reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function accidents(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'driver', 'date_from', 'date_to', 'per_page']);
            
            $report = $this->reportService->getAccidentReport($carrierId, $filters);
            
            Log::info('Accident report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.accidents', $report);
        } catch (\Exception $e) {
            Log::error('Error loading accident report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading accident report. Please try again.');
        }
    }
    
    /**
     * Export accident report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function accidentsExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'driver', 'date_from', 'date_to']);
            
            return $this->reportService->exportAccidentReportPdf($carrierId, $filters);
        } catch (\Exception $e) {
            Log::error('Error exporting accident report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }

    
    /**
     * Display medical records reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function medicalRecords(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'driver', 'expiration_status', 'date_from', 'date_to', 'per_page']);
            
            $report = $this->reportService->getMedicalRecordsReport($carrierId, $filters);
            
            Log::info('Medical records report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.medical-records', $report);
        } catch (\Exception $e) {
            Log::error('Error loading medical records report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading medical records report. Please try again.');
        }
    }
    
    /**
     * Export medical records report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function medicalRecordsExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'driver', 'expiration_status', 'date_from', 'date_to']);
            
            return $this->reportService->exportMedicalRecordsReportPdf($carrierId, $filters);
        } catch (\Exception $e) {
            Log::error('Error exporting medical records report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display license reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function licenses(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'driver', 'license_type', 'expiration_status', 'date_from', 'date_to', 'per_page']);
            
            $report = $this->reportService->getLicenseReport($carrierId, $filters);
            
            Log::info('License report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.licenses', $report);
        } catch (\Exception $e) {
            Log::error('Error loading license report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading license report. Please try again.');
        }
    }
    
    /**
     * Export license report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function licensesExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'driver', 'license_type', 'expiration_status', 'date_from', 'date_to']);
            
            return $this->reportService->exportLicenseReportPdf($carrierId, $filters);
        } catch (\Exception $e) {
            Log::error('Error exporting license report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display maintenance reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function maintenance(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'vehicle', 'type', 'status', 'date_from', 'date_to', 'per_page']);
            
            $report = $this->reportService->getMaintenanceReport($carrierId, $filters);
            
            Log::info('Maintenance report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.maintenance', $report);
        } catch (\Exception $e) {
            Log::error('Error loading maintenance report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading maintenance report. Please try again.');
        }
    }
    
    /**
     * Export maintenance report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function maintenanceExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'vehicle', 'type', 'status', 'date_from', 'date_to']);
            
            return $this->reportService->exportMaintenanceReportPdf($carrierId, $filters);
        } catch (\Exception $e) {
            Log::error('Error exporting maintenance report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display repair reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function repairs(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'vehicle', 'repair_type', 'status', 'date_from', 'date_to', 'per_page']);
            
            $report = $this->reportService->getRepairReport($carrierId, $filters);
            
            Log::info('Repair report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.repairs', $report);
        } catch (\Exception $e) {
            Log::error('Error loading repair report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading repair report. Please try again.');
        }
    }
    
    /**
     * Export repair report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function repairsExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['search', 'vehicle', 'repair_type', 'status', 'date_from', 'date_to']);
            
            return $this->reportService->exportRepairReportPdf($carrierId, $filters);
        } catch (\Exception $e) {
            Log::error('Error exporting repair report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display monthly summary reports
     * 
     * @param Request $request
     * @return View
     */
    public function monthly(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['date_from', 'date_to']);
            
            $report = $this->reportService->getMonthlySummary($carrierId, $filters);
            
            Log::info('Monthly summary report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.monthly', $report);
        } catch (\Exception $e) {
            Log::error('Error loading monthly summary report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading monthly summary. Please try again.');
        }
    }
    
    /**
     * Export monthly summary report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function monthlyExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $filters = $request->only(['date_from', 'date_to']);
            
            return $this->reportService->exportMonthlySummaryPdf($carrierId, $filters);
        } catch (\Exception $e) {
            Log::error('Error exporting monthly summary report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display trip reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function trips(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $hosReportService = app(\App\Services\HosReportService::class);
            
            $filters = $request->only(['driver_id', 'status', 'date_from', 'date_to', 'per_page']);
            
            $report = $hosReportService->getTripReport($filters, $carrierId);
            
            Log::info('Trip report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.trips', $report);
        } catch (\Exception $e) {
            Log::error('Error loading trip report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading trip report. Please try again.');
        }
    }
    
    /**
     * Export trip report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function tripsExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $hosReportService = app(\App\Services\HosReportService::class);
            
            $filters = $request->only(['driver_id', 'status', 'date_from', 'date_to']);
            
            return $hosReportService->exportTripReportPdf($filters, $carrierId);
        } catch (\Exception $e) {
            Log::error('Error exporting trip report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display HOS reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function hos(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $hosReportService = app(\App\Services\HosReportService::class);
            
            $filters = $request->only(['driver_id', 'date_from', 'date_to', 'has_violations', 'per_page']);
            
            $report = $hosReportService->getHosReport($filters, $carrierId);
            
            Log::info('HOS report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.hos', $report);
        } catch (\Exception $e) {
            Log::error('Error loading HOS report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading HOS report. Please try again.');
        }
    }
    
    /**
     * Export HOS report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function hosExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $hosReportService = app(\App\Services\HosReportService::class);
            
            $filters = $request->only(['driver_id', 'date_from', 'date_to', 'has_violations']);
            
            return $hosReportService->exportHosReportPdf($filters, $carrierId);
        } catch (\Exception $e) {
            Log::error('Error exporting HOS report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
    
    /**
     * Display violations reports with filters
     * 
     * @param Request $request
     * @return View
     */
    public function violations(Request $request): View
    {
        try {
            $carrierId = $this->getCarrierId();
            $hosReportService = app(\App\Services\HosReportService::class);
            
            $filters = $request->only(['driver_id', 'violation_type', 'severity', 'date_from', 'date_to', 'acknowledged', 'per_page']);
            
            $report = $hosReportService->getViolationsReport($filters, $carrierId);
            
            Log::info('Violations report accessed', [
                'carrier_id' => $carrierId,
                'filters' => $filters,
            ]);
            
            return view('carrier.reports.violations', $report);
        } catch (\Exception $e) {
            Log::error('Error loading violations report', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error loading violations report. Please try again.');
        }
    }
    
    /**
     * Export violations report to PDF
     * 
     * @param Request $request
     * @return Response
     */
    public function violationsExportPdf(Request $request): Response
    {
        try {
            $carrierId = $this->getCarrierId();
            $hosReportService = app(\App\Services\HosReportService::class);
            
            $filters = $request->only(['driver_id', 'violation_type', 'severity', 'date_from', 'date_to', 'acknowledged']);
            
            return $hosReportService->exportViolationsReportPdf($filters, $carrierId);
        } catch (\Exception $e) {
            Log::error('Error exporting violations report PDF', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error exporting report. Please try again.');
        }
    }
}
