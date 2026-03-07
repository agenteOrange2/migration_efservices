<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\DocumentAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierDriverAccidentsController extends Controller
{
    /**
     * Validar que un accidente pertenezca al carrier del usuario autenticado.
     * 
     * @param DriverAccident $accident
     * @param int $carrierId
     * @return bool
     */
    private function validateAccidentOwnership(DriverAccident $accident, $carrierId)
    {
        return (int) $accident->userDriverDetail->carrier_id === (int) $carrierId;
    }

    /**
     * Validar que un conductor pertenezca al carrier del usuario autenticado.
     * 
     * @param UserDriverDetail $driver
     * @param int $carrierId
     * @return bool
     */
    private function validateDriverOwnership(UserDriverDetail $driver, $carrierId)
    {
        return (int) $driver->carrier_id === (int) $carrierId;
    }

    /**
     * Registrar intento de acceso no autorizado.
     * 
     * @param string $action
     * @param array $context
     * @return void
     */
    private function logUnauthorizedAccess($action, array $context = [])
    {
        Log::warning("Intento de acceso no autorizado: {$action}", array_merge([
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ], $context));
    }

    /**
     * Mostrar la lista de accidentes de los conductores del carrier.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Vista de índice de accidentes accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'driver_filter', 'date_from', 'date_to', 'sort_field', 'sort_direction']),
            ]);
            
            $query = DriverAccident::query()
                ->with(['userDriverDetail.user'])
                ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                });

            // Aplicar filtros
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('nature_of_accident', 'like', '%' . $searchTerm . '%')
                      ->orWhere('comments', 'like', '%' . $searchTerm . '%');
                });
            }

            if ($request->filled('driver_filter')) {
                $query->where('user_driver_detail_id', $request->driver_filter);
            }

            // Filtro de rango de fechas con formato MM/DD/YYYY
            if ($request->filled('date_from')) {
                try {
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_from)->startOfDay();
                    $query->whereDate('accident_date', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_from', [
                        'date_from' => $request->date_from,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    Session::flash('warning', 'El formato de fecha "Desde" es inválido. Use MM/DD/YYYY.');
                }
            }

            if ($request->filled('date_to')) {
                try {
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_to)->endOfDay();
                    $query->whereDate('accident_date', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_to', [
                        'date_to' => $request->date_to,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    Session::flash('warning', 'El formato de fecha "Hasta" es inválido. Use MM/DD/YYYY.');
                }
            }

            // Ordenar resultados
            $sortField = $request->get('sort_field', 'accident_date');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $accidents = $query->paginate(10);
            
            // Obtener lista de conductores para el filtro (con eager loading optimizado)
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->with('user:id,name,email')
                ->get();

            return view('carrier.drivers.accidents.index', compact('accidents', 'drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de índice de accidentes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al cargar la lista de accidentes. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar el historial de accidentes de un conductor específico.
     * 
     * @param UserDriverDetail $driver
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function driverHistory(UserDriverDetail $driver, Request $request)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el conductor pertenezca al carrier del usuario autenticado
            if (!$this->validateDriverOwnership($driver, $carrier->id)) {
                $this->logUnauthorizedAccess('acceso al historial de conductor', [
                    'driver_id' => $driver->id,
                    'carrier_id' => $carrier->id,
                    'driver_carrier_id' => $driver->carrier_id,
                ]);
                
                abort(403, 'No tienes permiso para acceder a este conductor.');
            }
            
            Log::info('Historial de accidentes de conductor accedido', [
                'driver_id' => $driver->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'date_from', 'date_to', 'had_injuries', 'had_fatalities']),
            ]);
            
            $query = DriverAccident::where('user_driver_detail_id', $driver->id);

            // Aplicar filtros si existen
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('nature_of_accident', 'like', '%' . $searchTerm . '%')
                      ->orWhere('comments', 'like', '%' . $searchTerm . '%');
                });
            }
            
            // Filtro de rango de fechas con formato MM/DD/YYYY
            if ($request->filled('date_from')) {
                try {
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_from)->startOfDay();
                    $query->whereDate('accident_date', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_from del historial', [
                        'date_from' => $request->date_from,
                        'driver_id' => $driver->id,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    Session::flash('warning', 'El formato de fecha "Desde" es inválido. Use MM/DD/YYYY.');
                }
            }

            if ($request->filled('date_to')) {
                try {
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_to)->endOfDay();
                    $query->whereDate('accident_date', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_to del historial', [
                        'date_to' => $request->date_to,
                        'driver_id' => $driver->id,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    Session::flash('warning', 'El formato de fecha "Hasta" es inválido. Use MM/DD/YYYY.');
                }
            }
            
            // Filtro por lesiones
            if ($request->filled('had_injuries')) {
                $query->where('had_injuries', $request->had_injuries === '1');
            }
            
            // Filtro por fatalidades
            if ($request->filled('had_fatalities')) {
                $query->where('had_fatalities', $request->had_fatalities === '1');
            }

            // Ordenar resultados
            $sortField = $request->get('sort_field', 'accident_date');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $accidents = $query->paginate(10);
            
            // Calcular estadísticas del conductor
            $totalAccidents = DriverAccident::where('user_driver_detail_id', $driver->id)->count();
            $totalInjuries = DriverAccident::where('user_driver_detail_id', $driver->id)
                ->where('had_injuries', true)
                ->sum('number_of_injuries');
            $totalFatalities = DriverAccident::where('user_driver_detail_id', $driver->id)
                ->where('had_fatalities', true)
                ->sum('number_of_fatalities');

            return view('carrier.drivers.accidents.driver_history', compact(
                'driver', 
                'accidents', 
                'carrier',
                'totalAccidents',
                'totalInjuries',
                'totalFatalities'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar historial de accidentes del conductor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'driver_id' => $driver->id ?? null,
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->route('carrier.drivers.accidents.index')
                ->with('error', 'Ocurrió un error al cargar el historial de accidentes. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar el formulario para crear un nuevo registro de accidente.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Load only carrier's drivers for dropdown
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrier->id)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            Log::info('Formulario de creación de accidente accedido', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'available_drivers_count' => $drivers->count(),
            ]);
                
            return view('carrier.drivers.accidents.create', compact('drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de accidente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.accidents.index')
                ->with('error', 'Ocurrió un error al cargar el formulario. Por favor, intente nuevamente.');
        }
    }

    /**
     * Almacenar un nuevo registro de accidente.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'accident_date' => 'required|date',
            'nature_of_accident' => 'required|string|max:255',
            'had_injuries' => 'boolean',
            'number_of_injuries' => 'nullable|integer|min:0',
            'had_fatalities' => 'boolean',
            'number_of_fatalities' => 'nullable|integer|min:0',
            'comments' => 'nullable|string',
            'accident_files' => 'nullable|array',
            'accident_files.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'accident_files_json' => 'nullable|string', // Para archivos de Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if (!$this->validateDriverOwnership($driver, $carrier->id)) {
            $this->logUnauthorizedAccess('crear accidente para conductor no autorizado', [
                'carrier_id' => $carrier->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para crear accidentes para este conductor.');
        }

        // Convertir checkboxes a valores booleanos
        $validated['had_injuries'] = isset($request->had_injuries);
        $validated['had_fatalities'] = isset($request->had_fatalities);

        // Solo incluir el número de lesiones/fatalidades si se marcó el checkbox
        if (!$validated['had_injuries']) {
            $validated['number_of_injuries'] = null;
        }
        if (!$validated['had_fatalities']) {
            $validated['number_of_fatalities'] = null;
        }

        try {
            DB::beginTransaction();
            
            // Crear el registro de accidente
            $accident = DriverAccident::create($validated);
            
            // Procesar archivos desde datos JSON de Livewire si existen
            if ($request->filled('accident_files_json')) {
                try {
                    $filesData = json_decode($request->accident_files_json, true);
                    if (is_array($filesData)) {
                        foreach ($filesData as $fileData) {
                            if (isset($fileData['path']) && file_exists(storage_path('app/' . $fileData['path']))) {
                                $accident->addMedia(storage_path('app/' . $fileData['path']))
                                    ->withCustomProperties([
                                        'accident_id' => $accident->id,
                                        'driver_id' => $accident->user_driver_detail_id,
                                        'uploaded_by' => Auth::id(),
                                    ])
                                    ->toMediaCollection('accident-images');
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error al procesar archivos JSON de Livewire', [
                        'error' => $e->getMessage(),
                        'accident_id' => $accident->id,
                    ]);
                }
            }
            
            // Procesar archivos tradicionales si existen
            if ($request->hasFile('accident_files')) {
                foreach ($request->file('accident_files') as $file) {
                    $accident->addMedia($file)
                        ->withCustomProperties([
                            'accident_id' => $accident->id,
                            'driver_id' => $accident->user_driver_detail_id,
                            'uploaded_by' => Auth::id(),
                        ])
                        ->toMediaCollection('accident-images');
                }
            }
            
            // Regenerar PDF de accidentes del conductor
            $this->regenerateAccidentPDF($accident->user_driver_detail_id);
            
            DB::commit();
            
            Log::info('Registro de accidente creado exitosamente', [
                'accident_id' => $accident->id,
                'driver_id' => $accident->user_driver_detail_id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            Session::flash('success', 'Registro de accidente añadido exitosamente.');
            
            // Redirigir a la página apropiada
            if ($request->has('redirect_to_driver')) {
                return redirect()->route('carrier.drivers.accidents.driver_history', $validated['user_driver_detail_id']);
            }
            
            return redirect()->route('carrier.drivers.accidents.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear registro de accidente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al crear el registro de accidente. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Mostrar el formulario para editar un registro de accidente.
     * 
     * @param DriverAccident $accident
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(DriverAccident $accident)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el conductor pertenezca al carrier del usuario autenticado
            if (!$this->validateAccidentOwnership($accident, $carrier->id)) {
                $this->logUnauthorizedAccess('editar accidente no autorizado', [
                    'accident_id' => $accident->id,
                    'carrier_id' => $carrier->id,
                    'accident_carrier_id' => $accident->userDriverDetail->carrier_id,
                ]);
                
                abort(403, 'No tienes permiso para editar este registro de accidente.');
            }
            
            Log::info('Formulario de edición de accidente accedido', [
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Load only carrier's drivers for dropdown
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrier->id)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            // Cargar documentos del sistema antiguo
            $oldDocuments = $accident->getDocuments('default');
            
            // Cargar archivos de Media Library
            $mediaDocuments = $accident->getMedia('accident-images');
                
            return view('carrier.drivers.accidents.edit', compact('accident', 'drivers', 'carrier', 'oldDocuments', 'mediaDocuments'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición de accidente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'accident_id' => $accident->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.accidents.index')
                ->with('error', 'Ocurrió un error al cargar el formulario de edición. Por favor, intente nuevamente.');
        }
    }

    /**
     * Actualizar un registro de accidente.
     * 
     * @param Request $request
     * @param DriverAccident $accident
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, DriverAccident $accident)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->validateAccidentOwnership($accident, $carrier->id)) {
            $this->logUnauthorizedAccess('actualizar accidente no autorizado', [
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'accident_carrier_id' => $accident->userDriverDetail->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para actualizar este registro de accidente.');
        }
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'accident_date' => 'required|date',
            'nature_of_accident' => 'required|string|max:255',
            'had_injuries' => 'boolean',
            'number_of_injuries' => 'nullable|integer|min:0',
            'had_fatalities' => 'boolean',
            'number_of_fatalities' => 'nullable|integer|min:0',
            'comments' => 'nullable|string',
            'accident_files' => 'nullable|array',
            'accident_files.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'accident_files_json' => 'nullable|string', // Para archivos de Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if (!$this->validateDriverOwnership($driver, $carrier->id)) {
            $this->logUnauthorizedAccess('cambiar conductor del accidente a conductor no autorizado', [
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para asignar este accidente a ese conductor.');
        }

        // Convertir checkboxes a valores booleanos
        $validated['had_injuries'] = isset($request->had_injuries);
        $validated['had_fatalities'] = isset($request->had_fatalities);

        // Solo incluir el número de lesiones/fatalidades si se marcó el checkbox
        if (!$validated['had_injuries']) {
            $validated['number_of_injuries'] = null;
        }
        if (!$validated['had_fatalities']) {
            $validated['number_of_fatalities'] = null;
        }

        try {
            DB::beginTransaction();
            
            // Actualizar el registro de accidente
            $accident->update($validated);
            
            // Procesar archivos desde datos JSON de Livewire si existen
            if ($request->filled('accident_files_json')) {
                try {
                    $filesData = json_decode($request->accident_files_json, true);
                    if (is_array($filesData)) {
                        foreach ($filesData as $fileData) {
                            if (isset($fileData['path']) && file_exists(storage_path('app/' . $fileData['path']))) {
                                $accident->addMedia(storage_path('app/' . $fileData['path']))
                                    ->withCustomProperties([
                                        'accident_id' => $accident->id,
                                        'driver_id' => $accident->user_driver_detail_id,
                                        'uploaded_by' => Auth::id(),
                                    ])
                                    ->toMediaCollection('accident-images');
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error al procesar archivos JSON de Livewire en actualización', [
                        'error' => $e->getMessage(),
                        'accident_id' => $accident->id,
                    ]);
                }
            }
            
            // Procesar nuevos archivos tradicionales si existen
            if ($request->hasFile('accident_files')) {
                foreach ($request->file('accident_files') as $file) {
                    $accident->addMedia($file)
                        ->withCustomProperties([
                            'accident_id' => $accident->id,
                            'driver_id' => $accident->user_driver_detail_id,
                            'uploaded_by' => Auth::id(),
                        ])
                        ->toMediaCollection('accident-images');
                }
            }
            
            // Regenerar PDF de accidentes del conductor
            $this->regenerateAccidentPDF($accident->user_driver_detail_id);
            
            DB::commit();
            
            Log::info('Registro de accidente actualizado exitosamente', [
                'accident_id' => $accident->id,
                'driver_id' => $accident->user_driver_detail_id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            Session::flash('success', 'Registro de accidente actualizado exitosamente.');
            
            // Redirigir a la página apropiada
            if ($request->has('redirect_to_driver')) {
                return redirect()->route('carrier.drivers.accidents.driver_history', $accident->user_driver_detail_id);
            }
            
            return redirect()->route('carrier.drivers.accidents.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar registro de accidente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al actualizar el registro de accidente. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Eliminar un registro de accidente.
     * 
     * @param DriverAccident $accident
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DriverAccident $accident)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->validateAccidentOwnership($accident, $carrier->id)) {
            $this->logUnauthorizedAccess('eliminar accidente no autorizado', [
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'accident_carrier_id' => $accident->userDriverDetail->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para eliminar este registro de accidente.');
        }
        
        try {
            DB::beginTransaction();
            
            $driverId = $accident->user_driver_detail_id;
            $accidentId = $accident->id;
            
            // Eliminar todos los documentos asociados (físicos y registros)
            // El método deleteAllDocuments() del modelo se encarga de eliminar:
            // - Documentos del sistema antiguo
            // - Archivos de Media Library
            // Esto se ejecuta automáticamente en el evento 'deleting' del modelo
            
            // Eliminar el registro de accidente
            $accident->delete();
            
            // Regenerar PDF de accidentes del conductor
            $this->regenerateAccidentPDF($driverId);
            
            DB::commit();
            
            Log::info('Registro de accidente eliminado exitosamente', [
                'accident_id' => $accidentId,
                'driver_id' => $driverId,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            Session::flash('success', 'Registro de accidente eliminado exitosamente.');
            
            // Determinar la ruta de retorno basado en la URL de referencia
            $referer = request()->headers->get('referer');
            if (strpos($referer, 'driver_history') !== false) {
                return redirect()->route('carrier.drivers.accidents.driver_history', $driverId);
            }
            
            return redirect()->route('carrier.drivers.accidents.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al eliminar registro de accidente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.accidents.index')
                ->with('error', 'Ocurrió un error al eliminar el registro de accidente. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar todos los documentos de accidentes del carrier con filtros.
     */
    public function documents(Request $request)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Vista de documentos de accidentes accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['driver_id', 'file_type', 'start_date', 'end_date', 'accident_id']),
            ]);
            
            // Query base para obtener documentos de Media Library (optimizado con joins)
            $mediaQuery = Media::query()
                ->select('media.*')
                ->where('media.model_type', DriverAccident::class)
                ->where('media.collection_name', 'accident-images')
                ->join('driver_accidents', function ($join) {
                    $join->on('media.model_id', '=', 'driver_accidents.id')
                         ->where('media.model_type', '=', DriverAccident::class);
                })
                ->join('user_driver_details', 'driver_accidents.user_driver_detail_id', '=', 'user_driver_details.id')
                ->where('user_driver_details.carrier_id', $carrier->id)
                ->with(['model.userDriverDetail.user:id,name,email']);

            // Query base para documentos del sistema antiguo (optimizado con joins)
            $oldDocsQuery = DocumentAttachment::query()
                ->select('document_attachments.*')
                ->where('document_attachments.documentable_type', DriverAccident::class)
                ->join('driver_accidents', function ($join) {
                    $join->on('document_attachments.documentable_id', '=', 'driver_accidents.id')
                         ->where('document_attachments.documentable_type', '=', DriverAccident::class);
                })
                ->join('user_driver_details', 'driver_accidents.user_driver_detail_id', '=', 'user_driver_details.id')
                ->where('user_driver_details.carrier_id', $carrier->id)
                ->with(['documentable.userDriverDetail.user:id,name,email']);

            // Aplicar filtros (optimizado sin whereHas)
            if ($request->filled('driver_id')) {
                $mediaQuery->where('user_driver_details.id', $request->driver_id);
                $oldDocsQuery->where('user_driver_details.id', $request->driver_id);
            }

            if ($request->filled('file_type')) {
                $fileType = $request->file_type;
                if ($fileType === 'image') {
                    $mediaQuery->where('mime_type', 'like', 'image/%');
                    $oldDocsQuery->where('mime_type', 'like', 'image/%');
                } elseif ($fileType === 'pdf') {
                    $mediaQuery->where('mime_type', 'application/pdf');
                    $oldDocsQuery->where('mime_type', 'application/pdf');
                } elseif ($fileType === 'document') {
                    $mediaQuery->whereIn('mime_type', [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ]);
                    $oldDocsQuery->whereIn('mime_type', [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ]);
                }
            }

            if ($request->filled('start_date')) {
                try {
                    $startDate = \Carbon\Carbon::createFromFormat('m-d-Y', $request->start_date)->startOfDay();
                    $mediaQuery->where('driver_accidents.accident_date', '>=', $startDate);
                    $oldDocsQuery->where('driver_accidents.accident_date', '>=', $startDate);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en start_date de documentos', [
                        'start_date' => $request->start_date,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    Session::flash('warning', 'El formato de fecha "Desde" es inválido. Use MM-DD-YYYY.');
                }
            }

            if ($request->filled('end_date')) {
                try {
                    $endDate = \Carbon\Carbon::createFromFormat('m-d-Y', $request->end_date)->endOfDay();
                    $mediaQuery->where('driver_accidents.accident_date', '<=', $endDate);
                    $oldDocsQuery->where('driver_accidents.accident_date', '<=', $endDate);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en end_date de documentos', [
                        'end_date' => $request->end_date,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    Session::flash('warning', 'El formato de fecha "Hasta" es inválido. Use MM-DD-YYYY.');
                }
            }

            if ($request->filled('accident_id')) {
                $mediaQuery->where('driver_accidents.id', $request->accident_id);
                $oldDocsQuery->where('driver_accidents.id', $request->accident_id);
            }

            // Obtener resultados
            $mediaDocuments = $mediaQuery->orderBy('created_at', 'desc')->get();
            $oldDocuments = $oldDocsQuery->orderBy('created_at', 'desc')->get();

            // Combinar y paginar manualmente
            $allDocuments = collect([
                ...$mediaDocuments->map(fn($media) => [
                    'id' => 'media_' . $media->id,
                    'type' => 'media',
                    'name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'created_at' => $media->created_at,
                    'accident' => $media->model,
                    'driver' => $media->model->userDriverDetail,
                    'media_object' => $media,
                ]),
                ...$oldDocuments->map(fn($doc) => [
                    'id' => 'doc_' . $doc->id,
                    'type' => 'document',
                    'name' => $doc->original_name,
                    'mime_type' => $doc->mime_type,
                    'size' => $doc->size,
                    'created_at' => $doc->created_at,
                    'accident' => $doc->documentable,
                    'driver' => $doc->documentable->userDriverDetail,
                    'document_object' => $doc,
                ]),
            ])->sortByDesc('created_at');

            // Paginar manualmente
            $perPage = 15;
            $currentPage = $request->get('page', 1);
            $documents = new \Illuminate\Pagination\LengthAwarePaginator(
                $allDocuments->forPage($currentPage, $perPage),
                $allDocuments->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Obtener lista de conductores para el filtro (optimizado)
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->with('user:id,name,email')
                ->get();

            // Obtener lista de accidentes para el filtro (optimizado con join)
            $accidents = DriverAccident::select('driver_accidents.*')
                ->join('user_driver_details', 'driver_accidents.user_driver_detail_id', '=', 'user_driver_details.id')
                ->where('user_driver_details.carrier_id', $carrier->id)
                ->with('userDriverDetail.user:id,name,email')
                ->orderBy('driver_accidents.accident_date', 'desc')
                ->get();

            return view('carrier.drivers.accidents.documents', compact('documents', 'drivers', 'accidents', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de documentos de accidentes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->route('carrier.drivers.accidents.index')
                ->with('error', 'Ocurrió un error al cargar los documentos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar documentos de un accidente específico.
     */
    public function showDocuments(DriverAccident $accident)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el accidente pertenezca al carrier
            if (!$this->validateAccidentOwnership($accident, $carrier->id)) {
                $this->logUnauthorizedAccess('ver documentos de accidente no autorizado', [
                    'accident_id' => $accident->id,
                    'carrier_id' => $carrier->id,
                    'accident_carrier_id' => $accident->userDriverDetail->carrier_id,
                ]);
                
                abort(403, 'No tienes permiso para ver los documentos de este accidente.');
            }

            Log::info('Documentos de accidente específico accedidos', [
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);

            // Obtener documentos del sistema antiguo
            $oldDocuments = $accident->getDocuments('default');

            // Obtener archivos de Media Library
            $mediaDocuments = $accident->getMedia('accident-images');

            return view('carrier.drivers.accidents.show_documents', compact('accident', 'oldDocuments', 'mediaDocuments', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar documentos de accidente específico', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'accident_id' => $accident->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.accidents.index')
                ->with('error', 'Ocurrió un error al cargar los documentos del accidente. Por favor, intente nuevamente.');
        }
    }

    /**
     * Agregar documentos a un accidente existente.
     */
    public function storeDocuments(Request $request, DriverAccident $accident)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el accidente pertenezca al carrier
            if (!$this->validateAccidentOwnership($accident, $carrier->id)) {
                $this->logUnauthorizedAccess('agregar documentos a accidente no autorizado', [
                    'accident_id' => $accident->id,
                    'carrier_id' => $carrier->id,
                    'accident_carrier_id' => $accident->userDriverDetail->carrier_id,
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'No tienes permiso para agregar documentos a este accidente.'], 403);
                }
                abort(403, 'No tienes permiso para agregar documentos a este accidente.');
            }

            $validated = $request->validate([
                'accident_files' => 'required|array',
                'accident_files.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            ]);

            DB::beginTransaction();

            $uploadedCount = 0;
            foreach ($request->file('accident_files') as $file) {
                $accident->addMedia($file)
                    ->withCustomProperties([
                        'accident_id' => $accident->id,
                        'driver_id' => $accident->user_driver_detail_id,
                        'uploaded_by' => Auth::id(),
                    ])
                    ->toMediaCollection('accident-images');
                $uploadedCount++;
            }

            DB::commit();

            Log::info('Documentos agregados a accidente exitosamente', [
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'uploaded_count' => $uploadedCount,
            ]);

            $message = "Se subieron {$uploadedCount} documento(s) exitosamente.";
            
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            Session::flash('success', $message);
            return redirect()->route('carrier.drivers.accidents.documents.show', $accident);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validación fallida al subir documentos de accidente', [
                'accident_id' => $accident->id ?? null,
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Datos de validación inválidos.', 'errors' => $e->errors()], 422);
            }
            
            throw $e;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al subir documentos de accidente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'accident_id' => $accident->id ?? null,
                'carrier_id' => $carrier->id ?? null,
                'user_id' => Auth::id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Ocurrió un error al subir los documentos. Por favor, intente nuevamente.'], 500);
            }

            return redirect()->back()
                ->with('error', 'Ocurrió un error al subir los documentos. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Eliminar un documento del sistema antiguo.
     */
    public function destroyDocument(DocumentAttachment $document)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el documento pertenezca a un accidente del carrier
            if ($document->documentable_type !== DriverAccident::class) {
                $this->logUnauthorizedAccess('eliminar documento de tipo no válido', [
                    'document_id' => $document->id,
                    'documentable_type' => $document->documentable_type,
                    'carrier_id' => $carrier->id,
                ]);
                
                if (request()->expectsJson()) {
                    return response()->json(['error' => 'Documento no válido.'], 400);
                }
                abort(400, 'Documento no válido.');
            }

            $accident = $document->documentable;
            
            if (!$accident || !$this->validateAccidentOwnership($accident, $carrier->id)) {
                $this->logUnauthorizedAccess('eliminar documento de accidente no autorizado', [
                    'document_id' => $document->id,
                    'accident_id' => $accident ? $accident->id : null,
                    'carrier_id' => $carrier->id,
                    'accident_carrier_id' => $accident ? $accident->userDriverDetail->carrier_id : null,
                ]);
                
                if (request()->expectsJson()) {
                    return response()->json(['error' => 'No tienes permiso para eliminar este documento.'], 403);
                }
                abort(403, 'No tienes permiso para eliminar este documento.');
            }

            $accident->deleteDocument($document->id);

            Log::info('Documento de accidente eliminado exitosamente', [
                'document_id' => $document->id,
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);

            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Documento eliminado exitosamente.']);
            }

            Session::flash('success', 'Documento eliminado exitosamente.');
            return redirect()->back();

        } catch (\Exception $e) {
            Log::error('Error al eliminar documento de accidente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'document_id' => $document->id ?? null,
                'accident_id' => $accident->id ?? null,
                'carrier_id' => $carrier->id ?? null,
                'user_id' => Auth::id(),
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Ocurrió un error al eliminar el documento. Por favor, intente nuevamente.'], 500);
            }

            return redirect()->back()->with('error', 'Ocurrió un error al eliminar el documento. Por favor, intente nuevamente.');
        }
    }

    /**
     * Previsualizar un documento (imagen o PDF).
     */
    public function previewDocument($documentId)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Determinar si es Media Library o sistema antiguo por el prefijo
            $isMedia = str_starts_with($documentId, 'media_');
            $actualId = $isMedia ? str_replace('media_', '', $documentId) : str_replace('doc_', '', $documentId);

            Log::info('Previsualización de documento solicitada', [
                'document_id' => $documentId,
                'is_media' => $isMedia,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);

            if ($isMedia) {
                $media = Media::findOrFail($actualId);
                
                // Verificar ownership
                if ($media->model_type !== DriverAccident::class) {
                    $this->logUnauthorizedAccess('previsualizar documento de tipo no válido', [
                        'media_id' => $media->id,
                        'model_type' => $media->model_type,
                        'carrier_id' => $carrier->id,
                    ]);
                    abort(403, 'No tienes permiso para acceder a este documento.');
                }
                
                $accident = $media->model;
                if (!$accident || !$this->validateAccidentOwnership($accident, $carrier->id)) {
                    $this->logUnauthorizedAccess('previsualizar documento de accidente no autorizado', [
                        'media_id' => $media->id,
                        'accident_id' => $accident ? $accident->id : null,
                        'carrier_id' => $carrier->id,
                        'accident_carrier_id' => $accident ? $accident->userDriverDetail->carrier_id : null,
                    ]);
                    abort(403, 'No tienes permiso para acceder a este documento.');
                }

                // Servir el archivo
                $path = $media->getPath();
                if (!file_exists($path)) {
                    Log::warning('Archivo de media no encontrado en el sistema de archivos', [
                        'media_id' => $media->id,
                        'path' => $path,
                        'carrier_id' => $carrier->id,
                    ]);
                    abort(404, 'Archivo no encontrado.');
                }

                return response()->file($path, [
                    'Content-Type' => $media->mime_type,
                    'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
                ]);

            } else {
                $document = DocumentAttachment::findOrFail($actualId);
                
                // Verificar ownership
                if ($document->documentable_type !== DriverAccident::class) {
                    $this->logUnauthorizedAccess('previsualizar documento de tipo no válido', [
                        'document_id' => $document->id,
                        'documentable_type' => $document->documentable_type,
                        'carrier_id' => $carrier->id,
                    ]);
                    abort(403, 'No tienes permiso para acceder a este documento.');
                }
                
                $accident = $document->documentable;
                if (!$accident || !$this->validateAccidentOwnership($accident, $carrier->id)) {
                    $this->logUnauthorizedAccess('previsualizar documento de accidente no autorizado', [
                        'document_id' => $document->id,
                        'accident_id' => $accident ? $accident->id : null,
                        'carrier_id' => $carrier->id,
                        'accident_carrier_id' => $accident ? $accident->userDriverDetail->carrier_id : null,
                    ]);
                    abort(403, 'No tienes permiso para acceder a este documento.');
                }

                // Servir el archivo
                $path = Storage::disk('public')->path($document->file_path);
                if (!file_exists($path)) {
                    Log::warning('Archivo de documento no encontrado en el sistema de archivos', [
                        'document_id' => $document->id,
                        'path' => $path,
                        'carrier_id' => $carrier->id,
                    ]);
                    abort(404, 'Archivo no encontrado.');
                }

                return response()->file($path, [
                    'Content-Type' => $document->mime_type,
                    'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
                ]);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Documento no encontrado en la base de datos', [
                'document_id' => $documentId ?? null,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            abort(404, 'Documento no encontrado.');
            
        } catch (\Exception $e) {
            Log::error('Error al previsualizar documento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'document_id' => $documentId ?? null,
                'carrier_id' => $carrier->id ?? null,
                'user_id' => Auth::id(),
            ]);

            abort(500, 'Ocurrió un error al cargar el documento. Por favor, intente nuevamente.');
        }
    }

    /**
     * Eliminar un archivo de Media Library vía AJAX.
     */
    public function ajaxDestroyMedia($mediaId)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            $media = Media::findOrFail($mediaId);
            
            // Verificar que sea un documento de accidente
            if ($media->model_type !== DriverAccident::class) {
                $this->logUnauthorizedAccess('eliminar media de tipo no válido vía AJAX', [
                    'media_id' => $mediaId,
                    'model_type' => $media->model_type,
                    'carrier_id' => $carrier->id,
                ]);
                return response()->json(['error' => 'Documento no válido.'], 400);
            }

            $accident = $media->model;
            
            // Verificar ownership
            if (!$accident || !$this->validateAccidentOwnership($accident, $carrier->id)) {
                $this->logUnauthorizedAccess('eliminar media de accidente no autorizado vía AJAX', [
                    'media_id' => $mediaId,
                    'accident_id' => $accident ? $accident->id : null,
                    'carrier_id' => $carrier->id,
                    'accident_carrier_id' => $accident ? $accident->userDriverDetail->carrier_id : null,
                ]);
                return response()->json(['error' => 'No tienes permiso para eliminar este documento.'], 403);
            }

            // Eliminar usando el método seguro del modelo
            $accident->safeDeleteMedia($mediaId);

            Log::info('Archivo de Media Library eliminado vía AJAX exitosamente', [
                'media_id' => $mediaId,
                'accident_id' => $accident->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Media no encontrado en la base de datos para eliminación AJAX', [
                'media_id' => $mediaId ?? null,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return response()->json(['error' => 'Archivo no encontrado.'], 404);
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar archivo de Media Library vía AJAX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'media_id' => $mediaId ?? null,
                'carrier_id' => $carrier->id ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'Ocurrió un error al eliminar el archivo. Por favor, intente nuevamente.'
            ], 500);
        }
    }
    
    /**
     * Regenerar el PDF de accidentes del conductor.
     * 
     * Este método integra con el sistema existente de generación de PDFs del conductor.
     * Genera un PDF actualizado con todos los accidentes del conductor.
     * 
     * @param int $driverDetailId ID del conductor
     * @return void
     */
    private function regenerateAccidentPDF($driverDetailId)
    {
        try {
            // Cargar UserDriverDetail con las mismas relaciones que en el Admin
            $userDriverDetail = UserDriverDetail::with([
                'user',
                'carrier',
                'certification',
                'accidents'
            ])->find($driverDetailId);

            if (!$userDriverDetail) {
                Log::warning('UserDriverDetail not found for accident PDF regeneration', [
                    'driver_id' => $driverDetailId,
                    'user_id' => Auth::id(),
                ]);
                return;
            }

            // Obtener firma del conductor desde su certificación
            $signaturePath = null;
            if ($userDriverDetail->certification) {
                $signatureMedia = $userDriverDetail->certification->getMedia('signature')->first();
                if ($signatureMedia) {
                    $signaturePath = $signatureMedia->getPath();
                }
            }

            // Crear instancia de DriverCertificationStep para acceder a métodos privados
            $certificationStep = new \App\Livewire\Admin\Driver\DriverCertificationStep();
            
            // Obtener fechas efectivas usando reflexión para acceder al método privado
            $reflection = new \ReflectionClass($certificationStep);
            $getEffectiveDatesMethod = $reflection->getMethod('getEffectiveDates');
            $getEffectiveDatesMethod->setAccessible(true);
            $effectiveDates = $getEffectiveDatesMethod->invoke($certificationStep, $driverDetailId);
            
            // Preparar formatted_dates con ambas fechas cuando corresponda
            $formattedDates = [
                'updated_at' => $effectiveDates['updated_at']->format('m/d/Y'),
                'updated_at_long' => $effectiveDates['updated_at']->format('F j, Y')
            ];
            
            // Siempre incluir created_at (fecha de registro normal)
            if ($effectiveDates['show_created_at'] && $effectiveDates['created_at']) {
                $formattedDates['created_at'] = $effectiveDates['created_at']->format('m/d/Y');
                $formattedDates['created_at_long'] = $effectiveDates['created_at']->format('F j, Y');
            }
            
            // Incluir custom_created_at solo si está habilitado y tiene valor
            if ($effectiveDates['show_custom_created_at'] && $effectiveDates['custom_created_at']) {
                $formattedDates['custom_created_at'] = $effectiveDates['custom_created_at']->format('m/d/Y');
                $formattedDates['custom_created_at_long'] = $effectiveDates['custom_created_at']->format('F j, Y');
            }

            // Preparar datos para el PDF
            $pdfData = [
                'userDriverDetail' => $userDriverDetail,
                'signaturePath' => $signaturePath,
                'title' => 'Accident Record ',
                'date' => now()->format('m/d/Y'),
                'created_at' => $effectiveDates['created_at'],
                'updated_at' => $effectiveDates['updated_at'],
                'custom_created_at' => $effectiveDates['custom_created_at'],
                'formatted_dates' => $formattedDates,
                'use_custom_dates' => $effectiveDates['show_custom_created_at']
            ];

            // Generar el PDF usando dompdf
            $pdf = \Illuminate\Support\Facades\App::make('dompdf.wrapper')->loadView('pdf.driver.accident', $pdfData);
            
            // Guardar el PDF en el sistema de archivos
            $fileName = 'accident_record.pdf';
            $driverPath = 'driver/' . $userDriverDetail->id;
            $appSubPath = $driverPath . '/driver_applications';
            
            // Asegurar que los directorios existen
            Storage::disk('public')->makeDirectory($driverPath);
            Storage::disk('public')->makeDirectory($appSubPath);
            
            // Guardar PDF
            $pdfContent = $pdf->output();
            Storage::disk('public')->put($appSubPath . '/' . $fileName, $pdfContent);

            Log::info('PDF de accidente regenerado exitosamente', [
                'driver_id' => $driverDetailId,
                'file_path' => $appSubPath . '/' . $fileName,
                'has_signature' => $signaturePath !== null,
                'user_id' => Auth::id(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al regenerar PDF de accidentes del conductor', [
                'error' => $e->getMessage(),
                'driver_detail_id' => $driverDetailId,
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            // No lanzamos la excepción para no interrumpir el flujo principal
        }
    }
}
