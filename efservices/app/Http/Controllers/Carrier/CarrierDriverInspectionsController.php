<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverInspection;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CarrierDriverInspectionsController extends Controller
{
    /**
     * Mostrar la lista de inspecciones de los conductores del carrier.
     */
    public function index(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $query = DriverInspection::query()
            ->with(['userDriverDetail.user', 'vehicle'])
            ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                $q->where('carrier_id', $carrier->id);
            });

        // Aplicar filtros
        if ($request->filled('search_term')) {
            $query->where('inspection_type', 'like', '%' . $request->search_term . '%')
                ->orWhere('notes', 'like', '%' . $request->search_term . '%')
                ->orWhere('inspector_name', 'like', '%' . $request->search_term . '%');
        }

        if ($request->filled('driver_filter')) {
            $query->where('user_driver_detail_id', $request->driver_filter);
        }

        if ($request->filled('vehicle_filter')) {
            $query->where('vehicle_id', $request->vehicle_filter);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('inspection_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('inspection_date', '<=', $request->date_to);
        }

        if ($request->filled('inspection_type')) {
            $query->where('inspection_type', $request->inspection_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ordenar resultados
        $sortField = $request->get('sort_field', 'inspection_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $inspections = $query->paginate(10);
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();
        $vehicles = Vehicle::where('carrier_id', $carrier->id)->get();

        // Obtener valores únicos para los filtros de desplegable
        $inspectionTypes = DriverInspection::whereHas('userDriverDetail', function ($q) use ($carrier) {
                $q->where('carrier_id', $carrier->id);
            })
            ->distinct()
            ->pluck('inspection_type')
            ->filter()
            ->toArray();
            
        $statuses = DriverInspection::whereHas('userDriverDetail', function ($q) use ($carrier) {
                $q->where('carrier_id', $carrier->id);
            })
            ->distinct()
            ->pluck('status')
            ->filter()
            ->toArray();

        return view('carrier.drivers.inspections.index', compact(
            'inspections',
            'drivers',
            'vehicles',
            'carrier',
            'inspectionTypes',
            'statuses'
        ));
    }

    /**
     * Mostrar el historial de inspecciones de un conductor específico.
     */
    public function driverHistory(UserDriverDetail $driver, Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        $query = DriverInspection::where('user_driver_detail_id', $driver->id);

        // Aplicar filtros si existen
        if ($request->filled('search_term')) {
            $query->where('inspection_type', 'like', '%' . $request->search_term . '%')
                ->orWhere('notes', 'like', '%' . $request->search_term . '%')
                ->orWhere('inspector_name', 'like', '%' . $request->search_term . '%');
        }

        if ($request->filled('vehicle_filter')) {
            $query->where('vehicle_id', $request->vehicle_filter);
        }

        if ($request->filled('inspection_type')) {
            $query->where('inspection_type', $request->inspection_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ordenar resultados
        $sortField = $request->get('sort_field', 'inspection_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $inspections = $query->paginate(10);

        // Obtener vehículos del conductor para el filtro
        $driverVehicles = Vehicle::where(function ($query) use ($driver) {
            $query->where('user_driver_detail_id', $driver->id)
                ->orWhereHas('driverInspections', function ($q) use ($driver) {
                    $q->where('user_driver_detail_id', $driver->id);
                });
        })->get();

        // Obtener valores únicos para los filtros de desplegable
        $inspectionTypes = DriverInspection::where('user_driver_detail_id', $driver->id)
            ->distinct()
            ->pluck('inspection_type')
            ->filter()
            ->toArray();
            
        $statuses = DriverInspection::where('user_driver_detail_id', $driver->id)
            ->distinct()
            ->pluck('status')
            ->filter()
            ->toArray();

        return view('carrier.drivers.inspections.driver_history', compact(
            'driver',
            'inspections',
            'driverVehicles',
            'carrier',
            'inspectionTypes',
            'statuses'
        ));
    }

    /**
     * Mostrar el formulario para crear una nueva inspección.
     */
    public function create()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();
        $vehicles = Vehicle::where('carrier_id', $carrier->id)->get();
            
        return view('carrier.drivers.inspections.create', compact('drivers', 'vehicles', 'carrier'));
    }

    /**
     * Almacenar una nueva inspección.
     */
    public function store(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'inspection_date' => 'required|date',
            'inspection_type' => 'required|string|max:255',
            'inspection_level' => 'nullable|string|max:50',
            'inspector_name' => 'required|string|max:255',
            'inspector_number' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'defects_found' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'is_defects_corrected' => 'boolean',
            'defects_corrected_date' => 'nullable|date',
            'corrected_by' => 'nullable|string|max:255',
            'is_vehicle_safe_to_operate' => 'boolean',
            'notes' => 'nullable|string',
            'inspection_files' => 'nullable|string', // Campo JSON para los archivos de Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($validated['vehicle_id']) {
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            if ((int) $vehicle->carrier_id !== (int) $carrier->id) {
                return redirect()->route('carrier.drivers.inspections.index')
                    ->with('error', 'No tienes acceso a este vehículo.');
            }
        }

        // Convertir checkboxes a valores booleanos
        $validated['is_defects_corrected'] = isset($request->is_defects_corrected);
        $validated['is_vehicle_safe_to_operate'] = isset($request->is_vehicle_safe_to_operate);

        // Si hay defectos corregidos, pero no hay fecha, usar la fecha actual
        if ($validated['is_defects_corrected'] && empty($validated['defects_corrected_date'])) {
            $validated['defects_corrected_date'] = now();
        }

        // Si no hay defectos corregidos, eliminar fecha y responsable
        if (!$validated['is_defects_corrected']) {
            $validated['defects_corrected_date'] = null;
            $validated['corrected_by'] = null;
        }

        try {
            $inspection = DriverInspection::create($validated);
            
            // Procesar documentos si hay datos
            if ($request->filled('inspection_files')) {
                $this->processLivewireFiles($inspection, $request->inspection_files, 'inspection_documents');
            }
            
            Session::flash('success', 'Registro de inspección añadido exitosamente.');
            
            // Redirigir a la página apropiada
            if ($request->has('redirect_to_driver')) {
                return redirect()->route('carrier.drivers.inspections.driver_history', $validated['user_driver_detail_id']);
            }
            
            return redirect()->route('carrier.drivers.inspections.index');
            
        } catch (\Exception $e) {
            Log::error('Error al crear registro de inspección', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al crear registro de inspección: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar el formulario para editar una inspección.
     */
    public function edit(DriverInspection $inspection)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $inspection->userDriverDetail->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'No tienes acceso a este registro de inspección.');
        }
        
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();
        $vehicles = Vehicle::where('carrier_id', $carrier->id)->get();
        
        // Obtener los documentos asociados a esta inspección usando MediaLibrary
        $documents = $inspection->getMedia('inspection_documents')->map(function($media) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'collection' => $media->collection_name,
                'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                'isExisting' => true
            ];
        })->toArray();
            
        return view('carrier.drivers.inspections.edit', compact('inspection', 'drivers', 'vehicles', 'carrier', 'documents'));
    }

    /**
     * Actualizar una inspección.
     */
    public function update(Request $request, DriverInspection $inspection)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $inspection->userDriverDetail->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'No tienes acceso a este registro de inspección.');
        }
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'inspection_date' => 'required|date',
            'inspection_type' => 'required|string|max:255',
            'inspection_level' => 'nullable|string|max:50',
            'inspector_name' => 'required|string|max:255',
            'inspector_number' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'defects_found' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'is_defects_corrected' => 'boolean',
            'defects_corrected_date' => 'nullable|date',
            'corrected_by' => 'nullable|string|max:255',
            'is_vehicle_safe_to_operate' => 'boolean',
            'notes' => 'nullable|string',
            'inspection_files' => 'nullable|string', // Campo JSON para los archivos de Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        // Verificar que el vehículo pertenezca al carrier del usuario autenticado
        if ($validated['vehicle_id']) {
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            if ((int) $vehicle->carrier_id !== (int) $carrier->id) {
                return redirect()->route('carrier.drivers.inspections.index')
                    ->with('error', 'No tienes acceso a este vehículo.');
            }
        }

        // Convertir checkboxes a valores booleanos
        $validated['is_defects_corrected'] = isset($request->is_defects_corrected);
        $validated['is_vehicle_safe_to_operate'] = isset($request->is_vehicle_safe_to_operate);

        // Si hay defectos corregidos, pero no hay fecha, usar la fecha actual
        if ($validated['is_defects_corrected'] && empty($validated['defects_corrected_date'])) {
            $validated['defects_corrected_date'] = now();
        }

        // Si no hay defectos corregidos, eliminar fecha y responsable
        if (!$validated['is_defects_corrected']) {
            $validated['defects_corrected_date'] = null;
            $validated['corrected_by'] = null;
        }

        try {
            $inspection->update($validated);
            
            // Procesar documentos si hay datos nuevos
            if ($request->filled('inspection_files')) {
                $this->processLivewireFiles($inspection, $request->inspection_files, 'inspection_documents');
            }
            
            Session::flash('success', 'Registro de inspección actualizado exitosamente.');
            
            // Redirigir a la página apropiada
            if ($request->has('redirect_to_driver')) {
                return redirect()->route('carrier.drivers.inspections.driver_history', $inspection->user_driver_detail_id);
            }
            
            return redirect()->route('carrier.drivers.inspections.index');
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar registro de inspección', [
                'error' => $e->getMessage(),
                'inspection_id' => $inspection->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al actualizar registro de inspección: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar una inspección.
     */
    public function destroy(DriverInspection $inspection)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $inspection->userDriverDetail->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'No tienes acceso a este registro de inspección.');
        }
        
        try {
            $driverId = $inspection->user_driver_detail_id;
            
            // Eliminar archivos adjuntos
            $inspection->clearMediaCollection('inspection_reports');
            $inspection->clearMediaCollection('defect_photos');
            $inspection->clearMediaCollection('repair_documents');
            
            $inspection->delete();
            
            Session::flash('success', 'Registro de inspección eliminado exitosamente.');
            
            // Determinar la ruta de retorno basado en la URL de referencia
            $referer = request()->headers->get('referer');
            if (strpos($referer, 'driver_history') !== false) {
                return redirect()->route('carrier.drivers.inspections.driver_history', $driverId);
            }
            
            return redirect()->route('carrier.drivers.inspections.index');
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar registro de inspección', [
                'error' => $e->getMessage(),
                'inspection_id' => $inspection->id
            ]);
            
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'Error al eliminar registro de inspección: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar un archivo específico.
     */
    public function deleteFile($inspectionId, $mediaId)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        $inspection = DriverInspection::findOrFail($inspectionId);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $inspection->userDriverDetail->carrier_id !== (int) $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este archivo.'], 403);
        }
        
        try {
            $media = $inspection->media()->findOrFail($mediaId);
            $media->delete();
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar archivo de inspección', [
                'error' => $e->getMessage(),
                'inspection_id' => $inspectionId,
                'media_id' => $mediaId
            ]);
            
            return response()->json(['error' => 'Error al eliminar archivo: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Elimina un documento mediante una solicitud AJAX
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxDestroyDocument(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        try {
            $mediaId = $request->input('document_id');
            if (!$mediaId) {
                return response()->json(['error' => 'Media ID is required'], 400);
            }
            
            // 1. Buscar el media en la tabla media
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($mediaId);
            
            // 2. Verificar que pertenece a una inspección (tipo de modelo correcto)
            if ($media->model_type !== DriverInspection::class) {
                return response()->json(['error' => 'El documento no pertenece a una inspección'], 403);
            }
            
            $inspectionId = $media->model_id;
            $inspection = DriverInspection::find($inspectionId);
            
            if (!$inspection) {
                return response()->json(['error' => 'No se encontró la inspección asociada al documento'], 404);
            }
            
            // 3. Verificar que el conductor pertenezca al carrier del usuario autenticado
            if ((int) $inspection->userDriverDetail->carrier_id !== (int) $carrier->id) {
                return response()->json(['error' => 'No tienes acceso a este documento'], 403);
            }
            
            // 4. Eliminar el media usando el método safeDeleteMedia del modelo
            $result = $inspection->safeDeleteMedia($mediaId);
            
            if (!$result) {
                return response()->json(['error' => 'No se pudo eliminar el documento'], 500);
            }
            
            return response()->json([
                'success' => true, 
                'message' => 'Documento eliminado correctamente'
            ]);
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento mediante AJAX', [
                'media_id' => $request->input('document_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al eliminar documento: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener los archivos de una inspección.
     */
    public function getFiles(DriverInspection $inspection)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $inspection->userDriverDetail->carrier_id !== (int) $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a estos archivos.'], 403);
        }
        
        // Cargar la relación media si no está ya cargada
        if (!$inspection->relationLoaded('media')) {
            $inspection->load('media');
        }
        
        return response()->json([
            'media' => $inspection->media
        ]);
    }
    
    /**
     * Obtener vehículos por conductor.
     */
    public function getVehiclesByDriver(UserDriverDetail $driver)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return response()->json(['error' => 'No tienes acceso a este conductor.'], 403);
        }
        
        $vehicles = Vehicle::where(function ($query) use ($driver, $carrier) {
            $query->where('user_driver_detail_id', $driver->id)
                ->orWhere(function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id)
                      ->whereNull('user_driver_detail_id');
                });
        })->get();
        
        return response()->json($vehicles);
    }
    
    /**
     * Vista para todos los documentos de inspecciones del carrier
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function allDocuments(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Obtener IDs de inspecciones del carrier
        $inspectionIds = DriverInspection::whereHas('userDriverDetail', function($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->pluck('id')->toArray();
        
        // Construir la consulta base para los documentos
        $query = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', 'inspection_documents')
            ->where('model_type', DriverInspection::class)
            ->whereIn('model_id', $inspectionIds);
            
        // Aplicar filtros
        if ($request->filled('driver_filter') && $request->driver_filter != '') {
            $query->whereJsonContains('custom_properties->driver_id', (int)$request->driver_filter);
        }
        
        if ($request->filled('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('search_term') && $request->search_term != '') {
            $searchTerm = '%' . $request->search_term . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('file_name', 'like', $searchTerm);
            });
        }
        
        // Ordenar resultados
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Paginar resultados
        $documents = $query->paginate(20);
        
        // Cargar datos relacionados para los filtros
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)->with('user')->get();
        
        return view('carrier.drivers.inspections.all-documents', compact(
            'documents',
            'drivers',
            'carrier'
        ));
    }
    
    /**
     * Vista para los documentos de inspecciones de un conductor específico
     * 
     * @param UserDriverDetail $driver
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function driverDocuments(UserDriverDetail $driver, Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.inspections.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        // Obtener las licencias del conductor desde las colecciones correctas
        $licenseDocuments = [];
        
        // Buscar en las licencias del conductor
        if ($driver->licenses) {
            foreach ($driver->licenses as $license) {
                // Obtener archivos de license_front
                $frontFiles = $license->getMedia('license_front');
                foreach ($frontFiles as $file) {
                    $licenseDocuments[] = $file;
                }
                
                // Obtener archivos de license_back
                $backFiles = $license->getMedia('license_back');
                foreach ($backFiles as $file) {
                    $licenseDocuments[] = $file;
                }
                
                // Obtener archivos de license_documents
                $docFiles = $license->getMedia('license_documents');
                foreach ($docFiles as $file) {
                    $licenseDocuments[] = $file;
                }
            }
        }
        
        // Tomar el primer documento de licencia para mostrar como principal
        $license = !empty($licenseDocuments) ? $licenseDocuments[0] : null;
            
        // Obtener IDs de todas las inspecciones del conductor
        $inspectionIds = DriverInspection::where('user_driver_detail_id', $driver->id)
            ->pluck('id')
            ->toArray();
        
        // Consultar documentos basados en los IDs de inspecciones
        $query = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', 'inspection_documents')
            ->where('model_type', DriverInspection::class)
            ->whereIn('model_id', $inspectionIds);
            
        if ($request->filled('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('search_term') && $request->search_term != '') {
            $searchTerm = '%' . $request->search_term . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('file_name', 'like', $searchTerm);
            });
        }
        
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $documents = $query->paginate(20);
        
        return view('carrier.drivers.inspections.driver-documents', compact(
            'documents',
            'driver',
            'license',
            'licenseDocuments',
            'carrier'
        ));
    }
    
    /**
     * Procesar archivos subidos por Livewire
     * 
     * @param DriverInspection $inspection
     * @param string $filesJson
     * @param string $collection
     * @return int Número de archivos procesados correctamente
     */
    private function processLivewireFiles(DriverInspection $inspection, $filesJson, $collection)
    {
        $uploadedCount = 0;
        
        try {
            // Si no hay datos de archivos, salir
            if (empty($filesJson)) {
                return 0;
            }
            
            $filesArray = json_decode($filesJson, true);
            
            if (is_array($filesArray)) {
                foreach ($filesArray as $file) {
                    if (empty($file['path'])) {
                        continue;
                    }
                    
                    // Obtener la ruta completa del archivo
                    $filePath = $file['path'];
                    $fullPath = storage_path('app/' . $filePath);
                    
                    // Verificar si el archivo existe físicamente
                    if (!file_exists($fullPath)) {
                        Log::error('Archivo no encontrado', [
                            'path' => $filePath,
                            'full_path' => $fullPath,
                            'inspection_id' => $inspection->id
                        ]);
                        continue;
                    }
                    
                    // Usar MediaLibrary para agregar el archivo
                    $media = $inspection->addMedia($fullPath)
                        ->usingName($file['original_name'] ?? basename($fullPath))
                        ->withCustomProperties([
                            'inspection_id' => $inspection->id,
                            'driver_id' => $inspection->user_driver_detail_id,
                            'document_type' => 'inspection_document',
                            'original_name' => $file['original_name'] ?? basename($fullPath),
                            'mime_type' => $file['mime_type'] ?? mime_content_type($fullPath),
                            'size' => $file['size'] ?? filesize($fullPath)
                        ])
                        ->toMediaCollection($collection ?: 'inspection_documents');
                    
                    $uploadedCount++;
                    
                    Log::info('Documento de inspección subido correctamente', [
                        'inspection_id' => $inspection->id,
                        'media_id' => $media->id,
                        'file_name' => $media->file_name,
                        'collection' => $media->collection_name
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al procesar documentos de inspección', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'inspection_id' => $inspection->id,
                'collection' => $collection
            ]);
        }
        
        return $uploadedCount;
    }
}
