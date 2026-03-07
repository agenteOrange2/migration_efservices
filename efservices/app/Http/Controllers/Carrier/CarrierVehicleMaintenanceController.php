<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Models\Admin\Vehicle\VehicleDocument;
use Barryvdh\DomPDF\Facade\Pdf;

class CarrierVehicleMaintenanceController extends Controller
{
    // ========================================
    // GENERAL MAINTENANCE METHODS (not vehicle-specific)
    // Routes: /carrier/maintenance/*
    // ========================================

    /**
     * Display general maintenance dashboard with upcoming maintenance.
     * Requirements: 1.1, 1.2, 1.3
     */
    public function index(): View
    {
        $carrier = $this->getCarrier();

        $carrierScope = function($query) use ($carrier) {
            $query->where('carrier_id', $carrier->id);
        };

        // Query next 5 upcoming pending maintenance records for carrier's vehicles
        $upcomingMaintenance = VehicleMaintenance::whereHas('vehicle', $carrierScope)
            ->where('status', false)
            ->whereNotNull('next_service_date')
            ->with('vehicle')
            ->orderBy('next_service_date', 'asc')
            ->limit(5)
            ->get();

        // Calculate total scheduled maintenance for current month
        $currentMonthCount = VehicleMaintenance::whereHas('vehicle', $carrierScope)
            ->whereYear('next_service_date', now()->year)
            ->whereMonth('next_service_date', now()->month)
            ->count();

        // Summary counts
        $overdueCount = VehicleMaintenance::whereHas('vehicle', $carrierScope)->overdue()->count();
        $upcomingCount = VehicleMaintenance::whereHas('vehicle', $carrierScope)->upcoming()->count();
        $pendingCount = VehicleMaintenance::whereHas('vehicle', $carrierScope)->pending()->count();
        $completedCount = VehicleMaintenance::whereHas('vehicle', $carrierScope)->completed()->count();

        // Overdue maintenance list
        $overdueMaintenance = VehicleMaintenance::whereHas('vehicle', $carrierScope)
            ->overdue()
            ->with('vehicle')
            ->orderBy('next_service_date', 'asc')
            ->limit(10)
            ->get();

        return view('carrier.maintenance.index', compact(
            'upcomingMaintenance', 'currentMonthCount',
            'overdueCount', 'upcomingCount', 'pendingCount', 'completedCount',
            'overdueMaintenance'
        ));
    }

    /**
     * Show create form with vehicle selector.
     * Requirements: 2.1
     */
    public function create(): View
    {
        $carrier = $this->getCarrier();
        
        // Load all carrier's vehicles for dropdown selector
        $vehicles = Vehicle::where('carrier_id', $carrier->id)
            ->orderBy('company_unit_number')
            ->get();

        // Tipos de mantenimiento predefinidos
        $maintenanceTypes = [
            'Preventive',
            'Corrective',
            'Inspection',
            'Oil Change',
            'Tire Rotation',
            'Brake Service',
            'Engine Service',
            'Transmission Service',
            'Other'
        ];

        return view('carrier.maintenance.create', compact('vehicles', 'maintenanceTypes'));
    }

    /**
     * Store new maintenance record from general area.
     * Requirements: 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8
     */
    public function store(Request $request): RedirectResponse
    {
        $carrier = $this->getCarrier();

        // Parse dates from m/d/Y format to Y-m-d for validation and storage
        try {
            $serviceDate = Carbon::createFromFormat('m/d/Y', $request->service_date)->format('Y-m-d');
        } catch (\Exception $e) {
            $serviceDate = $request->service_date;
        }

        // Default next_service_date to service_date + 3 months if not provided
        if ($request->filled('next_service_date')) {
            try {
                $nextServiceDate = Carbon::createFromFormat('m/d/Y', $request->next_service_date)->format('Y-m-d');
            } catch (\Exception $e) {
                $nextServiceDate = $request->next_service_date;
            }
        } else {
            $nextServiceDate = Carbon::parse($serviceDate)->addMonths(3)->format('Y-m-d');
        }

        $request->merge([
            'service_date' => $serviceDate,
            'next_service_date' => $nextServiceDate,
        ]);

        // Validación condicional basada en si es un servicio histórico
        $isHistorical = $request->boolean('is_historical');
        
        $validationRules = [
            'vehicle_id' => 'required|exists:vehicles,id',
            'unit' => 'required|string|min:1|max:255',
            'service_tasks' => 'required|string|min:1|max:255',
            'service_date' => 'required|date',
            'vendor_mechanic' => 'required|string|min:1|max:255',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|boolean',
            'is_historical' => 'nullable|boolean'
        ];
        
        // Ajustar validación de fechas según si es histórico o no
        if ($isHistorical) {
            // Para servicios históricos, permitir fechas pasadas y next_service_date puede ser anterior a service_date
            $validationRules['next_service_date'] = 'required|date';
        } else {
            // Para servicios normales, mantener validación original
            $validationRules['service_date'] .= '|before_or_equal:today';
            $validationRules['next_service_date'] = 'required|date|after:service_date';
        }
        
        // Validar los datos del formulario
        $validated = $request->validate($validationRules);

        // Verify selected vehicle belongs to carrier
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $this->validateCarrierOwnership($vehicle);
        
        try {
            DB::beginTransaction();
            
            // Crear el registro de mantenimiento
            $maintenance = VehicleMaintenance::create([
                'vehicle_id' => $request->vehicle_id,
                'unit' => $request->unit,
                'service_tasks' => $request->service_tasks,
                'service_date' => $request->service_date,
                'next_service_date' => $request->next_service_date,
                'vendor_mechanic' => $request->vendor_mechanic,
                'cost' => $request->cost,
                'odometer' => $request->odometer,
                'description' => $request->description,
                'status' => $request->status ? 1 : 0,
                'is_historical' => $request->boolean('is_historical'),
                'created_by' => Auth::id(),
            ]);
            
            // Procesar documentos subidos por Livewire (si hay)
            if ($request->filled('livewire_files')) {
                $this->processLivewireFiles($maintenance, json_decode($request->input('livewire_files'), true));
            }
            
            DB::commit();
            
            return redirect()->route('carrier.maintenance.index')
                ->with('success', 'Registro de mantenimiento creado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear registro de mantenimiento: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
                'carrier_id' => $carrier->id,
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al crear el registro de mantenimiento. Por favor, inténtelo de nuevo.')
                ->withInput();
        }
    }

    /**
     * Display maintenance details.
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
     */
    public function show(int $id): View
    {
        $carrier = $this->getCarrier();
        $maintenance = VehicleMaintenance::with('vehicle')->findOrFail($id);

        // Verify maintenance belongs to carrier's vehicle
        $this->validateCarrierOwnership($maintenance->vehicle);

        return view('carrier.maintenance.show', compact('maintenance'));
    }

    /**
     * Show edit form.
     * Requirements: 5.1, 5.7
     */
    public function edit(int $id): View
    {
        $carrier = $this->getCarrier();
        $maintenance = VehicleMaintenance::with('vehicle')->findOrFail($id);

        // Verify maintenance belongs to carrier's vehicle
        $this->validateCarrierOwnership($maintenance->vehicle);

        // Get all vehicles for dropdown
        $vehicles = Vehicle::where('carrier_id', $carrier->id)
            ->orderBy('company_unit_number')
            ->get();

        // Tipos de mantenimiento predefinidos
        $maintenanceTypes = [
            'Preventive',
            'Corrective',
            'Inspection',
            'Oil Change',
            'Tire Rotation',
            'Brake Service',
            'Engine Service',
            'Transmission Service',
            'Other'
        ];

        return view('carrier.maintenance.edit', compact('maintenance', 'vehicles', 'maintenanceTypes'));
    }

    /**
     * Update maintenance record.
     * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.7
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $carrier = $this->getCarrier();
        $maintenance = VehicleMaintenance::findOrFail($id);

        // Verify maintenance belongs to carrier's vehicle
        $this->validateCarrierOwnership($maintenance->vehicle);

        // Parse dates from m/d/Y format to Y-m-d for validation and storage
        try {
            $serviceDate = Carbon::createFromFormat('m/d/Y', $request->service_date)->format('Y-m-d');
        } catch (\Exception $e) {
            $serviceDate = $request->service_date;
        }

        // Default next_service_date to service_date + 3 months if not provided
        if ($request->filled('next_service_date')) {
            try {
                $nextServiceDate = Carbon::createFromFormat('m/d/Y', $request->next_service_date)->format('Y-m-d');
            } catch (\Exception $e) {
                $nextServiceDate = $request->next_service_date;
            }
        } else {
            $nextServiceDate = Carbon::parse($serviceDate)->addMonths(3)->format('Y-m-d');
        }

        $request->merge([
            'service_date' => $serviceDate,
            'next_service_date' => $nextServiceDate,
        ]);

        // Validación condicional basada en si es un servicio histórico
        $isHistorical = $request->boolean('is_historical');
        
        $validationRules = [
            'vehicle_id' => 'required|exists:vehicles,id',
            'unit' => 'required|string|min:1|max:255',
            'service_tasks' => 'required|string|min:1|max:255',
            'service_date' => 'required|date',
            'vendor_mechanic' => 'required|string|min:1|max:255',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|boolean',
            'is_historical' => 'nullable|boolean'
        ];
        
        // Ajustar validación de fechas según si es histórico o no
        if ($isHistorical) {
            // Para servicios históricos, permitir fechas pasadas y next_service_date puede ser anterior a service_date
            $validationRules['next_service_date'] = 'required|date';
        } else {
            // Para servicios normales, mantener validación original
            $validationRules['service_date'] .= '|before_or_equal:today';
            $validationRules['next_service_date'] = 'required|date|after:service_date';
        }
        
        // Validar los datos del formulario
        $validated = $request->validate($validationRules);

        // Verify new vehicle also belongs to carrier
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $this->validateCarrierOwnership($vehicle);
        
        try {
            DB::beginTransaction();
            
            // Actualizar el registro de mantenimiento
            $maintenance->update([
                'vehicle_id' => $request->vehicle_id,
                'unit' => $request->unit,
                'service_tasks' => $request->service_tasks,
                'service_date' => $request->service_date,
                'next_service_date' => $request->next_service_date,
                'vendor_mechanic' => $request->vendor_mechanic,
                'cost' => $request->cost,
                'odometer' => $request->odometer,
                'description' => $request->description,
                'status' => $request->status ? true : false,
                'is_historical' => $request->boolean('is_historical'),
                'updated_by' => Auth::id(),
            ]);
            
            // Procesar documentos subidos por Livewire (si hay)
            if ($request->filled('livewire_files')) {
                $this->processLivewireFiles($maintenance, json_decode($request->input('livewire_files'), true));
            }
            
            DB::commit();
            
            return redirect()->route('carrier.maintenance.index')
                ->with('success', 'Registro de mantenimiento actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar registro de mantenimiento: ' . $e->getMessage(), [
                'exception' => $e,
                'maintenance_id' => $id,
                'request' => $request->all(),
                'carrier_id' => $carrier->id,
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el registro de mantenimiento. Por favor, inténtelo de nuevo.')
                ->withInput();
        }
    }

    /**
     * Toggle maintenance status.
     * Requirements: 6.1, 6.2, 6.4
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $maintenance = VehicleMaintenance::findOrFail($id);

        // Verify carrier owns the vehicle
        $this->validateCarrierOwnership($maintenance->vehicle);

        // Invert status
        $maintenance->status = !$maintenance->status;
        $maintenance->save();

        return redirect()->back()
            ->with('success', 'Maintenance status updated successfully.');
    }

    /**
     * Delete maintenance record.
     * Requirements: 13.1, 13.2, 13.3, 13.4
     */
    public function destroy(int $id): RedirectResponse
    {
        $maintenance = VehicleMaintenance::findOrFail($id);

        // Verify carrier owns the vehicle
        $this->validateCarrierOwnership($maintenance->vehicle);

        $maintenance->delete();

        return redirect()->route('carrier.maintenance.index')
            ->with('success', 'Maintenance record deleted successfully.');
    }

    /**
     * Display maintenance calendar.
     * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5
     */
    public function calendar(Request $request): View
    {
        try {
            $carrier = $this->getCarrier();
            
            // Get filter parameters
            $vehicleId = $request->input('vehicle_id');
            $status = $request->input('status');

            // Build query for maintenance records
            $query = VehicleMaintenance::whereHas('vehicle', function($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                })
                ->with('vehicle')
                ->orderBy('next_service_date', 'asc');
            
            // Apply vehicle filter if provided
            if ($vehicleId) {
                $query->where('vehicle_id', $vehicleId);
            }
            
            // Apply status filter if provided
            if ($status !== null && $status !== '') {
                $query->where('status', $status == '1' ? true : false);
            }
            
            $maintenances = $query->get();

            // Format data for calendar events
            $events = $maintenances->map(function ($maintenance) {
                $vehicleName = $maintenance->vehicle->make . ' ' . $maintenance->vehicle->model;
                
                return [
                    'id' => $maintenance->id,
                    'title' => $maintenance->service_tasks . ' - ' . $vehicleName,
                    'start' => $maintenance->next_service_date->format('Y-m-d'),
                    'end' => $maintenance->next_service_date->format('Y-m-d'),
                    'date' => $maintenance->next_service_date->format('Y-m-d'),
                    'backgroundColor' => $this->getStatusColor($maintenance->status),
                    'borderColor' => $this->getStatusColor($maintenance->status),
                    'url' => route('carrier.maintenance.edit', $maintenance->id),
                    'vehicle_name' => $vehicleName,
                    'service_type' => $maintenance->service_tasks,
                    'cost' => $maintenance->cost ? '$' . number_format($maintenance->cost, 2) : '',
                    'description' => $maintenance->description ?? '',
                    'status' => $maintenance->status ? 1 : 0,
                    'completed' => $maintenance->status ? true : false,
                ];
            });

            // Get upcoming maintenances (next 5 pending maintenances)
            // Apply the same filters as the calendar
            $upcomingQuery = VehicleMaintenance::whereHas('vehicle', function($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                })
                ->with('vehicle')
                ->where('status', false)
                ->where('next_service_date', '>=', now())
                ->orderBy('next_service_date', 'asc');
            
            // Apply vehicle filter if provided
            if ($vehicleId) {
                $upcomingQuery->where('vehicle_id', $vehicleId);
            }
            
            // Note: Status filter is not applied here because we always want pending maintenances
            // in the "Next Maintenance" section, regardless of status filter
            
            $upcomingMaintenances = $upcomingQuery->limit(5)->get();

            // Get vehicles for filter
            $vehicles = Vehicle::where('carrier_id', $carrier->id)
                ->orderBy('make')
                ->orderBy('model')
                ->get();

            return view('carrier.maintenance.calendar', compact('events', 'upcomingMaintenances', 'vehicles', 'vehicleId', 'status'));
        } catch (\Exception $e) {
            Log::error('Error loading maintenance calendar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('maintenance_error', 'Error al cargar el calendario de mantenimiento');
        }
    }

    /**
     * Get status color for calendar events
     *
     * @param bool $status
     * @return string
     */
    private function getStatusColor($status)
    {
        if ($status) {
            return '#28a745'; // Green - Completed
        } else {
            return '#dc3545'; // Red - Pending
        }
    }

    /**
     * Reschedule maintenance.
     * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5
     */
    public function reschedule(Request $request, int $id): RedirectResponse
    {
        $maintenance = VehicleMaintenance::findOrFail($id);

        // Verify carrier owns the vehicle
        $this->validateCarrierOwnership($maintenance->vehicle);

        // Validate reschedule data
        $validated = $request->validate([
            'next_service_date' => 'required|date|after:today',
            'reschedule_reason' => 'required|string|min:3',
        ]);

        // Append note with old date, new date, timestamp, and reason
        $oldDate = $maintenance->next_service_date;
        $newDate = $validated['next_service_date'];
        $timestamp = now()->format('Y-m-d H:i:s');
        $reason = $validated['reschedule_reason'];

        $note = "\n\n[Rescheduled on {$timestamp}]\n";
        $note .= "Old date: {$oldDate}\n";
        $note .= "New date: {$newDate}\n";
        $note .= "Reason: {$reason}";

        $maintenance->description = ($maintenance->description ?? '') . $note;
        $maintenance->next_service_date = $newDate;
        $maintenance->save();

        return redirect()->route('carrier.maintenance.show', $maintenance->id)
            ->with('success', 'Maintenance rescheduled successfully.');
    }

    /**
     * Delete maintenance document via AJAX.
     * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5
     */
    public function ajaxDeleteDocument(int $mediaId): JsonResponse
    {
        try {
            // Find media record
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($mediaId);
            
            // Get maintenance record
            $maintenance = VehicleMaintenance::findOrFail($media->model_id);

            // Verify carrier owns the vehicle
            $this->validateCarrierOwnership($maintenance->vehicle);

            // Delete file and record
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting maintenance document', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document. Please try again.',
            ], 500);
        }
    }

    /**
     * Display maintenance reports.
     */
    public function reports(Request $request): View
    {
        try {
            $carrier = $this->getCarrier();

            // Get filter parameters
            $period = $request->get('period', 'all');
            $vehicleId = $request->get('vehicle_id');
            $status = $request->get('status');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Build base query for filtered data - only carrier's vehicles
            $filteredQuery = VehicleMaintenance::with('vehicle')
                ->whereHas('vehicle', function($query) use ($carrier) {
                    $query->where('carrier_id', $carrier->id);
                });
            
            if ($vehicleId) {
                $filteredQuery->where('vehicle_id', $vehicleId);
            }
            
            if ($status !== null && $status !== '') {
                $filteredQuery->where('status', $status == '1' ? true : false);
            }
            
            // Apply date filters based on period
            switch ($period) {
                case 'daily':
                    $filteredQuery->whereDate('service_date', now()->format('Y-m-d'));
                    break;
                case 'weekly':
                    $filteredQuery->whereBetween('service_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'monthly':
                    $filteredQuery->whereBetween('service_date', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                case 'yearly':
                    $filteredQuery->whereBetween('service_date', [now()->startOfYear(), now()->endOfYear()]);
                    break;
                case 'custom':
                    if ($startDate && $endDate) {
                        $filteredQuery->whereBetween('service_date', [$startDate, $endDate]);
                    }
                    break;
                case 'all':
                default:
                    // No date filter - show all records
                    break;
            }
            
            // Get paginated results for the table
            $maintenances = $filteredQuery->paginate(15);
            
            // Calculate statistics from ALL filtered records (not just paginated)
            $allFilteredMaintenances = $filteredQuery->get();
            $totalMaintenances = $allFilteredMaintenances->count();
            $totalVehiclesServiced = $allFilteredMaintenances->pluck('vehicle_id')->unique()->count();
            $totalCost = $allFilteredMaintenances->sum('cost');
            $avgCostPerVehicle = $totalVehiclesServiced > 0 ? $totalCost / $totalVehiclesServiced : 0;
            
            // Get service type distribution from all filtered records
            $serviceTypeDistribution = [];
            $serviceTypes = $allFilteredMaintenances->pluck('service_tasks')->filter()->countBy();
            $totalServices = $serviceTypes->sum();
            
            foreach ($serviceTypes as $type => $count) {
                $serviceTypeDistribution[$type] = [
                    'count' => $count,
                    'percentage' => $totalServices > 0 ? ($count / $totalServices) * 100 : 0
                ];
            }
            
            // Get upcoming maintenances (always from carrier's vehicles, not filtered)
            $upcomingMaintenances = VehicleMaintenance::with('vehicle')
                ->whereHas('vehicle', function($query) use ($carrier) {
                    $query->where('carrier_id', $carrier->id);
                })
                ->where('status', false)
                ->where('next_service_date', '>=', now())
                ->orderBy('next_service_date', 'asc')
                ->limit(10)
                ->get();
            
            // Get carrier's vehicles for filter dropdown
            $vehicles = Vehicle::where('carrier_id', $carrier->id)
                ->orderBy('make')
                ->orderBy('model')
                ->get();
            
            return view('carrier.maintenance.reports', compact(
                'maintenances',
                'totalMaintenances',
                'totalCost',
                'vehicles',
                'period',
                'vehicleId',
                'status',
                'startDate',
                'endDate',
                'serviceTypeDistribution',
                'upcomingMaintenances',
                'avgCostPerVehicle'
            ), [
                'vehiclesServiced' => $totalVehiclesServiced
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading maintenance reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error al cargar los reportes de mantenimiento');
        }
    }

    // ========================================
    // VEHICLE-SPECIFIC MAINTENANCE METHODS
    // Routes: /carrier/vehicles/{vehicle}/maintenance/*
    // ========================================

    /**
     * Display maintenance list for a specific vehicle.
     * Requirements: 1B.1, 1B.2, 1B.3, 1B.4, 1B.5
     */
    public function indexForVehicle(Vehicle $vehicle): View
    {
        // Verify carrier owns the vehicle
        $this->validateCarrierOwnership($vehicle);

        // Query maintenance records for this vehicle only
        // Order by service_date descending
        // Paginate with 10 records per page
        $maintenances = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->orderBy('service_date', 'desc')
            ->paginate(10);

        return view('carrier.vehicles.maintenance.index', compact('vehicle', 'maintenances'));
    }

    /**
     * Show create form for specific vehicle.
     * Requirements: 2B.1
     */
    public function createForVehicle(Vehicle $vehicle): View
    {
        // Verify carrier owns the vehicle
        $this->validateCarrierOwnership($vehicle);

        return view('carrier.vehicles.maintenance.create', compact('vehicle'));
    }

    /**
     * Store maintenance for specific vehicle.
     * Requirements: 2B.2, 2B.3
     */
    public function storeForVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        // Verify carrier owns the vehicle
        $this->validateCarrierOwnership($vehicle);

        // Validate all fields
        $validated = $request->validate([
            'unit' => 'required|string|max:255',
            'service_tasks' => 'required|string',
            'service_date' => 'required|date',
            'next_service_date' => 'nullable|date',
            'vendor_mechanic' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'is_historical' => 'nullable|boolean',
            'files' => 'nullable|array',
        ]);

        // Conditional date validation
        if (!$request->boolean('is_historical')) {
            $request->validate([
                'service_date' => 'required|date|before_or_equal:today',
                'next_service_date' => 'nullable|date|after:service_date',
            ]);
        }

        try {
            DB::beginTransaction();

            // Default next_service_date to service_date + 3 months if not provided
            $nextServiceDate = $validated['next_service_date'] ?? null;
            if (empty($nextServiceDate)) {
                try {
                    $nextServiceDate = Carbon::parse($validated['service_date'])->addMonths(3)->format('Y-m-d');
                } catch (\Exception $e) {
                    $nextServiceDate = null;
                }
            }

            // Create with vehicle_id from route
            $maintenance = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id,
                'unit' => $validated['unit'],
                'service_tasks' => $validated['service_tasks'],
                'service_date' => $validated['service_date'],
                'next_service_date' => $nextServiceDate,
                'vendor_mechanic' => $validated['vendor_mechanic'],
                'cost' => $validated['cost'],
                'odometer' => $validated['odometer'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? false,
                'is_historical' => $validated['is_historical'] ?? false,
                'created_by' => Auth::id(),
            ]);

            // Process files
            if ($request->has('files')) {
                $filesData = $request->input('files');
                if (is_string($filesData)) {
                    $filesData = json_decode($filesData, true);
                }
                if (is_array($filesData) && count($filesData) > 0) {
                    $this->processLivewireFiles($maintenance, $filesData);
                }
            }

            DB::commit();

            return redirect()->route('carrier.vehicles.show', $vehicle->id)
                ->with('success', 'Maintenance record created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating maintenance record', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create maintenance record. Please try again.');
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Get authenticated carrier.
     */
    private function getCarrier()
    {
        return Auth::user()->carrierDetails->carrier;
    }

    /**
     * Validate carrier owns the vehicle.
     * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5
     * 
     * @param Vehicle $vehicle
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function validateCarrierOwnership(Vehicle $vehicle): void
    {
        $carrier = $this->getCarrier();
        
        if ($vehicle->carrier_id !== $carrier->id) {
            Log::warning('Unauthorized vehicle maintenance access attempt', [
                'user_id' => Auth::id(),
                'carrier_id' => $carrier->id,
                'vehicle_id' => $vehicle->id,
                'vehicle_carrier_id' => $vehicle->carrier_id,
            ]);
            
            abort(404);
        }
    }

    /**
     * Process Livewire uploaded files and attach to maintenance record.
     * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7
     * 
     * @param VehicleMaintenance $maintenance
     * @param array $filesData
     * @return void
     */
    private function processLivewireFiles(VehicleMaintenance $maintenance, array $filesData): void
    {
        $successCount = 0;
        $failedFiles = [];

        foreach ($filesData as $fileData) {
            try {
                // Handle both nested and flat array formats
                $tempPath = null;
                $originalName = null;
                
                if (is_array($fileData)) {
                    // Nested format from Livewire FileUploader component
                    $tempPath = $fileData['tempPath'] ?? $fileData['path'] ?? null;
                    $originalName = $fileData['originalName'] ?? $fileData['name'] ?? null;
                } elseif (is_string($fileData)) {
                    // Flat format (just the path)
                    $tempPath = $fileData;
                }

                if (!$tempPath) {
                    $failedFiles[] = $originalName ?? 'Unknown file';
                    continue;
                }

                // Try multiple possible temp directories
                $possiblePaths = [
                    storage_path('app/' . $tempPath),
                    storage_path('app/temp/' . basename($tempPath)),
                    storage_path('app/livewire-tmp/' . basename($tempPath)),
                ];

                $fullPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $fullPath = $path;
                        break;
                    }
                }

                // Check if temporary file exists
                if (!$fullPath) {
                    Log::warning('Livewire temp file not found', [
                        'temp_path' => $tempPath,
                        'maintenance_id' => $maintenance->id,
                        'tried_paths' => $possiblePaths,
                    ]);
                    $failedFiles[] = $originalName ?? basename($tempPath);
                    continue;
                }

                // Store file in Spatie Media Library
                $media = $maintenance->addMedia($fullPath)
                    ->withCustomProperties([
                        'maintenance_id' => $maintenance->id,
                        'vehicle_id' => $maintenance->vehicle_id,
                        'uploaded_at' => now()->toDateTimeString(),
                        'original_name' => $originalName ?? basename($tempPath),
                    ])
                    ->toMediaCollection('maintenance_files');

                $successCount++;

                Log::info('Maintenance file processed successfully', [
                    'maintenance_id' => $maintenance->id,
                    'media_id' => $media->id,
                    'original_name' => $originalName,
                ]);

            } catch (\Exception $e) {
                Log::error('Error processing maintenance file', [
                    'maintenance_id' => $maintenance->id,
                    'file_data' => $fileData,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failedFiles[] = is_array($fileData) ? ($fileData['originalName'] ?? $fileData['name'] ?? 'Unknown') : basename($fileData);
            }
        }

        if (count($failedFiles) > 0) {
            Log::info('Some files failed to upload', [
                'maintenance_id' => $maintenance->id,
                'success_count' => $successCount,
                'failed_files' => $failedFiles,
            ]);
        }

        Log::info('File processing completed', [
            'maintenance_id' => $maintenance->id,
            'success_count' => $successCount,
            'failed_count' => count($failedFiles),
        ]);
    }

    /**
     * Generate a maintenance report PDF for a single maintenance record.
     */
    public function generateReport(VehicleMaintenance $maintenance): RedirectResponse
    {
        try {
            $carrier = $this->getCarrier();
            $vehicle = $maintenance->vehicle;

            // Verify ownership
            if ($vehicle->carrier_id !== $carrier->id) {
                abort(403, 'Unauthorized');
            }

            $vehicle->load('carrier');

            $pdf = Pdf::loadView('admin.vehicles.maintenances.report-pdf', [
                'vehicle' => $vehicle,
                'maintenances' => collect([$maintenance]),
            ])->setPaper('letter', 'portrait');

            $fileName = 'maintenance_report_' . $vehicle->id . '_' . $maintenance->id . '_' . now()->format('Ymd_His') . '.pdf';
            $tempPath = storage_path('app/temp/' . $fileName);

            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            $document = VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type' => VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD,
                'document_number' => 'MR-' . $vehicle->id . '-' . $maintenance->id . '-' . now()->format('Ymd'),
                'issued_date' => now(),
                'status' => VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Maintenance Report for ' . $vehicle->make . ' ' . $vehicle->model . ' (' . $vehicle->year . '). Service: ' . $maintenance->service_tasks . '. Generated on ' . now()->format('m/d/Y h:i A'),
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');

            return redirect()
                ->back()
                ->with('maintenance_success', 'Maintenance report generated successfully and saved to vehicle documents.');

        } catch (\Exception $e) {
            Log::error('Error generating maintenance report (carrier)', [
                'maintenance_id' => $maintenance->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('maintenance_error', 'Error generating maintenance report: ' . $e->getMessage());
        }
    }

    /**
     * Store documents for a maintenance record (direct upload from show page).
     */
    public function storeDocumentsFromShow(VehicleMaintenance $maintenance, Request $request): RedirectResponse
    {
        try {
            $carrier = $this->getCarrier();
            $vehicle = $maintenance->vehicle;

            if ($vehicle->carrier_id !== $carrier->id) {
                abort(403, 'Unauthorized');
            }

            $uploadedCount = 0;

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $maintenance->addMedia($document->getPathname())
                        ->usingName($document->getClientOriginalName())
                        ->withCustomProperties([
                            'maintenance_id' => $maintenance->id,
                            'vehicle_id' => $maintenance->vehicle_id,
                            'uploaded_at' => now()->format('Y-m-d H:i:s'),
                            'original_name' => $document->getClientOriginalName()
                        ])
                        ->toMediaCollection('maintenance_files');
                    $uploadedCount++;
                }
            }

            if ($uploadedCount === 0) {
                return redirect()->back()->with('maintenance_error', 'No files were uploaded.');
            }

            return redirect()->back()->with('maintenance_success', "$uploadedCount document(s) uploaded successfully.");

        } catch (\Exception $e) {
            Log::error('Error uploading maintenance documents (carrier)', [
                'maintenance_id' => $maintenance->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('maintenance_error', 'Error uploading documents: ' . $e->getMessage());
        }
    }

    /**
     * Delete a generated maintenance report (VehicleDocument).
     */
    public function deleteReport(VehicleMaintenance $maintenance, VehicleDocument $report): RedirectResponse
    {
        try {
            $carrier = $this->getCarrier();
            $vehicle = $maintenance->vehicle;

            if ($vehicle->carrier_id !== $carrier->id) {
                abort(403, 'Unauthorized');
            }

            if ($report->vehicle_id !== $maintenance->vehicle_id) {
                return back()->with('maintenance_error', 'Report does not belong to this maintenance record.');
            }

            $report->clearMediaCollection('document_files');
            $report->delete();

            Log::info('Maintenance report deleted (carrier)', [
                'maintenance_id' => $maintenance->id,
                'report_id' => $report->id,
            ]);

            return back()->with('maintenance_success', 'Report deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting maintenance report (carrier)', [
                'maintenance_id' => $maintenance->id,
                'report_id' => $report->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->with('maintenance_error', 'Error deleting report: ' . $e->getMessage());
        }
    }
}
