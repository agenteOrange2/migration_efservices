<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\EmergencyRepair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Admin\Vehicle\VehicleDocument;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierEmergencyRepairController extends Controller
{
    /**
     * Display a listing of emergency repairs for the carrier.
     */
    public function index(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $query = EmergencyRepair::with(['vehicle', 'vehicle.carrier', 'vehicle.currentDriverAssignment.driver.user'])
            ->whereHas('vehicle', function ($q) use ($carrier) {
                $q->where('carrier_id', $carrier->id);
            });

        // Filter by driver
        if ($request->filled('driver_id')) {
            $query->whereHas('vehicle.currentDriverAssignment', function ($q) use ($request) {
                $q->where('user_driver_detail_id', $request->driver_id);
            });
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('repair_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('repair_date', '<=', $request->end_date);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('repair_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                      $vehicleQuery->where('make', 'like', "%{$search}%")
                                   ->orWhere('model', 'like', "%{$search}%")
                                   ->orWhere('vin', 'like', "%{$search}%")
                                   ->orWhere('company_unit_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Get current tab for filtering
        $currentTab = $request->get('tab', 'all');
        
        // Apply tab-based filtering
        if ($currentTab !== 'all') {
            $query->where('status', $currentTab);
        }
        
        $emergencyRepairs = $query->orderBy('repair_date', 'desc')->paginate(15);
        
        // Get drivers for filters (only from this carrier)
        $drivers = collect();
        $carrierVehicles = Vehicle::where('carrier_id', $carrier->id)
            ->with(['currentDriverAssignment', 'currentDriverAssignment.driver', 'currentDriverAssignment.driver.user'])
            ->get();
        
        $drivers = $carrierVehicles->filter(function($vehicle) {
            return $vehicle->currentDriverAssignment && $vehicle->currentDriverAssignment->driver;
        })->pluck('currentDriverAssignment.driver')->unique('id')->sortBy(function($driver) {
            return $driver->user->name ?? '';
        });
        
        // Get statistics for filter cards
        $totalCount = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->count();
        
        $pendingCount = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->where('status', 'pending')->count();
        
        $inProgressCount = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->where('status', 'in_progress')->count();
        
        $completedCount = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->where('status', 'completed')->count();
        
        // Calculate total costs
        $totalCost = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->sum('cost');
        
        $pendingCost = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->where('status', 'pending')->sum('cost');
        
        $inProgressCost = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->where('status', 'in_progress')->sum('cost');
        
        $completedCost = EmergencyRepair::whereHas('vehicle', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->where('status', 'completed')->sum('cost');

        return view('carrier.vehicles.emergency-repairs.index', compact(
            'emergencyRepairs', 
            'drivers',
            'currentTab',
            'totalCount',
            'pendingCount',
            'inProgressCount',
            'completedCount',
            'totalCost',
            'pendingCost',
            'inProgressCost',
            'completedCost'
        ));
    }

    /**
     * Show the form for creating a new emergency repair.
     */
    public function create(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        $vehicles = collect();
        
        // If vehicle_id is provided, get that specific vehicle
        if ($request->filled('vehicle_id')) {
            $vehicle = Vehicle::where('carrier_id', $carrier->id)
                ->findOrFail($request->vehicle_id);
            $vehicles = collect([$vehicle]);
        } else {
            // Get all vehicles for this carrier
            $vehicles = Vehicle::where('carrier_id', $carrier->id)
                ->orderBy('company_unit_number')
                ->get();
        }
        
        return view('carrier.vehicles.emergency-repairs.create', compact('vehicles'));
    }

    /**
     * Store a newly created emergency repair.
     */
    public function store(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        Log::info('Creating emergency repair for carrier', [
            'carrier_id' => $carrier->id,
            'request_data' => $request->except(['_token']),
            'request_has_files' => $request->hasFile('repair_files')
        ]);

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_name' => 'required|string|max:255',
            'repair_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
            'status' => 'required|in:pending,in_progress,completed',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for emergency repair creation', [
                'carrier_id' => $carrier->id,
                'errors' => $validator->errors()->toArray()
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Verify vehicle belongs to carrier
            $vehicle = Vehicle::where('id', $request->vehicle_id)
                ->where('carrier_id', $carrier->id)
                ->firstOrFail();
            
            DB::beginTransaction();

            $emergencyRepair = new EmergencyRepair([
                'vehicle_id' => $request->vehicle_id,
                'repair_name' => $request->repair_name,
                'repair_date' => $request->repair_date,
                'cost' => $request->cost,
                'odometer' => $request->odometer,
                'status' => $request->status,
                'description' => $request->description,
                'notes' => $request->notes,
            ]);

            $result = $emergencyRepair->save();

            Log::info('Emergency repair saved for carrier', [
                'carrier_id' => $carrier->id,
                'repair_id' => $emergencyRepair->id,
                'save_result' => $result,
                'data_saved' => $emergencyRepair->toArray()
            ]);

            // Process repair files if they exist
            if ($request->hasFile('repair_files')) {
                Log::info('Emergency repair files found', [
                    'carrier_id' => $carrier->id,
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
                            ->toMediaCollection('emergency_repair_files');

                        Log::info('File saved successfully', [
                            'media_id' => $media->id,
                            'file_name' => $media->file_name
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error saving file', [
                            'error' => $e->getMessage(),
                            'file_name' => $file->getClientOriginalName()
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('carrier.emergency-repairs.index')
                ->with('success', 'Emergency repair created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving emergency repair for carrier', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error saving emergency repair: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified emergency repair.
     */
    public function show(EmergencyRepair $emergencyRepair)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify vehicle belongs to carrier
        $this->verifyRepairAccess($emergencyRepair, $carrier);
        
        $emergencyRepair->load(['vehicle', 'vehicle.carrier', 'vehicle.driver']);
        
        return view('carrier.vehicles.emergency-repairs.show', compact('emergencyRepair'));
    }

    /**
     * Show the form for editing the specified emergency repair.
     */
    public function edit(EmergencyRepair $emergencyRepair)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify vehicle belongs to carrier
        $this->verifyRepairAccess($emergencyRepair, $carrier);
        
        $emergencyRepair->load(['vehicle', 'vehicle.carrier']);
        $vehicles = Vehicle::where('carrier_id', $carrier->id)
                          ->orderBy('company_unit_number')
                          ->get();
        
        return view('carrier.vehicles.emergency-repairs.edit', compact('emergencyRepair', 'vehicles'));
    }

    /**
     * Update the specified emergency repair.
     */
    public function update(Request $request, EmergencyRepair $emergencyRepair)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify vehicle belongs to carrier
        $this->verifyRepairAccess($emergencyRepair, $carrier);
        
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'repair_name' => 'required|string|max:255',
            'repair_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:pending,in_progress,completed',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Verify new vehicle also belongs to carrier
            $vehicle = Vehicle::where('id', $request->vehicle_id)
                ->where('carrier_id', $carrier->id)
                ->firstOrFail();
            
            $emergencyRepair->update([
                'vehicle_id' => $request->vehicle_id,
                'repair_name' => $request->repair_name,
                'repair_date' => $request->repair_date,
                'cost' => $request->cost,
                'status' => $request->status,
                'description' => $request->description,
                'notes' => $request->notes,
            ]);

            // Process repair files if they exist
            if ($request->hasFile('repair_files')) {
                Log::info('Emergency repair files found in update: ' . count($request->file('repair_files')));

                foreach ($request->file('repair_files') as $file) {
                    Log::info('Processing file in update: ' . $file->getClientOriginalName() . ' - ' . $file->getMimeType());

                    try {
                        $media = $emergencyRepair->addMedia($file)
                            ->toMediaCollection('emergency_repair_files');

                        Log::info('File updated successfully: ' . $media->id);
                    } catch (\Exception $e) {
                        Log::error('Error saving file in update: ' . $e->getMessage());
                    }
                }
            }

            return redirect()->route('carrier.emergency-repairs.index')
                ->with('success', 'Emergency repair updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating emergency repair for carrier', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'repair_id' => $emergencyRepair->id
            ]);

            return redirect()->back()
                ->with('error', 'Error updating emergency repair: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified emergency repair.
     */
    public function destroy(EmergencyRepair $emergencyRepair)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify vehicle belongs to carrier
        $this->verifyRepairAccess($emergencyRepair, $carrier);
        
        try {
            // Delete all associated files
            $emergencyRepair->clearMediaCollection('emergency_repair_files');
            
            $emergencyRepair->delete();

            return redirect()->route('carrier.vehicles.emergency-repairs.index')
                ->with('success', 'Emergency repair deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting emergency repair for carrier', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'repair_id' => $emergencyRepair->id
            ]);

            return redirect()->back()
                ->with('error', 'Error deleting emergency repair: ' . $e->getMessage());
        }
    }

    /**
     * Delete a specific file from an emergency repair.
     */
    public function deleteFile(EmergencyRepair $emergencyRepair, $mediaId)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify vehicle belongs to carrier
        $this->verifyRepairAccess($emergencyRepair, $carrier);
        
        Log::info('deleteFile called', [
            'carrier_id' => $carrier->id,
            'repair_id' => $emergencyRepair->id,
            'media_id' => $mediaId
        ]);

        try {
            // First check if the media exists at all
            $media = Media::find($mediaId);
            
            if (!$media) {
                Log::warning('Media does not exist in database', [
                    'repair_id' => $emergencyRepair->id,
                    'media_id' => $mediaId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo ya no existe o ha sido eliminado previamente.'
                ], 404);
            }

            // Verify that the file belongs to the emergency repair
            $mediaFromRepair = $emergencyRepair->media()->where('id', $mediaId)->first();

            if (!$mediaFromRepair) {
                Log::warning('Media not associated with this repair', [
                    'repair_id' => $emergencyRepair->id,
                    'media_id' => $mediaId,
                    'media_model_type' => $media->model_type,
                    'media_model_id' => $media->model_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Este archivo no pertenece a esta reparación de emergencia.'
                ], 403);
            }

            Log::info('Media found, deleting', [
                'media_id' => $media->id,
                'media_model_id' => $media->model_id,
                'media_model_type' => $media->model_type,
                'file_name' => $media->file_name
            ]);

            // Delete the media using the model method to ensure proper cleanup
            $media->delete();

            Log::info('File deleted successfully');

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in deleteFile', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vehicle details including driver information (AJAX endpoint).
     */
    public function getVehicleDetails($vehicleId)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        try {
            // Verify vehicle belongs to carrier
            $vehicle = Vehicle::where('id', $vehicleId)
                ->where('carrier_id', $carrier->id)
                ->with(['currentDriverAssignment', 'currentDriverAssignment.driver', 'currentDriverAssignment.driver.user'])
                ->firstOrFail();
            
            $driverInfo = null;
            if ($vehicle->currentDriverAssignment && $vehicle->currentDriverAssignment->driver) {
                $driver = $vehicle->currentDriverAssignment->driver;
                $driverInfo = [
                    'name' => trim(($driver->user->name ?? '') . ' ' . $driver->last_name),
                    'email' => $driver->user->email ?? 'N/A',
                    'phone' => $driver->phone ?? 'N/A',
                ];
            }
            
            return response()->json([
                'success' => true,
                'driver' => $driverInfo
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching vehicle details', [
                'vehicle_id' => $vehicleId,
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found or access denied'
            ], 404);
        }
    }

    /**
     * Verify that the emergency repair's vehicle belongs to the carrier.
     *
     * @param EmergencyRepair $emergencyRepair
     * @param \App\Models\Carrier $carrier
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function verifyRepairAccess(EmergencyRepair $emergencyRepair, $carrier): void
    {
        if ($emergencyRepair->vehicle->carrier_id !== $carrier->id) {
            abort(403, 'You do not have access to this emergency repair.');
        }
    }

    /**
     * Generate a repair report PDF for a vehicle's emergency repairs and save as VehicleDocument.
     */
    public function generateRepairReport(Vehicle $vehicle)
    {
        $carrier = Auth::user()->carrierDetails->carrier;

        if ($vehicle->carrier_id !== $carrier->id) {
            abort(403, 'Unauthorized');
        }

        try {
            $vehicle->load('carrier');

            $repairs = EmergencyRepair::where('vehicle_id', $vehicle->id)
                ->orderBy('repair_date', 'asc')
                ->get();

            $pdf = Pdf::loadView('admin.vehicles.emergency-repairs.report-pdf', [
                'vehicle' => $vehicle,
                'repairs' => $repairs,
            ])->setPaper('letter', 'portrait');

            $fileName = 'repair_report_' . $vehicle->id . '_' . now()->format('Ymd_His') . '.pdf';
            $tempPath = storage_path('app/temp/' . $fileName);

            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            $document = VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type' => VehicleDocument::DOC_TYPE_REPAIR_RECORD,
                'document_number' => 'RR-' . $vehicle->id . '-' . now()->format('Ymd'),
                'issued_date' => now(),
                'status' => VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Emergency Repair Report for ' . $vehicle->make . ' ' . $vehicle->model . ' (' . $vehicle->year . '). Generated on ' . now()->format('m/d/Y h:i A'),
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');

            return redirect()->back()
                ->with('success', 'Emergency repair report generated successfully.');

        } catch (\Exception $e) {
            Log::error('Error generating repair report (carrier)', [
                'vehicle_id' => $vehicle->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error generating repair report: ' . $e->getMessage());
        }
    }

    /**
     * Delete a generated repair report (VehicleDocument).
     */
    public function deleteRepairReport(Vehicle $vehicle, VehicleDocument $report)
    {
        $carrier = Auth::user()->carrierDetails->carrier;

        if ($vehicle->carrier_id !== $carrier->id) {
            abort(403, 'Unauthorized');
        }

        try {
            if ($report->vehicle_id !== $vehicle->id) {
                return redirect()->back()->with('error', 'Report does not belong to this vehicle.');
            }

            $report->clearMediaCollection('document_files');
            $report->delete();

            Log::info('Repair report deleted (carrier)', [
                'vehicle_id' => $vehicle->id,
                'report_id' => $report->id,
            ]);

            return redirect()->back()->with('success', 'Repair report deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting repair report (carrier)', [
                'vehicle_id' => $vehicle->id,
                'report_id' => $report->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Error deleting repair report: ' . $e->getMessage());
        }
    }
}
