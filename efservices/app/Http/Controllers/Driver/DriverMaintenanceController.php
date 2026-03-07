<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DriverMaintenanceController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->driverDetail) {
                abort(403, 'Access denied. Driver profile not found.');
            }
            return $next($request);
        });
    }

    /**
     * Get the authenticated driver's detail.
     */
    private function getDriverDetail()
    {
        return Auth::user()->driverDetail;
    }

    /**
     * Get the driver's assigned vehicle.
     */
    private function getDriverVehicle()
    {
        $driver = $this->getDriverDetail();
        
        // Check if driver has an active vehicle assignment
        $vehicleAssignment = $driver->activeVehicleAssignment;
        
        if ($vehicleAssignment && $vehicleAssignment->vehicle) {
            return $vehicleAssignment->vehicle;
        }
        
        // Fallback to assigned_vehicle_id if exists
        if ($driver->assigned_vehicle_id) {
            return Vehicle::find($driver->assigned_vehicle_id);
        }
        
        return null;
    }

    /**
     * Show the form for creating a new maintenance record.
     */
    public function create()
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you. Cannot create maintenance record.');
        }
        
        // Maintenance types
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
        
        return view('driver.maintenance.create', compact('driver', 'vehicle', 'maintenanceTypes'));
    }

    /**
     * Store a newly created maintenance record.
     */
    public function store(Request $request)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you. Cannot create maintenance record.');
        }
        
        // Validate
        $validated = $request->validate([
            'unit' => 'required|string|max:255',
            'service_tasks' => 'required|string|max:255',
            'service_date' => 'required|date|before_or_equal:today',
            'next_service_date' => 'required|date|after:service_date',
            'vendor_mechanic' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|boolean',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create maintenance record
            $maintenance = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id,
                'unit' => $validated['unit'],
                'service_tasks' => $validated['service_tasks'],
                'service_date' => $validated['service_date'],
                'next_service_date' => $validated['next_service_date'],
                'vendor_mechanic' => $validated['vendor_mechanic'],
                'cost' => $validated['cost'],
                'odometer' => $validated['odometer'],
                'description' => $validated['description'],
                'status' => $request->boolean('status'),
                'created_by' => Auth::id(),
            ]);
            
            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $maintenance->addMedia($file)
                        ->withCustomProperties([
                            'uploaded_by_driver' => true,
                            'driver_id' => $driver->id,
                            'driver_name' => $driver->user->name,
                            'uploaded_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ])
                        ->toMediaCollection('maintenance_files');
                }
            }
            
            DB::commit();
            
            Log::info('Driver created maintenance record', [
                'maintenance_id' => $maintenance->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id
            ]);
            
            return redirect()->route('driver.maintenance.show', $maintenance->id)
                ->with('success', 'Maintenance record created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating maintenance record by driver', [
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating maintenance record. Please try again.');
        }
    }

    /**
     * Display a listing of maintenance records for driver's assigned vehicle.
     */
    public function index(Request $request)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return view('driver.maintenance.index', [
                'driver' => $driver,
                'vehicle' => null,
                'maintenances' => collect([]),
                'stats' => [
                    'total' => 0,
                    'pending' => 0,
                    'completed' => 0,
                    'overdue' => 0,
                    'upcoming' => 0,
                ]
            ]);
        }
        
        // Build query for maintenance records
        $query = VehicleMaintenance::where('vehicle_id', $vehicle->id);
        
        // Apply status filter if provided
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'pending') {
                $query->where('status', false);
            } elseif ($status === 'completed') {
                $query->where('status', true);
            } elseif ($status === 'overdue') {
                $query->where('status', false)
                      ->where('next_service_date', '<', Carbon::now());
            } elseif ($status === 'upcoming') {
                $query->where('status', false)
                      ->where('next_service_date', '>=', Carbon::now())
                      ->where('next_service_date', '<=', Carbon::now()->addDays(30));
            }
        }
        
        // Order by next service date
        $maintenances = $query->orderBy('next_service_date', 'desc')->paginate(10);
        
        // Calculate statistics
        $stats = [
            'total' => VehicleMaintenance::where('vehicle_id', $vehicle->id)->count(),
            'pending' => VehicleMaintenance::where('vehicle_id', $vehicle->id)->where('status', false)->count(),
            'completed' => VehicleMaintenance::where('vehicle_id', $vehicle->id)->where('status', true)->count(),
            'overdue' => VehicleMaintenance::where('vehicle_id', $vehicle->id)
                ->where('status', false)
                ->where('next_service_date', '<', Carbon::now())
                ->count(),
            'upcoming' => VehicleMaintenance::where('vehicle_id', $vehicle->id)
                ->where('status', false)
                ->where('next_service_date', '>=', Carbon::now())
                ->where('next_service_date', '<=', Carbon::now()->addDays(30))
                ->count(),
        ];
        
        return view('driver.maintenance.index', compact('driver', 'vehicle', 'maintenances', 'stats'));
    }

    /**
     * Show the form for editing the maintenance record.
     */
    public function edit($id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you.');
        }
        
        // Find maintenance record and verify it belongs to driver's vehicle
        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->with('vehicle', 'media')
            ->firstOrFail();
        
        // Maintenance types
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
        
        return view('driver.maintenance.edit', compact('driver', 'vehicle', 'maintenance', 'maintenanceTypes'));
    }

    /**
     * Update the specified maintenance record.
     */
    public function update(Request $request, $id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you.');
        }
        
        // Find maintenance record and verify it belongs to driver's vehicle
        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        // Validate
        $validated = $request->validate([
            'unit' => 'required|string|max:255',
            'service_tasks' => 'required|string|max:255',
            'service_date' => 'required|date|before_or_equal:today',
            'next_service_date' => 'required|date|after:service_date',
            'vendor_mechanic' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|boolean',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Update maintenance record
            $maintenance->update([
                'unit' => $validated['unit'],
                'service_tasks' => $validated['service_tasks'],
                'service_date' => $validated['service_date'],
                'next_service_date' => $validated['next_service_date'],
                'vendor_mechanic' => $validated['vendor_mechanic'],
                'cost' => $validated['cost'],
                'odometer' => $validated['odometer'],
                'description' => $validated['description'],
                'status' => $request->boolean('status'),
                'updated_by' => Auth::id(),
            ]);
            
            // Handle new document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $maintenance->addMedia($file)
                        ->withCustomProperties([
                            'uploaded_by_driver' => true,
                            'driver_id' => $driver->id,
                            'driver_name' => $driver->user->name,
                            'uploaded_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ])
                        ->toMediaCollection('maintenance_files');
                }
            }
            
            DB::commit();
            
            Log::info('Driver updated maintenance record', [
                'maintenance_id' => $maintenance->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id
            ]);
            
            return redirect()->route('driver.maintenance.show', $maintenance->id)
                ->with('success', 'Maintenance record updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating maintenance record by driver', [
                'maintenance_id' => $id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating maintenance record. Please try again.');
        }
    }

    /**
     * Display the specified maintenance record.
     */
    public function show($id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            abort(404, 'No vehicle assigned to you.');
        }
        
        // Find maintenance record and verify it belongs to driver's vehicle
        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->with('vehicle', 'media')
            ->firstOrFail();
        
        return view('driver.maintenance.show', compact('driver', 'vehicle', 'maintenance'));
    }

    /**
     * Mark maintenance as completed.
     */
    public function complete(Request $request, $id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->back()->with('error', 'No vehicle assigned to you.');
        }
        
        // Find maintenance record and verify it belongs to driver's vehicle
        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        // Validate
        $request->validate([
            'completion_notes' => 'nullable|string|max:1000',
            'completion_odometer' => 'nullable|integer|min:0',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Update maintenance status
            $maintenance->status = true;
            
            // Add completion notes
            $completionNote = "\n\n[Completed by driver on " . Carbon::now()->format('Y-m-d H:i:s') . "]";
            $completionNote .= "\nDriver: " . $driver->user->name;
            
            if ($request->filled('completion_notes')) {
                $completionNote .= "\nNotes: " . $request->completion_notes;
            }
            
            if ($request->filled('completion_odometer')) {
                $completionNote .= "\nOdometer: " . $request->completion_odometer;
            }
            
            $maintenance->description = ($maintenance->description ?? '') . $completionNote;
            $maintenance->updated_by = Auth::id();
            $maintenance->save();
            
            DB::commit();
            
            Log::info('Driver completed maintenance', [
                'maintenance_id' => $maintenance->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id
            ]);
            
            return redirect()->route('driver.maintenance.show', $maintenance->id)
                ->with('success', 'Maintenance marked as completed successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error completing maintenance by driver', [
                'maintenance_id' => $id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error marking maintenance as completed. Please try again.');
        }
    }

    /**
     * Upload documentation for maintenance.
     */
    public function uploadDocument(Request $request, $id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->back()->with('error', 'No vehicle assigned to you.');
        }
        
        // Find maintenance record and verify it belongs to driver's vehicle
        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        // Validate
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_description' => 'nullable|string|max:255',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Upload document using Spatie Media Library
            $media = $maintenance->addMedia($request->file('document'))
                ->withCustomProperties([
                    'uploaded_by_driver' => true,
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->user->name,
                    'uploaded_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'description' => $request->input('document_description', '')
                ])
                ->toMediaCollection('maintenance_files');
            
            DB::commit();
            
            Log::info('Driver uploaded maintenance document', [
                'maintenance_id' => $maintenance->id,
                'driver_id' => $driver->id,
                'media_id' => $media->id,
                'file_name' => $media->file_name
            ]);
            
            return redirect()->back()
                ->with('success', 'Document uploaded successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error uploading maintenance document by driver', [
                'maintenance_id' => $id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading document. Please try again.');
        }
    }

    /**
     * Delete the specified maintenance record.
     */
    public function destroy($id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->back()->with('error', 'No vehicle assigned to you.');
        }
        
        // Find maintenance record and verify it belongs to driver's vehicle
        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        try {
            // Delete all associated media files
            $maintenance->clearMediaCollection('maintenance_files');
            
            // Delete the maintenance record
            $maintenance->delete();
            
            Log::info('Driver deleted maintenance record', [
                'maintenance_id' => $id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id
            ]);
            
            return redirect()->route('driver.maintenance.index')
                ->with('success', 'Maintenance record deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting maintenance record by driver', [
                'maintenance_id' => $id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting maintenance record. Please try again.');
        }
    }

    /**
     * Delete a maintenance document.
     */
    public function deleteDocument($maintenanceId, $mediaId)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->back()->with('error', 'No vehicle assigned to you.');
        }
        
        // Find maintenance record and verify it belongs to driver's vehicle
        $maintenance = VehicleMaintenance::where('id', $maintenanceId)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        try {
            // Find media
            $media = $maintenance->getMedia('maintenance_files')->where('id', $mediaId)->first();
            
            if (!$media) {
                return redirect()->back()->with('error', 'Document not found.');
            }
            
            // Check if document was uploaded by this driver
            $uploadedByDriver = $media->getCustomProperty('uploaded_by_driver', false);
            $uploadedDriverId = $media->getCustomProperty('driver_id');
            
            if (!$uploadedByDriver || $uploadedDriverId != $driver->id) {
                return redirect()->back()->with('error', 'You can only delete documents you uploaded.');
            }
            
            // Delete media
            $media->delete();
            
            Log::info('Driver deleted maintenance document', [
                'maintenance_id' => $maintenanceId,
                'driver_id' => $driver->id,
                'media_id' => $mediaId
            ]);
            
            return redirect()->back()
                ->with('success', 'Document deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting maintenance document by driver', [
                'maintenance_id' => $maintenanceId,
                'driver_id' => $driver->id,
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting document. Please try again.');
        }
    }
}

