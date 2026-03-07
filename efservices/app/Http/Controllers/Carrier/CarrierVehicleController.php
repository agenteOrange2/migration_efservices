<?php

namespace App\Http\Controllers\Carrier;

use App\Models\Carrier;
use App\Helpers\Constants;
use Illuminate\Http\Request;
use App\Models\UserDriverDetail;
use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMake;
use App\Models\Admin\Vehicle\VehicleType;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleServiceItem;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\OwnerOperatorDetail;
use App\Models\ThirdPartyDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Rules\ValidateVehicleDocumentFile;
use App\Mail\ThirdPartyVehicleVerification;
use App\Models\VehicleVerificationToken;

class CarrierVehicleController extends Controller
{
    /**
     * Mostrar una lista de todos los vehículos del carrier.
     */
    public function index(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $query = Vehicle::with(['carrier', 'driver', 'activeDriverAssignment.driver.user'])
            ->where('carrier_id', $carrier->id);
        
        // Enhanced search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%")
                  ->orWhere('company_unit_number', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }
        
        // Enhanced status filtering
        if ($request->has('status') && !empty($request->status)) {
            switch ($request->status) {
                case 'active':
                    $query->where('out_of_service', false)->where('suspended', false);
                    break;
                case 'out_of_service':
                    $query->where('out_of_service', true);
                    break;
                case 'suspended':
                    $query->where('suspended', true);
                    break;
            }
        }
        
        // Vehicle type filtering
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }
        
        // Vehicle make filtering
        if ($request->has('make') && !empty($request->make)) {
            $query->where('make', $request->make);
        }
        
        // Driver filtering
        if ($request->has('driver_id') && !empty($request->driver_id)) {
            $query->where('user_driver_detail_id', $request->driver_id);
        }
        
        // Pagination with configurable page size
        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }
        
        $vehicles = $query->paginate($perPage)->appends($request->query());
        
        // Obtener los conductores del carrier para el filtro
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();
        
        return view('carrier.vehicles.index', compact('vehicles', 'carrier', 'drivers'));
    }

    /**
     * Get filter options for the vehicle index page.
     */
    public function getFilterOptions(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Get unique vehicle types for this carrier
        $vehicleTypes = Vehicle::where('carrier_id', $carrier->id)
            ->distinct()
            ->pluck('type')
            ->filter()
            ->sort()
            ->values();
        
        // Get unique vehicle makes for this carrier
        $vehicleMakes = Vehicle::where('carrier_id', $carrier->id)
            ->distinct()
            ->pluck('make')
            ->filter()
            ->sort()
            ->values();
        
        // Get drivers for this carrier
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get()
            ->map(function($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user->name ?? 'Unknown Driver'
                ];
            });
        
        // Status options
        $statusOptions = [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'out_of_service', 'label' => 'Out of Service'],
            ['value' => 'suspended', 'label' => 'Suspended']
        ];
        
        return response()->json([
            'types' => $vehicleTypes->map(fn($type) => ['value' => $type, 'label' => $type]),
            'makes' => $vehicleMakes->map(fn($make) => ['value' => $make, 'label' => $make]),
            'drivers' => $drivers,
            'statuses' => $statusOptions
        ]);
    }

    /**
     * Export vehicles to PDF format.
     */
    public function exportPdf(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Apply the same filters as the index method
        $query = Vehicle::with(['carrier', 'driver', 'activeDriverAssignment.driver.user'])
            ->where('carrier_id', $carrier->id);
        
        // Apply filters from request
        $this->applyFilters($query, $request);
        
        $vehicles = $query->get();
        
        // Generate PDF using a view
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('carrier.vehicles.exports.pdf', [
            'vehicles' => $vehicles,
            'carrier' => $carrier,
            'filters' => $request->all(),
            'exportDate' => now()->format('Y-m-d H:i:s'),
            'exportedBy' => Auth::user()->name
        ]);
        
        $filename = 'vehicles_' . $carrier->name . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export vehicles to CSV format.
     */
    public function exportCsv(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Apply the same filters as the index method
        $query = Vehicle::with(['carrier', 'driver', 'activeDriverAssignment.driver.user'])
            ->where('carrier_id', $carrier->id);
        
        // Apply filters from request
        $this->applyFilters($query, $request);
        
        $vehicles = $query->get();
        
        $filename = 'vehicles_' . str_replace(' ', '_', $carrier->name) . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($vehicles, $carrier) {
            $file = fopen('php://output', 'w');
            
            // Add metadata header
            fputcsv($file, ['# Vehicle Export Report']);
            fputcsv($file, ['# Carrier:', $carrier->name]);
            fputcsv($file, ['# Export Date:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, ['# Exported By:', Auth::user()->name]);
            fputcsv($file, ['']); // Empty row
            
            // CSV headers
            fputcsv($file, [
                'Unit Number',
                'Make',
                'Model',
                'Type',
                'Year',
                'VIN',
                'Registration Number',
                'Registration State',
                'Registration Expiration',
                'Assigned Driver',
                'Driver Type',
                'Status',
                'Out of Service Date',
                'Suspended Date',
                'Location',
                'Ownership Type',
                'Fuel Type',
                'GVWR',
                'Notes'
            ]);
            
            // Vehicle data
            foreach ($vehicles as $vehicle) {
                $driverName = 'Not Assigned';
                $driverType = '';
                
                if ($vehicle->activeDriverAssignment && $vehicle->activeDriverAssignment->driver) {
                    $driverName = $vehicle->activeDriverAssignment->driver->user->name ?? 'Unknown';
                    $driverType = $vehicle->activeDriverAssignment->driver_type ?? '';
                }
                
                fputcsv($file, [
                    $vehicle->company_unit_number,
                    $vehicle->make,
                    $vehicle->model,
                    $vehicle->type,
                    $vehicle->year,
                    $vehicle->vin,
                    $vehicle->registration_number,
                    $vehicle->registration_state,
                    $vehicle->registration_expiration_date?->format('Y-m-d'),
                    $driverName,
                    $driverType,
                    $vehicle->status,
                    $vehicle->out_of_service_date?->format('Y-m-d'),
                    $vehicle->suspended_date?->format('Y-m-d'),
                    $vehicle->location,
                    $vehicle->ownership_type,
                    $vehicle->fuel_type,
                    $vehicle->gvwr,
                    $vehicle->notes
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Apply filters to the vehicle query.
     */
    private function applyFilters($query, Request $request)
    {
        // Enhanced search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%")
                  ->orWhere('company_unit_number', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }
        
        // Enhanced status filtering
        if ($request->has('status') && !empty($request->status)) {
            switch ($request->status) {
                case 'active':
                    $query->where('out_of_service', false)->where('suspended', false);
                    break;
                case 'out_of_service':
                    $query->where('out_of_service', true);
                    break;
                case 'suspended':
                    $query->where('suspended', true);
                    break;
            }
        }
        
        // Vehicle type filtering
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }
        
        // Vehicle make filtering
        if ($request->has('make') && !empty($request->make)) {
            $query->where('make', $request->make);
        }
        
        // Driver filtering
        if ($request->has('driver_id') && !empty($request->driver_id)) {
            $query->where('user_driver_detail_id', $request->driver_id);
        }
    }

    /**
     * Preview a vehicle document.
     */
    public function previewDocument(Vehicle $vehicle, VehicleDocument $document)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $document->vehicle_id !== $vehicle->id) {
            return response()->json(['error' => 'No tienes acceso a este documento.'], 403);
        }
        
        $media = $document->getFirstMedia('document_files');
        
        if (!$media) {
            return response()->json(['error' => 'No se encontró el archivo del documento.'], 404);
        }
        
        // Check if document can be previewed
        if (!$document->canPreview()) {
            return response()->json(['error' => 'Este tipo de archivo no se puede previsualizar.'], 400);
        }
        
        // Return the media information for preview
        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'type' => $document->document_type,
                'number' => $document->document_number,
                'name' => $media->name,
                'original_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $document->file_size,
                'url' => $media->getUrl(),
                'preview_url' => $document->preview_url,
                'download_url' => route('carrier.vehicles.documents.download', [$vehicle, $document]),
                'can_preview' => $document->canPreview(),
                'issued_date' => $document->issued_date?->format('Y-m-d'),
                'expiration_date' => $document->expiration_date?->format('Y-m-d'),
                'is_expired' => $document->isExpired(),
                'is_expiring_soon' => $document->isExpiringSoon(),
                'notes' => $document->notes
            ]
        ]);
    }

    /**
     * Get maintenance calendar data for a vehicle.
     */
    public function maintenanceCalendar(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este vehículo.'], 403);
        }
        
        $maintenanceItems = $vehicle->maintenances()
            ->whereNotNull('next_service_date')
            ->orderBy('next_service_date', 'asc')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->service_tasks ?? 'Maintenance',
                    'start' => $item->next_service_date->format('Y-m-d'),
                    'description' => $item->description ?? $item->service_tasks,
                    'vendor' => $item->vendor_mechanic,
                    'cost' => $item->cost,
                    'status' => $item->status,
                    'isOverdue' => $item->isOverdue(),
                    'isUpcoming' => $item->isUpcoming(),
                    'className' => $item->isOverdue() ? 'overdue' : ($item->isUpcoming() ? 'upcoming' : 'scheduled')
                ];
            });
        
        return response()->json($maintenanceItems);
    }

    /**
     * Get maintenance history for a vehicle with chronological ordering and status indicators.
     */
    public function maintenanceHistory(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este vehículo.'], 403);
        }
        
        $maintenances = $vehicle->maintenances()
            ->orderBy('service_date', 'desc')
            ->get()
            ->map(function($maintenance) {
                $statusIndicator = 'completed';
                if (!$maintenance->status) {
                    $statusIndicator = $maintenance->isOverdue() ? 'overdue' : 'pending';
                }
                
                return [
                    'id' => $maintenance->id,
                    'service_date' => $maintenance->service_date->format('Y-m-d'),
                    'next_service_date' => $maintenance->next_service_date?->format('Y-m-d'),
                    'service_tasks' => $maintenance->service_tasks,
                    'vendor_mechanic' => $maintenance->vendor_mechanic,
                    'description' => $maintenance->description,
                    'cost' => $maintenance->cost,
                    'odometer' => $maintenance->odometer,
                    'status' => $maintenance->status,
                    'status_indicator' => $statusIndicator,
                    'is_overdue' => $maintenance->isOverdue(),
                    'is_upcoming' => $maintenance->isUpcoming(),
                    'days_until_due' => $maintenance->next_service_date ? 
                        now()->diffInDays($maintenance->next_service_date, false) : null,
                    'has_files' => $maintenance->getMedia('maintenance_files')->count() > 0,
                    'files_count' => $maintenance->getMedia('maintenance_files')->count(),
                ];
            });
        
        return response()->json([
            'maintenances' => $maintenances,
            'summary' => [
                'total' => $maintenances->count(),
                'completed' => $maintenances->where('status', true)->count(),
                'pending' => $maintenances->where('status', false)->count(),
                'overdue' => $maintenances->where('is_overdue', true)->count(),
                'upcoming' => $maintenances->where('is_upcoming', true)->count(),
                'total_cost' => $maintenances->sum('cost'),
            ]
        ]);
    }

    /**
     * Get overdue maintenance items for the carrier.
     */
    public function getOverdueMaintenance()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Get all vehicles for this carrier with overdue maintenance
        $vehiclesWithOverdueMaintenance = Vehicle::where('carrier_id', $carrier->id)
            ->with(['maintenances' => function($query) {
                $query->overdue()->orderBy('next_service_date', 'asc');
            }])
            ->whereHas('maintenances', function($query) {
                $query->overdue();
            })
            ->get();
        
        $overdueItems = [];
        
        foreach ($vehiclesWithOverdueMaintenance as $vehicle) {
            foreach ($vehicle->maintenances as $maintenance) {
                $daysOverdue = now()->diffInDays($maintenance->next_service_date);
                
                $overdueItems[] = [
                    'maintenance_id' => $maintenance->id,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_unit' => $vehicle->company_unit_number ?? $vehicle->vin,
                    'vehicle_make_model' => $vehicle->make . ' ' . $vehicle->model,
                    'service_tasks' => $maintenance->service_tasks,
                    'vendor_mechanic' => $maintenance->vendor_mechanic,
                    'next_service_date' => $maintenance->next_service_date->format('Y-m-d'),
                    'days_overdue' => $daysOverdue,
                    'cost' => $maintenance->cost,
                    'priority' => $this->getMaintenancePriority($daysOverdue),
                    'urgency_level' => $this->getUrgencyLevel($daysOverdue),
                ];
            }
        }
        
        // Sort by days overdue (most overdue first)
        usort($overdueItems, function($a, $b) {
            return $b['days_overdue'] <=> $a['days_overdue'];
        });
        
        return response()->json([
            'overdue_items' => $overdueItems,
            'total_overdue' => count($overdueItems),
            'vehicles_affected' => $vehiclesWithOverdueMaintenance->count(),
            'total_estimated_cost' => array_sum(array_column($overdueItems, 'cost')),
        ]);
    }

    /**
     * Get upcoming maintenance items for the carrier.
     */
    public function getUpcomingMaintenance($days = 30)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Get all vehicles for this carrier with upcoming maintenance
        $vehiclesWithUpcomingMaintenance = Vehicle::where('carrier_id', $carrier->id)
            ->with(['maintenances' => function($query) use ($days) {
                $query->upcoming($days)->orderBy('next_service_date', 'asc');
            }])
            ->whereHas('maintenances', function($query) use ($days) {
                $query->upcoming($days);
            })
            ->get();
        
        $upcomingItems = [];
        
        foreach ($vehiclesWithUpcomingMaintenance as $vehicle) {
            foreach ($vehicle->maintenances as $maintenance) {
                $daysUntilDue = now()->diffInDays($maintenance->next_service_date);
                
                $upcomingItems[] = [
                    'maintenance_id' => $maintenance->id,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_unit' => $vehicle->company_unit_number ?? $vehicle->vin,
                    'vehicle_make_model' => $vehicle->make . ' ' . $vehicle->model,
                    'service_tasks' => $maintenance->service_tasks,
                    'vendor_mechanic' => $maintenance->vendor_mechanic,
                    'next_service_date' => $maintenance->next_service_date->format('Y-m-d'),
                    'days_until_due' => $daysUntilDue,
                    'cost' => $maintenance->cost,
                    'priority' => $this->getMaintenancePriority(-$daysUntilDue), // Negative for upcoming
                ];
            }
        }
        
        return response()->json([
            'upcoming_items' => $upcomingItems,
            'total_upcoming' => count($upcomingItems),
            'vehicles_affected' => $vehiclesWithUpcomingMaintenance->count(),
            'total_estimated_cost' => array_sum(array_column($upcomingItems, 'cost')),
        ]);
    }

    /**
     * Get maintenance notifications for the carrier dashboard.
     */
    public function getMaintenanceNotifications()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $notifications = [];
        
        // Get overdue maintenance count
        $overdueCount = VehicleMaintenance::whereHas('vehicle', function($query) use ($carrier) {
            $query->where('carrier_id', $carrier->id);
        })->overdue()->count();
        
        if ($overdueCount > 0) {
            $notifications[] = [
                'type' => 'overdue',
                'level' => 'danger',
                'title' => 'Mantenimiento Vencido',
                'message' => "Tienes {$overdueCount} " . ($overdueCount === 1 ? 'mantenimiento vencido' : 'mantenimientos vencidos'),
                'count' => $overdueCount,
                'action_url' => route('carrier.vehicles.maintenance.overdue'),
                'icon' => 'fas fa-exclamation-triangle'
            ];
        }
        
        // Get upcoming maintenance count (next 7 days)
        $upcomingCount = VehicleMaintenance::whereHas('vehicle', function($query) use ($carrier) {
            $query->where('carrier_id', $carrier->id);
        })->upcoming(7)->count();
        
        if ($upcomingCount > 0) {
            $notifications[] = [
                'type' => 'upcoming',
                'level' => 'warning',
                'title' => 'Mantenimiento Próximo',
                'message' => "Tienes {$upcomingCount} " . ($upcomingCount === 1 ? 'mantenimiento programado' : 'mantenimientos programados') . ' para los próximos 7 días',
                'count' => $upcomingCount,
                'action_url' => route('carrier.vehicles.maintenance.upcoming'),
                'icon' => 'fas fa-clock'
            ];
        }
        
        // Get vehicles without recent maintenance (over 6 months)
        $vehiclesWithoutRecentMaintenance = Vehicle::where('carrier_id', $carrier->id)
            ->whereDoesntHave('maintenances', function($query) {
                $query->where('service_date', '>=', now()->subMonths(6));
            })
            ->count();
        
        if ($vehiclesWithoutRecentMaintenance > 0) {
            $notifications[] = [
                'type' => 'no_recent_maintenance',
                'level' => 'info',
                'title' => 'Sin Mantenimiento Reciente',
                'message' => "Tienes {$vehiclesWithoutRecentMaintenance} " . ($vehiclesWithoutRecentMaintenance === 1 ? 'vehículo sin' : 'vehículos sin') . ' mantenimiento en los últimos 6 meses',
                'count' => $vehiclesWithoutRecentMaintenance,
                'action_url' => route('carrier.vehicles.index'),
                'icon' => 'fas fa-info-circle'
            ];
        }
        
        return response()->json([
            'notifications' => $notifications,
            'total_notifications' => count($notifications),
            'has_critical' => $overdueCount > 0,
        ]);
    }

    /**
     * Get maintenance priority based on days overdue/upcoming.
     */
    private function getMaintenancePriority($days)
    {
        if ($days > 30) {
            return 'critical';
        } elseif ($days > 14) {
            return 'high';
        } elseif ($days > 7) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get urgency level for overdue maintenance.
     */
    private function getUrgencyLevel($daysOverdue)
    {
        if ($daysOverdue > 60) {
            return 'critical';
        } elseif ($daysOverdue > 30) {
            return 'high';
        } elseif ($daysOverdue > 14) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get assignment history for a vehicle.
     * Requirement 6.3: Display all assignments ordered by start date descending
     */
    public function assignmentHistory(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este vehículo.'], 403);
        }
        
        // Requirement 6.3: Order by start_date descending
        $assignments = $vehicle->assignmentHistory()
            ->with(['driver.user', 'assignedByUser', 'ownerOperatorDetail', 'thirdPartyDetail', 'companyDriverDetail'])
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function($assignment) {
                $driverName = $assignment->driver && $assignment->driver->user 
                    ? $assignment->driver->user->name 
                    : 'Unknown Driver';
                
                // Requirement 6.4: Display all required fields
                return [
                    'id' => $assignment->id,
                    'driver_name' => $driverName,
                    'driver_type' => $assignment->driver_type,
                    'start_date' => $assignment->start_date?->format('Y-m-d'),
                    'end_date' => $assignment->end_date?->format('Y-m-d'),
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                    'assigned_by' => $assignment->assignedByUser?->name,
                    'duration_days' => $assignment->getDurationInDays(),
                    'is_active' => $assignment->isActive()
                ];
            });
        
        return response()->json($assignments);
    }

    /**
     * Display driver assignment history page for a vehicle.
     * Requirements 16.1, 16.2, 16.3, 16.4, 16.5
     */
    public function driverAssignmentHistory(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Requirement 16.5: Verify vehicle belongs to carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            abort(403, 'No tienes acceso a este vehículo.');
        }
        
        // Load vehicle with carrier relationship
        $vehicle->load(['carrier']);
        
        // Requirement 16.1: Order by start_date descending
        // Requirement 16.3: Paginate with 15 assignments per page
        $assignmentHistory = VehicleDriverAssignment::where('vehicle_id', $vehicle->id)
            ->with([
                'user.driverDetail',
                'ownerOperatorDetail',
                'thirdPartyDetail',
                'companyDriverDetail'
            ])
            ->orderBy('start_date', 'desc')
            ->paginate(15);
        
        // Requirement 16.2: Display all required fields (handled in view)
        // Requirement 16.4: Indicate active assignment (handled in view)
        return view('carrier.vehicles.driver-assignment-history', compact('vehicle', 'assignmentHistory'));
    }

    /**
     * Update driver assignment for a vehicle.
     */
    public function updateAssignment(Request $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este vehículo.'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'driver_type' => 'required|in:owner_operator,company_driver,third_party',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Requirement 6.5: Validate driver belongs to carrier
        $driver = UserDriverDetail::find($request->user_driver_detail_id);
        if (!$driver || $driver->carrier_id !== $carrier->id) {
            return response()->json([
                'error' => 'El conductor seleccionado no pertenece a tu carrier.'
            ], 403);
        }
        
        try {
            // Requirement 6.2: End current active assignment if exists
            $currentAssignment = $vehicle->activeDriverAssignment;
            if ($currentAssignment) {
                $currentAssignment->end();
            }
            
            // Requirement 6.1: Create new assignment
            $assignment = VehicleDriverAssignment::create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $request->user_driver_detail_id,
                'driver_type' => $request->driver_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'active',
                'notes' => $request->notes,
                'assigned_by' => Auth::id()
            ]);
            
            // Requirement 6.6: Update vehicle's direct driver reference for backward compatibility
            $vehicle->update(['user_driver_detail_id' => $request->user_driver_detail_id]);
            
            return response()->json([
                'message' => 'Asignación actualizada exitosamente',
                'assignment' => $assignment->load('driver.user')
            ]);
            
        } catch (\Exception $e) {            
            return response()->json(['error' => 'Error al actualizar asignación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update vehicle status (activate, suspend, or put out of service).
     */
    public function updateStatus(Request $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este vehículo.'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,out_of_service,suspended',
            'out_of_service_date' => 'nullable|date|required_if:status,out_of_service',
            'suspended_date' => 'nullable|date|required_if:status,suspended',
            'reason' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $updateData = [];
            
            switch ($request->status) {
                case 'active':
                    $updateData = [
                        'out_of_service' => false,
                        'suspended' => false,
                        'out_of_service_date' => null,
                        'suspended_date' => null,
                        'status' => 'active'
                    ];
                    break;
                    
                case 'out_of_service':
                    $updateData = [
                        'out_of_service' => true,
                        'suspended' => false,
                        'out_of_service_date' => $request->out_of_service_date,
                        'suspended_date' => null,
                        'status' => 'out_of_service'
                    ];
                    break;
                    
                case 'suspended':
                    $updateData = [
                        'out_of_service' => false,
                        'suspended' => true,
                        'out_of_service_date' => null,
                        'suspended_date' => $request->suspended_date,
                        'status' => 'suspended'
                    ];
                    break;
            }
            
            if ($request->reason) {
                $updateData['notes'] = ($vehicle->notes ? $vehicle->notes . "\n\n" : '') . 
                    "Status changed to {$request->status} on " . now()->format('Y-m-d H:i:s') . 
                    ": {$request->reason}";
            }
            
            $vehicle->update($updateData);
            
            return response()->json([
                'message' => 'Estado del vehículo actualizado exitosamente',
                'vehicle' => $vehicle->fresh()
            ]);
            
        } catch (\Exception $e) {            
            return response()->json(['error' => 'Error al actualizar estado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create a new vehicle make via AJAX.
     */
    public function createMake(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:vehicle_makes,name'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $make = VehicleMake::create(['name' => $request->name]);
            
            return response()->json([
                'success' => true,
                'make' => $make,
                'message' => 'Vehicle make created successfully'
            ]);
        } catch (\Exception $e) {            
            return response()->json([
                'success' => false,
                'message' => 'Error creating vehicle make: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new vehicle type via AJAX.
     */
    public function createType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:vehicle_types,name'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $type = VehicleType::create(['name' => $request->name]);
            
            return response()->json([
                'success' => true,
                'type' => $type,
                'message' => 'Vehicle type created successfully'
            ]);
        } catch (\Exception $e) {            
            return response()->json([
                'success' => false,
                'message' => 'Error creating vehicle type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vehicle statistics for the carrier.
     */
    public function getStatistics()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $totalVehicles = Vehicle::where('carrier_id', $carrier->id)->count();
        $activeVehicles = Vehicle::where('carrier_id', $carrier->id)->active()->count();
        $outOfServiceVehicles = Vehicle::where('carrier_id', $carrier->id)->outOfService()->count();
        $suspendedVehicles = Vehicle::where('carrier_id', $carrier->id)->suspended()->count();
        $unassignedVehicles = Vehicle::where('carrier_id', $carrier->id)->unassigned()->count();
        
        // Vehicles with expiring documents (next 30 days)
        $vehiclesWithExpiringDocs = Vehicle::where('carrier_id', $carrier->id)
            ->whereHas('documents', function($query) {
                $query->whereNotNull('expiration_date')
                      ->where('expiration_date', '>', now())
                      ->where('expiration_date', '<=', now()->addDays(30));
            })
            ->count();
        
        // Vehicles with overdue maintenance
        $vehiclesWithOverdueMaintenance = Vehicle::where('carrier_id', $carrier->id)
            ->whereHas('maintenances', function($query) {
                $query->whereNotNull('next_service_date')
                      ->where('next_service_date', '<', now())
                      ->where('status', false);
            })
            ->count();
        
        return response()->json([
            'total_vehicles' => $totalVehicles,
            'active_vehicles' => $activeVehicles,
            'out_of_service_vehicles' => $outOfServiceVehicles,
            'suspended_vehicles' => $suspendedVehicles,
            'unassigned_vehicles' => $unassignedVehicles,
            'vehicles_with_expiring_docs' => $vehiclesWithExpiringDocs,
            'vehicles_with_overdue_maintenance' => $vehiclesWithOverdueMaintenance,
            'utilization_rate' => $totalVehicles > 0 ? round(($totalVehicles - $unassignedVehicles) / $totalVehicles * 100, 1) : 0
        ]);
    }

    /**
     * Get document expiration statistics for the carrier.
     */
    public function getDocumentExpirationStats()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Get all documents for this carrier's vehicles
        $documents = VehicleDocument::whereHas('vehicle', function($query) use ($carrier) {
            $query->where('carrier_id', $carrier->id);
        })->whereNotNull('expiration_date')->get();
        
        $expiredCount = $documents->filter(function($doc) {
            return $doc->isExpired();
        })->count();
        
        $expiringSoonCount = $documents->filter(function($doc) {
            return $doc->isExpiringSoon() && !$doc->isExpired();
        })->count();
        
        $activeCount = $documents->filter(function($doc) {
            return !$doc->isExpired() && !$doc->isExpiringSoon();
        })->count();
        
        return response()->json([
            'total_documents' => $documents->count(),
            'expired_documents' => $expiredCount,
            'expiring_soon_documents' => $expiringSoonCount,
            'active_documents' => $activeCount,
            'expiration_rate' => $documents->count() > 0 ? round($expiredCount / $documents->count() * 100, 1) : 0
        ]);
    }

    /**
     * Mostrar el formulario para crear un nuevo vehículo.
     */
    public function create()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar si se ha alcanzado el límite de vehículos
        $maxVehicles = $carrier->membership->max_vehicles ?? 1;
        $currentVehiclesCount = Vehicle::where('carrier_id', $carrier->id)->count();
        
        if ($currentVehiclesCount >= $maxVehicles) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'Has alcanzado el límite máximo de vehículos para tu plan. Actualiza tu membresía para añadir más vehículos.');
        }
        
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->where('status', 1) // Solo conductores activos
            ->get();
            
        $vehicleMakes = VehicleMake::all();
        $vehicleTypes = VehicleType::all();
        $usStates = Constants::usStates();

        return view('carrier.vehicles.create', compact('carrier', 'drivers', 'vehicleMakes', 'vehicleTypes', 'usStates'));
    }

    /**
     * Almacenar un vehículo recién creado.
     */
    public function store(StoreVehicleRequest $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;

        // Guardar o crear marca del vehículo si no existe
        if ($request->has('make') && !VehicleMake::where('name', $request->make)->exists()) {
            VehicleMake::create(['name' => $request->make]);
        }
        
        // Guardar o crear tipo de vehículo si no existe
        if ($request->has('type') && !VehicleType::where('name', $request->type)->exists()) {
            VehicleType::create(['name' => $request->type]);
        }

        // Crear el vehículo (solo campos básicos del vehículo)
        $vehicleData = $request->only([
            'make', 'model', 'type', 'year', 'vin', 'color',
            'company_unit_number', 'gvwr', 'tire_size', 'fuel_type',
            'irp_apportioned_plate', 'registration_state', 'registration_number',
            'registration_expiration_date', 'annual_inspection_expiration_date', 
            'permanent_tag', 'location', 'notes', 'ownership_type',
            'out_of_service', 'out_of_service_date', 'suspended', 'suspended_date', 
            'status', 'user_driver_detail_id'
        ]);
        
        // Automatically set carrier_id to authenticated user's carrier
        $vehicleData['carrier_id'] = $carrier->id;
        
        $vehicle = Vehicle::create($vehicleData);
        
        // Procesar y guardar los service_items si existen
        if ($request->has('service_items') && is_array($request->service_items)) {
            foreach ($request->service_items as $serviceItem) {
                // Solo guardar si hay datos significativos
                if (!empty($serviceItem['service_date']) || !empty($serviceItem['service_tasks']) || 
                    !empty($serviceItem['vendor_mechanic']) || !empty($serviceItem['description'])) {
                    
                    // Crear un array con solo los campos permitidos en el modelo
                    $serviceItemData = [
                        'vehicle_id' => $vehicle->id,
                        'unit' => $serviceItem['unit'] ?? null,
                        'service_date' => $serviceItem['service_date'] ?? null,
                        'next_service_date' => $serviceItem['next_service_date'] ?? null,
                        'service_tasks' => $serviceItem['service_tasks'] ?? null,
                        'vendor_mechanic' => $serviceItem['vendor_mechanic'] ?? null,
                        'description' => $serviceItem['description'] ?? null,
                        'cost' => $serviceItem['cost'] ?? null,
                        'odometer' => $serviceItem['odometer'] ?? null
                    ];
                    
                    // Crear el service item vinculado al vehículo
                    VehicleServiceItem::create($serviceItemData);                
                }
            }
        }

        // Redirect to driver type assignment page as per requirement 2.9
        return redirect()->route('carrier.vehicles.assign-driver-type', $vehicle->id)
            ->with('success', 'Vehicle created successfully. Please assign a driver type.');
    }

    /**
     * Mostrar un vehículo específico.
     */
    public function show(Vehicle $vehicle)
    {
        // Verify vehicle access using helper method
        $this->verifyVehicleAccess($vehicle);
        
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Load vehicle with all relationships as per requirements 3.2, 3.3, 3.4, 3.5
        $vehicle->load([
            'carrier',
            'driver.user',
            'currentDriverAssignment.driver.user',
            'assignmentHistory.driver.user',
            'assignmentHistory.assignedByUser',
            'maintenances' => function($query) {
                $query->orderBy('service_date', 'desc');
            },
            'documents',
            'emergencyRepairs' => function($query) {
                $query->orderBy('repair_date', 'desc');
            }
        ]);
        
        return view('carrier.vehicles.show', compact('vehicle', 'carrier'));
    }
    
    /**
     * Verify that the vehicle belongs to the authenticated user's carrier.
     * Returns 403 if vehicle doesn't belong to carrier (Requirement 3.6).
     *
     * @param Vehicle $vehicle
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function verifyVehicleAccess(Vehicle $vehicle): void
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        if ($vehicle->carrier_id !== $carrier->id) {
            abort(403, 'You do not have access to this vehicle.');
        }
    }

    /**
     * Mostrar el formulario para editar un vehículo.
     * Requirement 4.1: Verify vehicle belongs to carrier before allowing edits
     */
    public function edit(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado (Requirement 4.1)
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        // Load vehicle with relationships (scoped to carrier)
        $vehicle->load(['currentDriverAssignment.user', 'currentDriverAssignment.driver.user', 'carrier']);
        
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->where('status', 1) // Solo conductores activos
            ->get();
            
        $vehicleMakes = VehicleMake::orderBy('name')->get();
        $vehicleTypes = VehicleType::orderBy('name')->get();
        $usStates = Constants::usStates();
        
        // Cargar historial de mantenimiento del vehículo
        $maintenanceHistory = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->orderBy('service_date', 'desc')
            ->get();
        
        // Load owner operator and third party details if applicable
        $ownerDetails = null;
        $thirdPartyDetails = null;
        
        // Buscar el vehicle driver assignment asociado al vehículo (incluyendo pending y active)
        $vehicleAssignment = VehicleDriverAssignment::where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($vehicleAssignment) {
            // Cargar detalles según el tipo de propiedad
            if ($vehicle->ownership_type === 'owned') {
                $ownerDetails = OwnerOperatorDetail::where('vehicle_driver_assignment_id', $vehicleAssignment->id)->first();            
            } 
            else if ($vehicle->ownership_type === 'third-party') {
                $thirdPartyDetails = ThirdPartyDetail::where('vehicle_driver_assignment_id', $vehicleAssignment->id)->first();

            }
        }

        return view('carrier.vehicles.edit', compact(
            'vehicle', 
            'carrier', 
            'drivers', 
            'vehicleMakes', 
            'vehicleTypes', 
            'usStates',
            'ownerDetails',
            'thirdPartyDetails',
            'maintenanceHistory'
        ));
    }

    /**
     * Actualizar un vehículo específico.
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado (Requirement 4.1)
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }

        // Guardar o crear marca del vehículo si no existe
        if ($request->has('make') && !VehicleMake::where('name', $request->make)->exists()) {
            VehicleMake::create(['name' => $request->make]);
        }
        
        // Guardar o crear tipo de vehículo si no existe
        if ($request->has('type') && !VehicleType::where('name', $request->type)->exists()) {
            VehicleType::create(['name' => $request->type]);
        }
        
        // Actualizar el vehículo (Requirement 4.2: validation handled by UpdateVehicleRequest)
        $vehicle->update($request->all());
        
        // Handle service items updates (delete old, create new) - Requirement 4.4
        if ($request->has('service_items') && is_array($request->service_items)) {
            // Delete existing service items
            VehicleServiceItem::where('vehicle_id', $vehicle->id)->delete();
            
            foreach ($request->service_items as $serviceItem) {
                // Solo guardar si hay datos significativos
                if (!empty($serviceItem['service_date']) || !empty($serviceItem['service_tasks']) || 
                    !empty($serviceItem['vendor_mechanic']) || !empty($serviceItem['description'])) {
                    
                    // Crear un array con solo los campos permitidos en el modelo
                    $serviceItemData = [
                        'vehicle_id' => $vehicle->id,
                        'unit' => $serviceItem['unit'] ?? null,
                        'service_date' => $serviceItem['service_date'] ?? null,
                        'next_service_date' => $serviceItem['next_service_date'] ?? null,
                        'service_tasks' => $serviceItem['service_tasks'] ?? null,
                        'vendor_mechanic' => $serviceItem['vendor_mechanic'] ?? null,
                        'description' => $serviceItem['description'] ?? null,
                        'cost' => $serviceItem['cost'] ?? null,
                        'odometer' => $serviceItem['odometer'] ?? null
                    ];
                    
                    // Crear el service item vinculado al vehículo
                    VehicleServiceItem::create($serviceItemData);

                }
            }
        }
        
        // Handle ownership type updates (owned, third-party) - Requirements 4.5, 4.6
        if ($request->ownership_type === 'owned' || $request->ownership_type === 'third-party') {
            try {
                // Get or create vehicle driver assignment
                $vehicleAssignment = VehicleDriverAssignment::where('vehicle_id', $vehicle->id)->first();
                
                if (!$vehicleAssignment) {
                    // Use the current authenticated user
                    $userId = Auth::id();
                    
                    // Create a new driver application with the user_id
                    $driverApplication = new \App\Models\Admin\Driver\DriverApplication();
                    $driverApplication->user_id = $userId;
                    $driverApplication->status = 'pending';
                    $driverApplication->save();
                    
                    // Create vehicle driver assignment
                    $vehicleAssignment = VehicleDriverAssignment::create([
                        'driver_application_id' => $driverApplication->id,
                        'vehicle_id' => $vehicle->id,
                        'driver_type' => $request->ownership_type === 'owned' ? 'owner_operator' : 'third_party',
                        'status' => 'pending',
                        'assigned_at' => now()
                    ]);
                    
                }
                
                // Update assignment details based on ownership type
                if ($request->ownership_type === 'owned') {
                    // Update or create owner operator details (Requirement 4.5)
                    $ownerDetails = OwnerOperatorDetail::updateOrCreate(
                        ['vehicle_driver_assignment_id' => $vehicleAssignment->id],
                        [
                            'owner_name' => $request->owner_name,
                            'owner_phone' => $request->owner_phone,
                            'owner_email' => $request->owner_email,
                            'contract_agreed' => true
                        ]
                    );

                } 
                else if ($request->ownership_type === 'third-party') {
                    // Update or create third party details (Requirement 4.6)
                    $thirdPartyDetails = ThirdPartyDetail::updateOrCreate(
                        ['vehicle_driver_assignment_id' => $vehicleAssignment->id],
                        [
                            'third_party_name' => $request->third_party_name,
                            'third_party_phone' => $request->third_party_phone,
                            'third_party_email' => $request->third_party_email,
                            'third_party_dba' => $request->third_party_dba ?? '',
                            'third_party_address' => $request->third_party_address ?? '',
                            'third_party_contact' => $request->third_party_contact ?? '',
                            'third_party_fein' => $request->third_party_fein ?? '',
                            'email_sent' => $request->has('email_sent') && $request->email_sent ? 1 : 0
                        ]
                    );

                }
                
                // Log success
            } catch (\Exception $e) {
                // Log the error

            }
        }
        
        // Add third-party email sending logic (Requirement 4.7)
        if ($request->ownership_type === 'third-party' && $request->has('email_sent') && $request->email_sent) {
            // Buscar el VehicleDriverAssignment para este vehículo
            $vehicleAssignment = VehicleDriverAssignment::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['active', 'pending'])
                ->first();
            
            if ($vehicleAssignment) {
                // Buscar los detalles de third party usando el vehicle_driver_assignment_id
                $thirdPartyDetail = ThirdPartyDetail::where('vehicle_driver_assignment_id', $vehicleAssignment->id)->first();
                
                if ($thirdPartyDetail && $thirdPartyDetail->third_party_email) {
                    $this->sendThirdPartyVerificationEmail(
                        $vehicle,
                        $thirdPartyDetail->third_party_name,
                        $thirdPartyDetail->third_party_email,
                        $thirdPartyDetail->third_party_phone,
                        $vehicleAssignment->id
                    );
                    
                    // Update the email_sent flag en la tabla third_party_details
                    $thirdPartyDetail->email_sent = 1;
                    $thirdPartyDetail->save();                    
                }
            }
        }
        
        // Requirement 4.8: Redirect to vehicle show page with success message
        return redirect()->route('carrier.vehicles.show', $vehicle->id)
            ->with('success', 'Vehículo actualizado exitosamente');
    }
    
    /**
     * Enviar correo de verificación a third party company driver
     * Requirement 4.7: Third-party email sending logic
     */
    private function sendThirdPartyVerificationEmail($vehicle, $thirdPartyName, $thirdPartyEmail, $thirdPartyPhone, $vehicleAssignmentId)
    {
        try {
            // Obtener datos del driver desde el vehicle assignment
            $driverName = '';
            $driverId = 0;
            
            // Obtener el vehicle assignment
            $vehicleAssignment = VehicleDriverAssignment::find($vehicleAssignmentId);
            if ($vehicleAssignment && $vehicleAssignment->driverApplication && $vehicleAssignment->driverApplication->user) {
                // Obtener el UserDriverDetail asociado al usuario de la aplicación
                $userDriverDetail = UserDriverDetail::where('user_id', $vehicleAssignment->driverApplication->user_id)->first();
                
                if ($userDriverDetail) {
                    $driverName = $vehicleAssignment->driverApplication->user->name;
                    $driverId = $userDriverDetail->id;
                    
                    // Actualizar el user_driver_detail_id del vehículo para que el CustomPathGenerator funcione correctamente
                    $vehicle->user_driver_detail_id = $driverId;
                    $vehicle->save();
                }
            }
            
            // Generar token único para la verificación usando el modelo VehicleVerificationToken
            $token = VehicleVerificationToken::generateToken();
            $expiresAt = now()->addDays(7);
            
            // Guardar el token de verificación en la base de datos
            $verification = VehicleVerificationToken::create([
                'token' => $token,
                'vehicle_driver_assignment_id' => $vehicleAssignmentId,
                'vehicle_id' => $vehicle->id,
                'third_party_name' => $thirdPartyName,
                'third_party_email' => $thirdPartyEmail,
                'third_party_phone' => $thirdPartyPhone,
                'expires_at' => $expiresAt,
            ]);
        
            
            // Convertir el objeto vehículo a un array asociativo para la plantilla de correo
            $vehicleData = [
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'vin' => $vehicle->vin,
                'type' => $vehicle->type,
                'registration_state' => $vehicle->registration_state,
                'registration_number' => $vehicle->registration_number
            ];
            
            // Enviar correo
            Mail::to($thirdPartyEmail)
                ->queue(new ThirdPartyVehicleVerification(
                    $thirdPartyName,
                    $driverName,
                    $vehicleData,
                    $token,
                    $driverId, // Este es el ID del conductor (user_driver_detail_id)
                    $vehicleAssignmentId
                ));
                        
            return true;
        } catch (\Exception $e) {            
            return false;
        }
    }

    /**
     * Eliminar un vehículo específico.
     * Requirements: 5.1, 5.2, 5.3, 5.4
     */
    public function destroy(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Requirement 5.1, 5.4: Verify vehicle belongs to carrier before deletion
        // Return 403 if vehicle doesn't belong to carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            abort(403, 'You do not have access to this vehicle.');
        }
        
        try {
            // Eliminar documentos relacionados
            $vehicle->documents()->get()->each(function($document) {
                $document->clearMediaCollection('document_files');
                $document->delete();
            });
            
            // Eliminar items de servicio relacionados
            $vehicle->serviceItems()->delete();
            
            // Requirement 5.2: Delete vehicle from database
            $vehicle->delete();
            
            // Requirement 5.3: Redirect to index with success message
            return redirect()->route('carrier.vehicles.index')
                ->with('success', 'Vehículo eliminado exitosamente');
                
        } catch (\Exception $e) {
            
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'Error al eliminar vehículo: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar la lista de documentos de un vehículo.
     */
    public function documents(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        $documents = $vehicle->documents()->paginate(10);
        
        return view('carrier.vehicles.documents.index', compact('vehicle', 'documents', 'carrier'));
    }
    
    /**
     * Mostrar el formulario para crear un nuevo documento de vehículo.
     */
    public function createDocument(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        return view('carrier.vehicles.documents.create', compact('vehicle', 'carrier'));
    }
    
    /**
     * Almacenar un nuevo documento de vehículo.
     */
    public function storeDocument(Request $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after:issued_date',
            'notes' => 'nullable|string',
            'document_file' => ['required', new ValidateVehicleDocumentFile()],
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Crear el documento
            $document = new VehicleDocument([
                'vehicle_id' => $vehicle->id,
                'document_type' => $request->document_type,
                'document_number' => $request->document_number,
                'issued_date' => $request->issued_date,
                'expiration_date' => $request->expiration_date,
                'notes' => $request->notes,
                'status' => 'active',
            ]);
            
            $document->save();
            
            // Procesar el archivo
            if ($request->hasFile('document_file')) {
                $document->addMediaFromRequest('document_file')
                    ->toMediaCollection('document_files');
            }
            
            return redirect()->route('carrier.vehicles.documents', $vehicle->id)
                ->with('success', 'Documento creado exitosamente');
                
        } catch (\Exception $e) {
            
            return redirect()->back()
                ->with('error', 'Error al crear documento: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Mostrar el formulario para editar un documento de vehículo.
     */
    public function editDocument(Vehicle $vehicle, VehicleDocument $document)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $document->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este documento.');
        }
        
        return view('carrier.vehicles.documents.edit', compact('vehicle', 'document', 'carrier'));
    }
    
    /**
     * Actualizar un documento de vehículo.
     */
    public function updateDocument(Request $request, Vehicle $vehicle, VehicleDocument $document)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $document->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este documento.');
        }
        
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after:issued_date',
            'notes' => 'nullable|string',
            'document_file' => ['nullable', new ValidateVehicleDocumentFile()],
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Actualizar el documento
            $document->update([
                'document_type' => $request->document_type,
                'document_number' => $request->document_number,
                'issued_date' => $request->issued_date,
                'expiration_date' => $request->expiration_date,
                'notes' => $request->notes,
            ]);
            
            // Procesar el archivo si se proporcionó uno nuevo
            if ($request->hasFile('document_file')) {
                $document->clearMediaCollection('document_files');
                $document->addMediaFromRequest('document_file')
                    ->toMediaCollection('document_files');
            }
            
            return redirect()->route('carrier.vehicles.documents', $vehicle->id)
                ->with('success', 'Documento actualizado exitosamente');
                
        } catch (\Exception $e) {
            
            return redirect()->back()
                ->with('error', 'Error al actualizar documento: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Eliminar un documento de vehículo.
     */
    public function destroyDocument(Vehicle $vehicle, VehicleDocument $document)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $document->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este documento.');
        }
        
        try {
            // Eliminar archivos adjuntos
            $document->clearMediaCollection('document_files');
            
            // Eliminar el documento
            $document->delete();
            
            return redirect()->route('carrier.vehicles.documents', $vehicle->id)
                ->with('success', 'Documento eliminado exitosamente');
                
        } catch (\Exception $e) {

            
            return redirect()->route('carrier.vehicles.documents', $vehicle->id)
                ->with('error', 'Error al eliminar documento: ' . $e->getMessage());
        }
    }
    
    /**
     * Descargar un documento de vehículo.
     */
    public function downloadDocument(Vehicle $vehicle, VehicleDocument $document)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $document->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este documento.');
        }
        
        $media = $document->getFirstMedia('document_files');
        
        if (!$media) {
            return redirect()->back()
                ->with('error', 'No se encontró el archivo del documento.');
        }
        
        return $media;
    }
    
    /**
     * Mostrar la lista de mantenimientos de un vehículo.
     */
    public function serviceItems(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        $serviceItems = $vehicle->serviceItems()->paginate(10);
        
        return view('carrier.vehicles.service-items.index', compact('vehicle', 'serviceItems', 'carrier'));
    }
    
    /**
     * Mostrar el formulario para crear un nuevo item de servicio.
     */
    public function createServiceItem(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        return view('carrier.vehicles.service-items.create', compact('vehicle', 'carrier'));
    }
    
    /**
     * Almacenar un nuevo item de servicio.
     */
    public function storeServiceItem(Request $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        $validator = Validator::make($request->all(), [
            'service_date' => 'required|date',
            'next_service_date' => 'required|date|after:service_date',
            'service_type' => 'required|string|max:255',
            'service_tasks' => 'required|string',
            'vendor_mechanic' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'odometer_reading' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'service_documents.*' => 'nullable|file|max:10240', // 10MB max
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Crear el item de servicio
            $serviceItem = new VehicleServiceItem([
                'vehicle_id' => $vehicle->id,
                'service_date' => $request->service_date,
                'next_service_date' => $request->next_service_date,
                'service_type' => $request->service_type,
                'service_tasks' => $request->service_tasks,
                'vendor_mechanic' => $request->vendor_mechanic,
                'cost' => $request->cost,
                'odometer_reading' => $request->odometer_reading,
                'notes' => $request->notes,
                'status' => 'completed',
            ]);
            
            $serviceItem->save();
            
            // Procesar los archivos si se proporcionaron
            if ($request->hasFile('service_documents')) {
                foreach ($request->file('service_documents') as $file) {
                    $serviceItem->addMedia($file)
                        ->toMediaCollection('service_documents');
                }
            }
            
            return redirect()->route('carrier.vehicles.service-items', $vehicle->id)
                ->with('success', 'Item de servicio creado exitosamente');
                
        } catch (\Exception $e) {            
            return redirect()->back()
                ->with('error', 'Error al crear item de servicio: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Mostrar el formulario para editar un item de servicio.
     */
    public function editServiceItem(Vehicle $vehicle, VehicleServiceItem $serviceItem)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $serviceItem->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este item de servicio.');
        }
        
        return view('carrier.vehicles.service-items.edit', compact('vehicle', 'serviceItem', 'carrier'));
    }
    
    /**
     * Actualizar un item de servicio.
     */
    public function updateServiceItem(Request $request, Vehicle $vehicle, VehicleServiceItem $serviceItem)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $serviceItem->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este item de servicio.');
        }
        
        $validator = Validator::make($request->all(), [
            'service_date' => 'required|date',
            'next_service_date' => 'required|date|after:service_date',
            'service_type' => 'required|string|max:255',
            'service_tasks' => 'required|string',
            'vendor_mechanic' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'odometer_reading' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'service_documents.*' => 'nullable|file|max:10240', // 10MB max
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Actualizar el item de servicio
            $serviceItem->update([
                'service_date' => $request->service_date,
                'next_service_date' => $request->next_service_date,
                'service_type' => $request->service_type,
                'service_tasks' => $request->service_tasks,
                'vendor_mechanic' => $request->vendor_mechanic,
                'cost' => $request->cost,
                'odometer_reading' => $request->odometer_reading,
                'notes' => $request->notes,
                'status' => $request->status,
            ]);
            
            // Procesar los archivos si se proporcionaron
            if ($request->hasFile('service_documents')) {
                foreach ($request->file('service_documents') as $file) {
                    $serviceItem->addMedia($file)
                        ->toMediaCollection('service_documents');
                }
            }
            
            return redirect()->route('carrier.vehicles.service-items', $vehicle->id)
                ->with('success', 'Item de servicio actualizado exitosamente');
                
        } catch (\Exception $e) {            
            return redirect()->back()
                ->with('error', 'Error al actualizar item de servicio: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Eliminar un item de servicio.
     */
    public function destroyServiceItem(Vehicle $vehicle, VehicleServiceItem $serviceItem)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $serviceItem->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este item de servicio.');
        }
        
        try {
            // Eliminar archivos adjuntos
            $serviceItem->clearMediaCollection('service_documents');
            
            // Eliminar el item de servicio
            $serviceItem->delete();
            
            return redirect()->route('carrier.vehicles.service-items', $vehicle->id)
                ->with('success', 'Item de servicio eliminado exitosamente');
                
        } catch (\Exception $e) {            
            return redirect()->route('carrier.vehicles.service-items', $vehicle->id)
                ->with('error', 'Error al eliminar item de servicio: ' . $e->getMessage());
        }
    }
    
    /**
     * Cambiar el estado de un item de servicio.
     */
    public function toggleServiceItemStatus(Vehicle $vehicle, VehicleServiceItem $serviceItem)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($vehicle->carrier_id !== $carrier->id || $serviceItem->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este item de servicio.');
        }
        
        try {
            // Cambiar el estado del item de servicio
            $newStatus = $serviceItem->status === 'completed' ? 'pending' : 'completed';
            $serviceItem->update(['status' => $newStatus]);
            
            return redirect()->route('carrier.vehicles.service-items', $vehicle->id)
                ->with('success', 'Estado del item de servicio actualizado exitosamente');
                
        } catch (\Exception $e) {            
            return redirect()->route('carrier.vehicles.service-items', $vehicle->id)
                ->with('error', 'Error al cambiar estado del item de servicio: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of maintenance records for a vehicle.
     */
    public function maintenances(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        $maintenances = $vehicle->maintenances()
            ->orderBy('service_date', 'desc')
            ->paginate(10);
        
        return view('carrier.vehicles.maintenances.index', compact('vehicle', 'maintenances', 'carrier'));
    }

    /**
     * Show the form for creating a new maintenance record.
     */
    public function createMaintenance(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        return view('carrier.vehicles.maintenances.create', compact('vehicle', 'carrier'));
    }

    /**
     * Store a newly created maintenance record.
     */
    public function storeMaintenance(Request $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este vehículo.');
        }
        
        $validator = Validator::make($request->all(), [
            'service_date' => 'required|date|before_or_equal:today',
            'next_service_date' => 'nullable|date|after:service_date',
            'service_tasks' => 'required|string|max:255',
            'vendor_mechanic' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
            'maintenance_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Create the maintenance record
            $maintenance = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id,
                'unit' => $vehicle->company_unit_number ?? $vehicle->vin,
                'service_date' => $request->service_date,
                'next_service_date' => $request->next_service_date,
                'service_tasks' => $request->service_tasks,
                'vendor_mechanic' => $request->vendor_mechanic,
                'description' => $request->description,
                'cost' => $request->cost,
                'odometer' => $request->odometer,
                'status' => $request->status,
                'is_historical' => false,
            ]);
            
            // Process uploaded files if provided
            if ($request->hasFile('maintenance_files')) {
                foreach ($request->file('maintenance_files') as $file) {
                    $maintenance->addMedia($file)
                        ->toMediaCollection('maintenance_files');
                }
            }
            
            return redirect()->route('carrier.vehicles.maintenances', $vehicle->id)
                ->with('success', 'Registro de mantenimiento creado exitosamente');
                
        } catch (\Exception $e) {
            
            return redirect()->back()
                ->with('error', 'Error al crear registro de mantenimiento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified maintenance record.
     */
    public function showMaintenance(Vehicle $vehicle, VehicleMaintenance $maintenance)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id || $maintenance->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este registro de mantenimiento.');
        }
        
        return view('carrier.vehicles.maintenances.show', compact('vehicle', 'maintenance', 'carrier'));
    }

    /**
     * Show the form for editing the specified maintenance record.
     */
    public function editMaintenance(Vehicle $vehicle, VehicleMaintenance $maintenance)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id || $maintenance->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este registro de mantenimiento.');
        }
        
        return view('carrier.vehicles.maintenances.edit', compact('vehicle', 'maintenance', 'carrier'));
    }

    /**
     * Update the specified maintenance record.
     */
    public function updateMaintenance(Request $request, Vehicle $vehicle, VehicleMaintenance $maintenance)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id || $maintenance->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este registro de mantenimiento.');
        }
        
        $validator = Validator::make($request->all(), [
            'service_date' => 'required|date|before_or_equal:today',
            'next_service_date' => 'nullable|date|after:service_date',
            'service_tasks' => 'required|string|max:255',
            'vendor_mechanic' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
            'maintenance_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Update the maintenance record
            $maintenance->update([
                'service_date' => $request->service_date,
                'next_service_date' => $request->next_service_date,
                'service_tasks' => $request->service_tasks,
                'vendor_mechanic' => $request->vendor_mechanic,
                'description' => $request->description,
                'cost' => $request->cost,
                'odometer' => $request->odometer,
                'status' => $request->status,
            ]);
            
            // Process uploaded files if provided
            if ($request->hasFile('maintenance_files')) {
                foreach ($request->file('maintenance_files') as $file) {
                    $maintenance->addMedia($file)
                        ->toMediaCollection('maintenance_files');
                }
            }
            
            return redirect()->route('carrier.vehicles.maintenances', $vehicle->id)
                ->with('success', 'Registro de mantenimiento actualizado exitosamente');
                
        } catch (\Exception $e) {            
            return redirect()->back()
                ->with('error', 'Error al actualizar registro de mantenimiento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified maintenance record.
     */
    public function destroyMaintenance(Vehicle $vehicle, VehicleMaintenance $maintenance)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id || $maintenance->vehicle_id !== $vehicle->id) {
            return redirect()->route('carrier.vehicles.index')
                ->with('error', 'No tienes acceso a este registro de mantenimiento.');
        }
        
        try {
            // Delete associated media files
            $maintenance->clearMediaCollection('maintenance_files');
            
            // Delete the maintenance record
            $maintenance->delete();
            
            return redirect()->route('carrier.vehicles.maintenances', $vehicle->id)
                ->with('success', 'Registro de mantenimiento eliminado exitosamente');
                
        } catch (\Exception $e) {
            
            return redirect()->route('carrier.vehicles.maintenances', $vehicle->id)
                ->with('error', 'Error al eliminar registro de mantenimiento: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the status of a maintenance record.
     */
    public function toggleMaintenanceStatus(Vehicle $vehicle, VehicleMaintenance $maintenance)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id || $maintenance->vehicle_id !== $vehicle->id) {
            return response()->json(['error' => 'No tienes acceso a este registro de mantenimiento.'], 403);
        }
        
        try {
            // Toggle the status
            $maintenance->update(['status' => !$maintenance->status]);
            
            return response()->json([
                'success' => true,
                'message' => 'Estado del mantenimiento actualizado exitosamente',
                'status' => $maintenance->status
            ]);
            
        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado del mantenimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get maintenance reports for a vehicle.
     */
    public function maintenanceReports(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify that the vehicle belongs to the authenticated user's carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este vehículo.'], 403);
        }
        
        $maintenances = $vehicle->maintenances()
            ->orderBy('service_date', 'desc')
            ->get();
        
        // Calculate maintenance statistics
        $totalCost = $maintenances->sum('cost');
        $completedCount = $maintenances->where('status', true)->count();
        $pendingCount = $maintenances->where('status', false)->count();
        $overdueCount = $maintenances->filter(function($maintenance) {
            return $maintenance->isOverdue();
        })->count();
        
        // Get upcoming maintenance (next 30 days)
        $upcomingCount = $maintenances->filter(function($maintenance) {
            return $maintenance->isUpcoming(30);
        })->count();
        
        return response()->json([
            'total_maintenances' => $maintenances->count(),
            'completed_maintenances' => $completedCount,
            'pending_maintenances' => $pendingCount,
            'overdue_maintenances' => $overdueCount,
            'upcoming_maintenances' => $upcomingCount,
            'total_cost' => $totalCost,
            'average_cost' => $maintenances->count() > 0 ? round($totalCost / $maintenances->count(), 2) : 0,
            'maintenances' => $maintenances->map(function($maintenance) {
                return [
                    'id' => $maintenance->id,
                    'service_date' => $maintenance->service_date->format('Y-m-d'),
                    'next_service_date' => $maintenance->next_service_date?->format('Y-m-d'),
                    'service_tasks' => $maintenance->service_tasks,
                    'vendor_mechanic' => $maintenance->vendor_mechanic,
                    'cost' => $maintenance->cost,
                    'status' => $maintenance->status,
                    'is_overdue' => $maintenance->isOverdue(),
                    'is_upcoming' => $maintenance->isUpcoming(),
                ];
            })
        ]);
    }

    /**
     * Get driver data via AJAX
     */
    public function getDriverData(Request $request, Vehicle $vehicle)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            if ($vehicle->carrier_id !== $carrier->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $selectedDriverId = $request->get('driver_id');
            
            if (!$selectedDriverId) {
                return response()->json(['error' => 'Driver ID is required'], 400);
            }
            
            $selectedDriver = \App\Models\UserDriverDetail::with(['user', 'licenses'])
                ->whereHas('user', function($query) use ($selectedDriverId) {
                    $query->where('id', $selectedDriverId);
                })
                ->where('carrier_id', $carrier->id)
                ->first();
                
            if (!$selectedDriver || !$selectedDriver->user) {
                return response()->json(['error' => 'Driver not found'], 404);
            }
            
            $primaryLicense = $selectedDriver->licenses()->first();
            
            // Construir nombre completo
            $fullName = trim($selectedDriver->user->name ?? '');
            if ($selectedDriver->middle_name) {
                $fullName .= ' ' . trim($selectedDriver->middle_name);
            }
            if ($selectedDriver->last_name) {
                $fullName .= ' ' . trim($selectedDriver->last_name);
            }
            
            // Separar nombre y apellido
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
            
            // Formatear fecha de expiración
            $licenseExpiration = '';
            if ($primaryLicense && $primaryLicense->expiration_date) {
                try {
                    $licenseExpiration = \Carbon\Carbon::parse($primaryLicense->expiration_date)->format('m/d/Y');
                } catch (\Exception $e) {
                    $licenseExpiration = $primaryLicense->expiration_date;
                }
            }
            
            $driverData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $selectedDriver->phone ?? '',
                'email' => $selectedDriver->user->email ?? '',
                'license_number' => $primaryLicense ? ($primaryLicense->license_number ?? '') : '',
                'license_class' => $primaryLicense ? ($primaryLicense->license_class ?? '') : '',
                'license_state' => $primaryLicense ? ($primaryLicense->state_of_issue ?? '') : '',
                'license_expiration' => $licenseExpiration,
                'ownership_type' => 'company_driver'
            ];
            
            return response()->json(['success' => true, 'data' => $driverData]);
            
        } catch (\Exception $e) {            
            return response()->json(['error' => 'Error loading driver data'], 500);
        }
    }

    /**
     * Show form to assign driver type to vehicle
     */
    public function assignDriverType(Request $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify vehicle belongs to carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            abort(403, 'Unauthorized access');
        }
        
        // Load current assignment
        $vehicle->load(['currentDriverAssignment.user', 'currentDriverAssignment.thirdPartyDetail', 'currentDriverAssignment.ownerOperatorDetail']);
        $currentAssignment = $vehicle->currentDriverAssignment;
        
        // Get available drivers for this carrier
        $availableDrivers = \App\Models\UserDriverDetail::with(['user'])
            ->where('carrier_id', $carrier->id)
            ->where('status', \App\Models\UserDriverDetail::STATUS_ACTIVE) // Use status instead of application_completed
            ->whereHas('user', function($query) {
                $query->where('status', 1);
            })
            ->get()
            ->map(function($driverDetail) {
                return (object) [
                    'id' => $driverDetail->user->id,
                    'name' => $driverDetail->user->name,
                    'email' => $driverDetail->user->email
                ];
            });
        
        
        $driverData = null;
        $thirdPartyData = [];
        $currentDriverType = $currentAssignment ? $currentAssignment->driver_type : null;
        
        return view('carrier.vehicles.assign-driver-type', compact('vehicle', 'driverData', 'availableDrivers', 'currentAssignment', 'thirdPartyData', 'currentDriverType'));
    }

    /**
     * Store driver type assignment
     */
    public function storeDriverType(Request $request, Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify vehicle belongs to carrier
        if ($vehicle->carrier_id !== $carrier->id) {
            abort(403, 'Unauthorized access');
        }
        
        Log::info('storeDriverType - Input received', [
            'user_id_raw' => $request->input('user_id'),
            'user_id_type' => gettype($request->input('user_id')),
            'ownership_type' => $request->input('ownership_type'),
            'all_input' => $request->all()
        ]);
        
        // Convert empty string or 'unassigned' to null for user_id
        $userId = $request->input('user_id');
        if ($userId === '' || $userId === null || $userId === 'unassigned') {
            $request->merge(['user_id' => null]);
        }
        
        Log::info('storeDriverType - After conversion', [
            'user_id' => $request->input('user_id'),
        ]);
        
        $request->validate([
            'ownership_type' => 'required|in:company_driver,owner_operator,third_party',
            'user_id' => 'nullable|integer|exists:users,id',
            'owner_first_name' => 'nullable|string|max:255',
            'owner_last_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:20',
            'owner_email' => 'nullable|email|max:255',
            'third_party_name' => 'nullable|string|max:255',
            'third_party_phone' => 'nullable|string|max:20',
            'third_party_email' => 'nullable|email|max:255',
            'third_party_dba' => 'nullable|string|max:255',
            'third_party_fein' => 'nullable|string|max:50',
            'third_party_address' => 'nullable|string|max:500',
            'third_party_contact_person' => 'nullable|string|max:255',
        ]);
        
        try {
            DB::beginTransaction();
        
            
            // Find user_driver_detail_id if user_id is provided
            $userDriverDetailId = null;
            if ($request->filled('user_id')) {
                $userDriverDetail = \App\Models\UserDriverDetail::where('user_id', $request->user_id)->first();
                if ($userDriverDetail) {
                    $userDriverDetailId = $userDriverDetail->id;
                }
            }
            
            // End any existing active assignment to preserve history
            $existingAssignment = VehicleDriverAssignment::where('vehicle_id', $vehicle->id)
                ->where('status', 'active')
                ->first();
            
            if ($existingAssignment) {
                $existingAssignment->update([
                    'end_date' => now(),
                    'status' => 'inactive'
                ]);
            }

            // Create new assignment
            $assignment = VehicleDriverAssignment::create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $userDriverDetailId,
                'driver_type' => $request->ownership_type,
                'start_date' => now(),
                'status' => 'active'
            ]);
            
            // Save specific details based on driver type
            switch ($request->ownership_type) {
                case 'owner_operator':
                    $ownerOperatorDetail = \App\Models\OwnerOperatorDetail::updateOrCreate(
                        ['vehicle_driver_assignment_id' => $assignment->id],
                        [
                            'owner_name' => trim(($request->owner_first_name ?? '') . ' ' . ($request->owner_last_name ?? '')),
                            'owner_phone' => $request->owner_phone,
                            'owner_email' => $request->owner_email,
                            'contract_agreed' => true,
                            'notes' => 'Owner operator assignment created via carrier portal'
                        ]
                    );
                    
                    break;
                    
                case 'third_party':
                    $thirdPartyDetail = \App\Models\ThirdPartyDetail::updateOrCreate(
                        ['vehicle_driver_assignment_id' => $assignment->id],
                        [
                            'third_party_name' => $request->third_party_name,
                            'third_party_phone' => $request->third_party_phone,
                            'third_party_email' => $request->third_party_email,
                            'third_party_dba' => $request->third_party_dba,
                            'third_party_fein' => $request->third_party_fein,
                            'third_party_address' => $request->third_party_address,
                            'third_party_contact' => $request->third_party_contact_person,
                            'email_sent' => false,
                            'notes' => 'Third party assignment created via carrier portal'
                        ]
                    );
                    
                    break;
                    
                case 'company_driver':
                    $companyDriverDetail = \App\Models\CompanyDriverDetail::updateOrCreate(
                        ['vehicle_driver_assignment_id' => $assignment->id],
                        [
                            'carrier_id' => $vehicle->carrier_id,
                            'notes' => 'Company driver assignment created via carrier portal'
                        ]
                    );
                
                    break;
            }
            
            DB::commit();
            
            return redirect()->route('carrier.vehicles.show', $vehicle->id)
                ->with('success', 'Driver type assigned successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error assigning driver: ' . $e->getMessage())
                ->withInput();
        }
    }
}
