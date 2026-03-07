<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\EmergencyRepair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DriverEmergencyRepairController extends Controller
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
     * Display a listing of emergency repairs for driver's vehicle.
     */
    public function index(Request $request)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return view('driver.emergency-repairs.index', [
                'driver' => $driver,
                'vehicle' => null,
                'emergencyRepairs' => collect([]),
                'stats' => [
                    'total' => 0,
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'total_cost' => 0,
                ]
            ]);
        }
        
        // Build query for emergency repairs
        $query = EmergencyRepair::where('vehicle_id', $vehicle->id);
        
        // Apply status filter if provided
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Apply date range filters
        if ($request->filled('start_date')) {
            $query->where('repair_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('repair_date', '<=', $request->end_date);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('repair_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('notes', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Order by repair date descending
        $emergencyRepairs = $query->orderBy('repair_date', 'desc')->paginate(10);
        
        // Calculate statistics
        $stats = [
            'total' => EmergencyRepair::where('vehicle_id', $vehicle->id)->count(),
            'pending' => EmergencyRepair::where('vehicle_id', $vehicle->id)->where('status', 'pending')->count(),
            'in_progress' => EmergencyRepair::where('vehicle_id', $vehicle->id)->where('status', 'in_progress')->count(),
            'completed' => EmergencyRepair::where('vehicle_id', $vehicle->id)->where('status', 'completed')->count(),
            'total_cost' => EmergencyRepair::where('vehicle_id', $vehicle->id)->sum('cost'),
        ];
        
        return view('driver.emergency-repairs.index', compact('driver', 'vehicle', 'emergencyRepairs', 'stats'));
    }

    /**
     * Show the form for creating a new emergency repair.
     */
    public function create()
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you. Cannot create emergency repair.');
        }
        
        return view('driver.emergency-repairs.create', compact('driver', 'vehicle'));
    }

    /**
     * Store a newly created emergency repair.
     */
    public function store(Request $request)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you. Cannot create emergency repair.');
        }
        
        Log::info('Creating emergency repair for driver', [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'request_data' => $request->except(['_token']),
        ]);
        
        // Convert date format from MM/DD/YYYY to Y-m-d before validation
        if ($request->has('repair_date') && $request->repair_date) {
            try {
                $parsedDate = Carbon::createFromFormat('m/d/Y', $request->repair_date);
                $request->merge(['repair_date' => $parsedDate->format('Y-m-d')]);
            } catch (\Exception $e) {
                // Try other common formats
                try {
                    $parsedDate = Carbon::parse($request->repair_date);
                    $request->merge(['repair_date' => $parsedDate->format('Y-m-d')]);
                } catch (\Exception $e2) {
                    Log::warning('Could not parse repair_date', ['repair_date' => $request->repair_date]);
                }
            }
        }
        
        // Validate
        $validated = $request->validate([
            'repair_name' => 'required|string|max:255',
            'repair_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
            'status' => 'required|in:pending,in_progress,completed',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'repair_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create emergency repair
            $emergencyRepair = EmergencyRepair::create([
                'vehicle_id' => $vehicle->id,
                'repair_name' => $validated['repair_name'],
                'repair_date' => $validated['repair_date'],
                'cost' => $validated['cost'],
                'odometer' => $validated['odometer'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'],
                'notes' => $validated['notes'],
            ]);
            
            Log::info('Emergency repair saved for driver', [
                'driver_id' => $driver->id,
                'repair_id' => $emergencyRepair->id,
                'data_saved' => $emergencyRepair->toArray()
            ]);
            
            // Process repair files if they exist
            if ($request->hasFile('repair_files')) {
                Log::info('Emergency repair files found', [
                    'driver_id' => $driver->id,
                    'file_count' => count($request->file('repair_files'))
                ]);
                
                foreach ($request->file('repair_files') as $file) {
                    Log::info('Processing file', [
                        'name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize()
                    ]);
                    
                    try {
                        $media = $emergencyRepair->addMedia($file)
                            ->withCustomProperties([
                                'uploaded_by_driver' => true,
                                'driver_id' => $driver->id,
                                'driver_name' => $driver->user->name,
                                'uploaded_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ])
                            ->toMediaCollection('emergency_repair_files');
                        
                        Log::info('File saved successfully', [
                            'media_id' => $media->id,
                            'file_name' => $media->file_name
                        ]);
                    } catch (\Exception $fileError) {
                        Log::error('Error saving individual file', [
                            'file_name' => $file->getClientOriginalName(),
                            'error' => $fileError->getMessage()
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('driver.emergency-repairs.show', $emergencyRepair->id)
                ->with('success', 'Emergency repair created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating emergency repair by driver', [
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating emergency repair. Please try again.');
        }
    }

    /**
     * Display the specified emergency repair.
     */
    public function show($id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            abort(404, 'No vehicle assigned to you.');
        }
        
        // Find emergency repair and verify it belongs to driver's vehicle
        $emergencyRepair = EmergencyRepair::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->with('vehicle', 'media')
            ->firstOrFail();
        
        return view('driver.emergency-repairs.show', compact('driver', 'vehicle', 'emergencyRepair'));
    }

    /**
     * Show the form for editing the emergency repair.
     */
    public function edit($id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you.');
        }
        
        // Find emergency repair and verify it belongs to driver's vehicle
        $emergencyRepair = EmergencyRepair::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->with('vehicle', 'media')
            ->firstOrFail();
        
        return view('driver.emergency-repairs.edit', compact('driver', 'vehicle', 'emergencyRepair'));
    }

    /**
     * Update the specified emergency repair.
     */
    public function update(Request $request, $id)
    {
        Log::info('Driver emergency repair update started', [
            'repair_id' => $id,
            'request_data' => $request->except(['_token', '_method', 'repair_files']),
            'has_files' => $request->hasFile('repair_files')
        ]);
        
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            Log::warning('Driver has no vehicle assigned', ['driver_id' => $driver->id]);
            return redirect()->route('driver.dashboard')
                ->with('error', 'No vehicle assigned to you.');
        }
        
        // Find emergency repair and verify it belongs to driver's vehicle
        $emergencyRepair = EmergencyRepair::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        Log::info('Emergency repair found', [
            'repair_id' => $emergencyRepair->id,
            'vehicle_id' => $emergencyRepair->vehicle_id,
            'current_data' => $emergencyRepair->toArray()
        ]);
        
        // Convert date format from MM/DD/YYYY to Y-m-d before validation
        if ($request->has('repair_date') && $request->repair_date) {
            try {
                $parsedDate = Carbon::createFromFormat('m/d/Y', $request->repair_date);
                $request->merge(['repair_date' => $parsedDate->format('Y-m-d')]);
            } catch (\Exception $e) {
                // Try other common formats
                try {
                    $parsedDate = Carbon::parse($request->repair_date);
                    $request->merge(['repair_date' => $parsedDate->format('Y-m-d')]);
                } catch (\Exception $e2) {
                    Log::warning('Could not parse repair_date', ['repair_date' => $request->repair_date]);
                }
            }
        }
        
        // Validate
        $validated = $request->validate([
            'repair_name' => 'required|string|max:255',
            'repair_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
            'status' => 'required|in:pending,in_progress,completed',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'repair_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);
        
        Log::info('Validation passed', ['validated_data' => $validated]);
        
        try {
            DB::beginTransaction();
            
            Log::info('Updating emergency repair', [
                'repair_id' => $emergencyRepair->id,
                'update_data' => [
                    'repair_name' => $validated['repair_name'],
                    'repair_date' => $validated['repair_date'],
                    'cost' => $validated['cost'],
                    'odometer' => $validated['odometer'] ?? null,
                    'status' => $validated['status'],
                    'description' => $validated['description'],
                    'notes' => $validated['notes'],
                ]
            ]);
            
            // Update emergency repair
            $emergencyRepair->update([
                'repair_name' => $validated['repair_name'],
                'repair_date' => $validated['repair_date'],
                'cost' => $validated['cost'],
                'odometer' => $validated['odometer'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'],
                'notes' => $validated['notes'],
            ]);
            
            Log::info('Emergency repair updated successfully', [
                'repair_id' => $emergencyRepair->id,
                'updated_data' => $emergencyRepair->fresh()->toArray()
            ]);
            
            // Process new repair files if they exist
            if ($request->hasFile('repair_files')) {
                foreach ($request->file('repair_files') as $file) {
                    $emergencyRepair->addMedia($file)
                        ->withCustomProperties([
                            'uploaded_by_driver' => true,
                            'driver_id' => $driver->id,
                            'driver_name' => $driver->user->name,
                            'uploaded_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ])
                        ->toMediaCollection('emergency_repair_files');
                }
            }
            
            DB::commit();
            
            Log::info('Driver updated emergency repair', [
                'repair_id' => $emergencyRepair->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id
            ]);
            
            return redirect()->route('driver.emergency-repairs.show', $emergencyRepair->id)
                ->with('success', 'Emergency repair updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating emergency repair by driver', [
                'repair_id' => $id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating emergency repair. Please try again.');
        }
    }

    /**
     * Upload documentation for emergency repair.
     */
    public function uploadDocument(Request $request, $id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->back()->with('error', 'No vehicle assigned to you.');
        }
        
        // Find emergency repair and verify it belongs to driver's vehicle
        $emergencyRepair = EmergencyRepair::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        // Validate
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'document_description' => 'nullable|string|max:255',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Upload document using Spatie Media Library
            $media = $emergencyRepair->addMedia($request->file('document'))
                ->withCustomProperties([
                    'uploaded_by_driver' => true,
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->user->name,
                    'uploaded_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'description' => $request->input('document_description', '')
                ])
                ->toMediaCollection('emergency_repair_files');
            
            DB::commit();
            
            Log::info('Driver uploaded emergency repair document', [
                'repair_id' => $emergencyRepair->id,
                'driver_id' => $driver->id,
                'media_id' => $media->id,
                'file_name' => $media->file_name
            ]);
            
            return redirect()->back()
                ->with('success', 'Document uploaded successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error uploading emergency repair document by driver', [
                'repair_id' => $id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading document. Please try again.');
        }
    }

    /**
     * Delete an emergency repair document.
     */
    public function deleteDocument($repairId, $mediaId)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->back()->with('error', 'No vehicle assigned to you.');
        }
        
        // Find emergency repair and verify it belongs to driver's vehicle
        $emergencyRepair = EmergencyRepair::where('id', $repairId)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        try {
            // Find media
            $media = $emergencyRepair->getMedia('emergency_repair_files')->where('id', $mediaId)->first();
            
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
            
            Log::info('Driver deleted emergency repair document', [
                'repair_id' => $repairId,
                'driver_id' => $driver->id,
                'media_id' => $mediaId
            ]);
            
            return redirect()->back()
                ->with('success', 'Document deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting emergency repair document by driver', [
                'repair_id' => $repairId,
                'driver_id' => $driver->id,
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting document. Please try again.');
        }
    }

    /**
     * Remove the specified emergency repair.
     */
    public function destroy($id)
    {
        $driver = $this->getDriverDetail();
        $vehicle = $this->getDriverVehicle();
        
        if (!$vehicle) {
            return redirect()->back()->with('error', 'No vehicle assigned to you.');
        }
        
        // Find emergency repair and verify it belongs to driver's vehicle
        $emergencyRepair = EmergencyRepair::where('id', $id)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();
        
        try {
            // Delete all associated files
            $emergencyRepair->clearMediaCollection('emergency_repair_files');
            
            $emergencyRepair->delete();

            Log::info('Driver deleted emergency repair', [
                'repair_id' => $id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id
            ]);

            return redirect()->route('driver.emergency-repairs.index')
                ->with('success', 'Emergency repair deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting emergency repair by driver', [
                'repair_id' => $id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Error deleting emergency repair: ' . $e->getMessage());
        }
    }
}

