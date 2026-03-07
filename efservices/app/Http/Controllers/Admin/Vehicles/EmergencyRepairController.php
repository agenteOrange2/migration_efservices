<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\EmergencyRepair;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Admin\Vehicle\VehicleDocument;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmergencyRepairController extends Controller
{
    /**
     * Display a listing of emergency repairs.
     */
    public function index(Request $request)
    {
        $query = EmergencyRepair::with(['vehicle', 'vehicle.carrier', 'vehicle.currentDriverAssignment.driver.user']);

        // Filter by carrier
        if ($request->filled('carrier_id')) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('carrier_id', $request->carrier_id);
            });
        }

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
        
        // Get carriers and drivers for filters
        $carriers = Carrier::orderBy('name')->get();
        $drivers = collect();
        
        if ($request->filled('carrier_id')) {
            $drivers = UserDriverDetail::whereHas('currentVehicleAssignment.vehicle', function ($q) use ($request) {
                $q->where('carrier_id', $request->carrier_id);
            })->with('user')->get();
        }
        
        // Get statistics for filter cards
        $totalCount = EmergencyRepair::count();
        $pendingCount = EmergencyRepair::where('status', 'pending')->count();
        $inProgressCount = EmergencyRepair::where('status', 'in_progress')->count();
        $completedCount = EmergencyRepair::where('status', 'completed')->count();
        
        // Calculate total costs
        $totalCost = EmergencyRepair::sum('cost');
        $pendingCost = EmergencyRepair::where('status', 'pending')->sum('cost');
        $inProgressCost = EmergencyRepair::where('status', 'in_progress')->sum('cost');
        $completedCost = EmergencyRepair::where('status', 'completed')->sum('cost');

        return view('admin.vehicles.emergency-repairs.index', compact(
            'emergencyRepairs', 
            'carriers', 
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
        $vehicles = collect();
        $carriers = Carrier::orderBy('name')->get();
        
        // If vehicle_id is provided, get that specific vehicle
        if ($request->filled('vehicle_id')) {
            $vehicle = Vehicle::findOrFail($request->vehicle_id);
            $vehicles = collect([$vehicle]);
        }
        
        return view('admin.vehicles.emergency-repairs.create', compact('vehicles', 'carriers'));
    }

    /**
     * Store a newly created emergency repair.
     */
    public function store(Request $request)
    {
        // Parse repair_date from m/d/Y format to Y-m-d for validation and storage
        try {
            $repairDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->repair_date)->format('Y-m-d');
            $request->merge(['repair_date' => $repairDate]);
        } catch (\Exception $e) {
            // Fallback: keep as-is if already in Y-m-d or other parseable format
        }

        Log::info('Creating emergency repair', [
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
                'errors' => $validator->errors()->toArray()
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
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

            Log::info('Emergency repair saved', [
                'repair_id' => $emergencyRepair->id,
                'save_result' => $result,
                'data_saved' => $emergencyRepair->toArray()
            ]);

            // Process repair files if they exist
            if ($request->hasFile('repair_files')) {
                Log::info('Emergency repair files found', [
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

            return redirect()->route('admin.vehicles.emergency-repairs.index')
                ->with('success', 'Emergency repair created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving emergency repair', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error saving emergency repair: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Resolve EmergencyRepair from route parameters.
     * Handles both standalone route (1 param) and vehicle-nested route (2 params).
     */
    private function resolveEmergencyRepair($firstParam, $secondParam = null): EmergencyRepair
    {
        if ($firstParam instanceof EmergencyRepair) {
            return $firstParam;
        }

        // If secondParam exists, first is vehicle ID and second is repair ID
        $repairId = $secondParam ?? $firstParam;

        return EmergencyRepair::findOrFail($repairId);
    }

    /**
     * Display the specified emergency repair.
     */
    public function show($firstParam, $secondParam = null)
    {
        $emergencyRepair = $this->resolveEmergencyRepair($firstParam, $secondParam);
        $emergencyRepair->load(['vehicle', 'vehicle.carrier', 'vehicle.driver']);
        
        return view('admin.vehicles.emergency-repairs.show', compact('emergencyRepair'));
    }

    /**
     * Show the form for editing the specified emergency repair.
     */
    public function edit($firstParam, $secondParam = null)
    {
        $emergencyRepair = $this->resolveEmergencyRepair($firstParam, $secondParam);
        $emergencyRepair->load(['vehicle', 'vehicle.carrier']);
        $carriers = Carrier::orderBy('name')->get();
        $vehicles = Vehicle::where('carrier_id', $emergencyRepair->vehicle->carrier_id)
                          ->orderBy('company_unit_number')
                          ->get();
        
        return view('admin.vehicles.emergency-repairs.edit', compact('emergencyRepair', 'carriers', 'vehicles'));
    }

    /**
     * Update the specified emergency repair.
     */
    public function update(Request $request, $firstParam, $secondParam = null)
    {
        $emergencyRepair = $this->resolveEmergencyRepair($firstParam, $secondParam);
        // Parse repair_date from m/d/Y format to Y-m-d for validation and storage
        try {
            $repairDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->repair_date)->format('Y-m-d');
            $request->merge(['repair_date' => $repairDate]);
        } catch (\Exception $e) {
            // Fallback: keep as-is if already in Y-m-d or other parseable format
        }

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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $emergencyRepair->update([
                'vehicle_id' => $request->vehicle_id,
                'repair_name' => $request->repair_name,
                'repair_date' => $request->repair_date,
                'cost' => $request->cost,
                'odometer' => $request->odometer,
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

            return redirect()->route('admin.vehicles.emergency-repairs.index')
                ->with('success', 'Emergency repair updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating emergency repair', [
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
    public function destroy($firstParam, $secondParam = null)
    {
        $emergencyRepair = $this->resolveEmergencyRepair($firstParam, $secondParam);
        try {
            // Delete all associated files
            $emergencyRepair->clearMediaCollection('emergency_repair_files');
            
            $emergencyRepair->delete();

            return redirect()->route('admin.vehicles.emergency-repairs.index')
                ->with('success', 'Emergency repair deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting emergency repair', [
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
    public function deleteFile($firstParam, $secondParam, $thirdParam = null)
    {
        if ($thirdParam !== null) {
            // Vehicle-nested route: {vehicle}, {id}, {mediaId}
            $emergencyRepair = EmergencyRepair::findOrFail($secondParam);
            $mediaId = $thirdParam;
        } else {
            // Standalone route: {emergencyRepair}, {mediaId}
            $emergencyRepair = $firstParam instanceof EmergencyRepair ? $firstParam : EmergencyRepair::findOrFail($firstParam);
            $mediaId = $secondParam;
        }
        Log::info('deleteFile called', [
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
     * Upload a document to an emergency repair.
     */
    public function uploadDocument(Request $request, EmergencyRepair $emergencyRepair)
    {
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
                    'uploaded_by_admin' => true,
                    'admin_id' => Auth::id(),
                    'admin_name' => Auth::user()->name,
                    'uploaded_at' => now()->format('Y-m-d H:i:s'),
                    'description' => $request->input('document_description', '')
                ])
                ->toMediaCollection('emergency_repair_files');
            
            Log::info('Document uploaded to emergency repair', [
                'repair_id' => $emergencyRepair->id,
                'media_id' => $media->id,
                'file_name' => $media->file_name,
                'admin_id' => Auth::id()
            ]);
            
            DB::commit();
            
            return redirect()->back()
                ->with('success', 'Document uploaded successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error uploading document to emergency repair', [
                'repair_id' => $emergencyRepair->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading document: ' . $e->getMessage());
        }
    }

    /**
     * Get vehicles by carrier (AJAX endpoint).
     */
    public function getVehiclesByCarrier($carrierId)
    {
        if (!$carrierId) {
            return response()->json([]);
        }
        
        $vehicles = Vehicle::where('carrier_id', $carrierId)
                          ->orderBy('company_unit_number')
                          ->get(['id', 'company_unit_number', 'make', 'model', 'vin']);
        
        return response()->json($vehicles);
    }

    /**
     * Get drivers by carrier (AJAX endpoint).
     */
    public function getDriversByCarrier(Request $request)
    {
        $carrierId = $request->get('carrier_id');
        
        if (!$carrierId) {
            return response()->json([]);
        }
        
        $drivers = UserDriverDetail::whereHas('currentVehicleAssignment.vehicle', function ($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->with('user')->get(['id', 'last_name', 'user_id']);
        
        return response()->json($drivers);
    }

    /**
     * Show the form for creating a new emergency repair for a specific vehicle.
     */
    public function createForVehicle(Vehicle $vehicle)
    {
        $vehicle->load('carrier');
        
        return view('admin.vehicles.emergency-repairs.create-for-vehicle', compact('vehicle'));
    }

    /**
     * Store a newly created emergency repair for a specific vehicle.
     */
    public function storeForVehicle(Request $request, Vehicle $vehicle)
    {
        // Parse repair_date from m/d/Y format to Y-m-d for validation and storage
        try {
            $repairDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->repair_date)->format('Y-m-d');
            $request->merge(['repair_date' => $repairDate]);
        } catch (\Exception $e) {
            // Fallback: keep as-is if already in Y-m-d or other parseable format
        }

        Log::info('Creating emergency repair for vehicle', [
            'vehicle_id' => $vehicle->id,
            'request_data' => $request->except(['_token']),
            'request_has_files' => $request->hasFile('repair_files')
        ]);

        $validator = Validator::make($request->all(), [
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
                'errors' => $validator->errors()->toArray()
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $emergencyRepair = new EmergencyRepair([
                'vehicle_id' => $vehicle->id,
                'repair_name' => $request->repair_name,
                'repair_date' => $request->repair_date,
                'cost' => $request->cost,
                'odometer' => $request->odometer,
                'status' => $request->status,
                'description' => $request->description,
                'notes' => $request->notes,
            ]);

            $result = $emergencyRepair->save();

            Log::info('Emergency repair saved for vehicle', [
                'repair_id' => $emergencyRepair->id,
                'vehicle_id' => $vehicle->id,
                'save_result' => $result,
                'data_saved' => $emergencyRepair->toArray()
            ]);

            // Process repair files if they exist
            if ($request->hasFile('repair_files')) {
                Log::info('Emergency repair files found', [
                    'file_count' => count($request->file('repair_files'))
                ]);

                foreach ($request->file('repair_files') as $file) {
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

            return redirect()->route('admin.vehicles.maintenances.index', $vehicle->id)
                ->with('success', 'Emergency repair created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving emergency repair for vehicle', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vehicle_id' => $vehicle->id
            ]);

            return redirect()->back()
                ->with('error', 'Error saving emergency repair: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display emergency repairs for a specific vehicle.
     */
    public function indexForVehicle(Vehicle $vehicle)
    {
        $emergencyRepairs = EmergencyRepair::where('vehicle_id', $vehicle->id)
            ->orderBy('repair_date', 'desc')
            ->paginate(15);
        
        return view('admin.vehicles.emergency-repairs.index-for-vehicle', compact('vehicle', 'emergencyRepairs'));
    }

    /**
     * Generate a repair report PDF for a vehicle's emergency repairs and save as VehicleDocument.
     */
    public function generateRepairReport(Vehicle $vehicle)
    {
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
                ->with('success', 'Emergency repair report generated successfully and saved to vehicle documents.');

        } catch (\Exception $e) {
            Log::error('Error generating repair report', [
                'vehicle_id' => $vehicle->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error generating repair report: ' . $e->getMessage());
        }
    }

    /**
     * Generate a repair report PDF for a single emergency repair and save as VehicleDocument.
     */
    public function generateSingleRepairReport(EmergencyRepair $emergencyRepair)
    {
        try {
            $vehicle = $emergencyRepair->vehicle;
            $vehicle->load('carrier');

            // Only this single repair
            $repairs = collect([$emergencyRepair]);

            $pdf = Pdf::loadView('admin.vehicles.emergency-repairs.report-pdf', [
                'vehicle' => $vehicle,
                'repairs' => $repairs,
            ])->setPaper('letter', 'portrait');

            $fileName = 'repair_report_' . $emergencyRepair->id . '_' . now()->format('Ymd_His') . '.pdf';
            $tempPath = storage_path('app/temp/' . $fileName);

            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            $document = VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type' => VehicleDocument::DOC_TYPE_REPAIR_RECORD,
                'document_number' => 'RR-' . $emergencyRepair->id . '-' . now()->format('Ymd'),
                'issued_date' => now(),
                'status' => VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Emergency Repair Report: ' . $emergencyRepair->repair_name . ' for ' . $vehicle->make . ' ' . $vehicle->model . ' (' . $vehicle->year . '). Generated on ' . now()->format('m/d/Y h:i A'),
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');

            return redirect()->back()
                ->with('success', 'Individual repair report generated successfully and saved to vehicle documents.');

        } catch (\Exception $e) {
            Log::error('Error generating single repair report', [
                'repair_id' => $emergencyRepair->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        try {
            if ($report->vehicle_id !== $vehicle->id) {
                return redirect()->back()->with('error', 'Report does not belong to this vehicle.');
            }

            $report->clearMediaCollection('document_files');
            $report->delete();

            Log::info('Repair report deleted', [
                'vehicle_id' => $vehicle->id,
                'report_id' => $report->id,
            ]);

            return redirect()->back()->with('success', 'Repair report deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting repair report', [
                'vehicle_id' => $vehicle->id,
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Error deleting repair report: ' . $e->getMessage());
        }
    }
}