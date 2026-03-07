<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierTrainingSchoolsController extends Controller
{
    /**
     * Validar que una escuela de entrenamiento pertenezca al carrier del usuario autenticado.
     * 
     * @param DriverTrainingSchool $school
     * @param int $carrierId
     * @return bool
     */
    private function validateSchoolOwnership(DriverTrainingSchool $school, $carrierId)
    {
        return (int) $school->userDriverDetail->carrier_id === (int) $carrierId;
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
     * Mostrar la lista de escuelas de entrenamiento de los conductores del carrier.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Vista de índice de training schools accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'driver_filter', 'date_from', 'date_to', 'sort_field', 'sort_direction']),
            ]);
            
            $query = DriverTrainingSchool::query()
                ->with(['userDriverDetail.user'])
                ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                });

            // Aplicar filtros
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('school_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('city', 'like', '%' . $searchTerm . '%')
                      ->orWhere('state', 'like', '%' . $searchTerm . '%');
                });
            }

            if ($request->filled('driver_filter')) {
                $query->where('user_driver_detail_id', $request->driver_filter);
            }

            // Filtro de rango de fechas con formato MM/DD/YYYY
            if ($request->filled('date_from')) {
                try {
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_from)->startOfDay();
                    $query->whereDate('date_start', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_from', [
                        'date_from' => $request->date_from,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    session()->flash('warning', 'El formato de fecha "Desde" es inválido. Use MM/DD/YYYY.');
                }
            }

            if ($request->filled('date_to')) {
                try {
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_to)->endOfDay();
                    $query->whereDate('date_end', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_to', [
                        'date_to' => $request->date_to,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    session()->flash('warning', 'El formato de fecha "Hasta" es inválido. Use MM/DD/YYYY.');
                }
            }

            // Ordenar resultados
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $trainingSchools = $query->paginate(10);
            
            // Obtener lista de conductores para el filtro
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->with('user:id,name,email')
                ->get();

            return view('carrier.drivers.training-schools.index', compact('trainingSchools', 'drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de índice de training schools', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al cargar la lista de escuelas de entrenamiento. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar el formulario para crear una nueva escuela de entrenamiento.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Cargar solo conductores del carrier
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrier->id)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            Log::info('Formulario de creación de training school accedido', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'available_drivers_count' => $drivers->count(),
            ]);
                
            return view('carrier.drivers.training-schools.create', compact('drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de training school', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.training-schools.index')
                ->with('error', 'Ocurrió un error al cargar el formulario. Por favor, intente nuevamente.');
        }
    }

    /**
     * Almacenar una nueva escuela de entrenamiento.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'school_name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'training_skills' => 'nullable|array',
            'training_files' => 'nullable|string', // JSON de archivos del componente Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if (!$this->validateDriverOwnership($driver, $carrier->id)) {
            $this->logUnauthorizedAccess('crear training school para conductor no autorizado', [
                'carrier_id' => $carrier->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para crear escuelas de entrenamiento para este conductor.');
        }

        try {
            DB::beginTransaction();
            
            // Crear el registro de escuela de entrenamiento
            $trainingSchool = new DriverTrainingSchool();
            $trainingSchool->user_driver_detail_id = $request->user_driver_detail_id;
            $trainingSchool->date_start = $request->date_start;
            $trainingSchool->date_end = $request->date_end;
            $trainingSchool->school_name = $request->school_name;
            $trainingSchool->city = $request->city;
            $trainingSchool->state = $request->state;

            $trainingSchool->graduated = $request->has('graduated');
            $trainingSchool->subject_to_safety_regulations = $request->has('subject_to_safety_regulations');
            $trainingSchool->performed_safety_functions = $request->has('performed_safety_functions');

            // Guardar habilidades de entrenamiento como JSON
            if ($request->has('training_skills')) {
                $trainingSchool->training_skills = json_encode($request->training_skills);
            }

            $trainingSchool->save();

            // Procesar archivos si existen usando Spatie Media Library
            if ($request->filled('training_files')) {
                $filesData = json_decode($request->training_files, true);
                
                if (is_array($filesData)) {
                    foreach ($filesData as $fileData) {
                        if (!empty($fileData['name'])) {
                            try {
                                // Ruta del archivo temporal
                                $tempPath = isset($fileData['tempPath']) 
                                    ? $fileData['tempPath'] 
                                    : (isset($fileData['path']) 
                                        ? $fileData['path'] 
                                        : null);
                                
                                if (empty($tempPath)) {
                                    Log::warning('Archivo sin ruta temporal', ['file' => $fileData]);
                                    continue;
                                }
                                
                                // Verificar que el archivo temporal existe
                                if (!Storage::exists($tempPath)) {
                                    // Intentar buscar en la carpeta temp directamente
                                    $tempPath = 'temp/' . basename($tempPath);
                                    
                                    if (!Storage::exists($tempPath)) {
                                        Log::error('Archivo temporal no encontrado (store)', [
                                            'temp_path' => $tempPath,
                                            'original_name' => $fileData['name']
                                        ]);
                                        continue;
                                    }
                                }
                                
                                // Obtener la ruta completa del archivo temporal
                                $fullTempPath = Storage::path($tempPath);
                                
                                // Añadir el archivo a la colección de Spatie Media Library
                                $media = $trainingSchool->addMedia($fullTempPath)
                                    ->usingName($fileData['name'])
                                    ->usingFileName($fileData['name'])
                                    ->withCustomProperties([
                                        'document_type' => 'training_certificate',
                                        'uploaded_by' => Auth::id(),
                                        'description' => 'Training School Document'
                                    ])
                                    ->toMediaCollection('school_certificates');
                                
                                Log::info('Documento guardado correctamente con Spatie Media Library', [
                                    'media_id' => $media->id,
                                    'file_name' => $fileData['name'],
                                    'training_school_id' => $trainingSchool->id
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Error al procesar archivo con Spatie Media Library', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'file' => $fileData
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            
            Log::info('Registro de training school creado exitosamente', [
                'training_school_id' => $trainingSchool->id,
                'driver_id' => $trainingSchool->user_driver_detail_id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            session()->flash('success', 'Escuela de entrenamiento añadida exitosamente.');
            
            return redirect()->route('carrier.training-schools.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear registro de training school', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al crear la escuela de entrenamiento. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Mostrar los detalles y documentos de una escuela de entrenamiento.
     * 
     * @param DriverTrainingSchool $trainingSchool
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(DriverTrainingSchool $trainingSchool)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que la escuela pertenezca al carrier del usuario autenticado
            if (!$this->validateSchoolOwnership($trainingSchool, $carrier->id)) {
                $this->logUnauthorizedAccess('ver training school no autorizada', [
                    'training_school_id' => $trainingSchool->id,
                    'carrier_id' => $carrier->id,
                    'school_carrier_id' => $trainingSchool->userDriverDetail->carrier_id,
                ]);
                
                abort(403, 'No tienes permiso para ver esta escuela de entrenamiento.');
            }
            
            Log::info('Vista de documentos de training school accedida', [
                'training_school_id' => $trainingSchool->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            $trainingSchool->load('userDriverDetail.user');
            $school = $trainingSchool; // Renombrar para consistencia con la vista
            
            return view('carrier.drivers.training-schools.show', compact('school', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de documentos de training school', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_school_id' => $trainingSchool->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.training-schools.index')
                ->with('error', 'Ocurrió un error al cargar los documentos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar el formulario para editar una escuela de entrenamiento.
     * 
     * @param DriverTrainingSchool $trainingSchool
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(DriverTrainingSchool $trainingSchool)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que la escuela pertenezca al carrier del usuario autenticado
            if (!$this->validateSchoolOwnership($trainingSchool, $carrier->id)) {
                $this->logUnauthorizedAccess('editar training school no autorizada', [
                    'training_school_id' => $trainingSchool->id,
                    'carrier_id' => $carrier->id,
                    'school_carrier_id' => $trainingSchool->userDriverDetail->carrier_id,
                ]);
                
                abort(403, 'No tienes permiso para editar esta escuela de entrenamiento.');
            }
            
            Log::info('Formulario de edición de training school accedido', [
                'training_school_id' => $trainingSchool->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            $trainingSchool->load('userDriverDetail.user');
            
            // Cargar solo conductores del carrier
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrier->id)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            // Cargar documentos existentes desde Spatie Media Library
            $documents = Media::where('model_type', DriverTrainingSchool::class)
                ->where('model_id', $trainingSchool->id)
                ->where('collection_name', 'school_certificates')
                ->get();
            
            // Convertir los documentos a un formato que el componente FileUploader pueda entender
            $existingFilesArray = [];
            foreach ($documents as $media) {
                $existingFilesArray[] = [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'original_name' => $media->name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'file_path' => $media->getUrl(),
                    'url' => $media->getUrl(),
                    'is_existing' => true,
                    'document_id' => $media->id,
                    'created_at' => $media->created_at->format('Y-m-d H:i:s')
                ];
            }
            
            // Decodificar training_skills para los checkboxes
            $trainingSkills = is_array($trainingSchool->training_skills) 
                ? $trainingSchool->training_skills 
                : (json_decode($trainingSchool->training_skills) ?: []);
                
            return view('carrier.drivers.training-schools.edit', compact(
                'trainingSchool',
                'drivers',
                'carrier',
                'existingFilesArray',
                'trainingSkills'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición de training school', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_school_id' => $trainingSchool->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.training-schools.index')
                ->with('error', 'Ocurrió un error al cargar el formulario de edición. Por favor, intente nuevamente.');
        }
    }

    /**
     * Actualizar una escuela de entrenamiento existente.
     * 
     * @param Request $request
     * @param DriverTrainingSchool $trainingSchool
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, DriverTrainingSchool $trainingSchool)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que la escuela pertenezca al carrier del usuario autenticado
        if (!$this->validateSchoolOwnership($trainingSchool, $carrier->id)) {
            $this->logUnauthorizedAccess('actualizar training school no autorizada', [
                'training_school_id' => $trainingSchool->id,
                'carrier_id' => $carrier->id,
                'school_carrier_id' => $trainingSchool->userDriverDetail->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para actualizar esta escuela de entrenamiento.');
        }
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'school_name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'training_skills' => 'nullable|array',
            'training_files' => 'nullable|string', // JSON de archivos del componente Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if (!$this->validateDriverOwnership($driver, $carrier->id)) {
            $this->logUnauthorizedAccess('cambiar conductor de training school a conductor no autorizado', [
                'training_school_id' => $trainingSchool->id,
                'carrier_id' => $carrier->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para asignar esta escuela a ese conductor.');
        }

        try {
            DB::beginTransaction();
            
            // Actualizar el registro de escuela de entrenamiento
            $trainingSchool->user_driver_detail_id = $request->user_driver_detail_id;
            $trainingSchool->date_start = $request->date_start;
            $trainingSchool->date_end = $request->date_end;
            $trainingSchool->school_name = $request->school_name;
            $trainingSchool->city = $request->city;
            $trainingSchool->state = $request->state;

            $trainingSchool->graduated = $request->has('graduated');
            $trainingSchool->subject_to_safety_regulations = $request->has('subject_to_safety_regulations');
            $trainingSchool->performed_safety_functions = $request->has('performed_safety_functions');
            
            // Guardar habilidades de entrenamiento como JSON
            if ($request->has('training_skills')) {
                $trainingSchool->training_skills = json_encode($request->training_skills);
            } else {
                $trainingSchool->training_skills = null;
            }
            
            $trainingSchool->save();

            // Procesar archivos si existen usando Spatie Media Library
            if ($request->filled('training_files')) {
                $filesData = json_decode($request->training_files, true);
                
                if (is_array($filesData)) {
                    foreach ($filesData as $fileData) {
                        if (!empty($fileData['name'])) {
                            try {
                                // Si es un archivo existente, omitirlo ya que no necesitamos procesarlo nuevamente
                                if (isset($fileData['is_existing']) && $fileData['is_existing']) {
                                    continue;
                                }
                                
                                // Ruta del archivo temporal
                                $tempPath = isset($fileData['tempPath']) 
                                    ? $fileData['tempPath'] 
                                    : (isset($fileData['path']) 
                                        ? $fileData['path'] 
                                        : null);
                                
                                if (empty($tempPath)) {
                                    Log::warning('Archivo sin ruta temporal', ['file' => $fileData]);
                                    continue;
                                }
                                
                                // Verificar que el archivo temporal existe
                                if (!Storage::exists($tempPath)) {
                                    // Intentar buscar en la carpeta temp directamente
                                    $tempPath = 'temp/' . basename($tempPath);
                                    
                                    if (!Storage::exists($tempPath)) {
                                        Log::error('Archivo temporal no encontrado (update)', [
                                            'temp_path' => $tempPath,
                                            'original_name' => $fileData['name']
                                        ]);
                                        continue;
                                    }
                                }
                                
                                // Obtener la ruta completa del archivo temporal
                                $fullTempPath = Storage::path($tempPath);
                                
                                // Añadir el archivo a la colección de Spatie Media Library
                                $media = $trainingSchool->addMedia($fullTempPath)
                                    ->usingName($fileData['name'])
                                    ->usingFileName($fileData['name'])
                                    ->withCustomProperties([
                                        'document_type' => 'training_certificate',
                                        'uploaded_by' => Auth::id(),
                                        'description' => 'Training School Document'
                                    ])
                                    ->toMediaCollection('school_certificates');
                                
                                Log::info('Documento guardado correctamente con Spatie Media Library (update)', [
                                    'media_id' => $media->id,
                                    'file_name' => $fileData['name'],
                                    'training_school_id' => $trainingSchool->id
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Error al procesar archivo con Spatie Media Library (update)', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'file' => $fileData
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            
            Log::info('Registro de training school actualizado exitosamente', [
                'training_school_id' => $trainingSchool->id,
                'driver_id' => $trainingSchool->user_driver_detail_id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            session()->flash('success', 'Escuela de entrenamiento actualizada exitosamente.');
            
            return redirect()->route('carrier.training-schools.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar registro de training school', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_school_id' => $trainingSchool->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al actualizar la escuela de entrenamiento. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Eliminar una escuela de entrenamiento.
     * 
     * @param DriverTrainingSchool $trainingSchool
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DriverTrainingSchool $trainingSchool)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que la escuela pertenezca al carrier del usuario autenticado
        if (!$this->validateSchoolOwnership($trainingSchool, $carrier->id)) {
            $this->logUnauthorizedAccess('eliminar training school no autorizada', [
                'training_school_id' => $trainingSchool->id,
                'carrier_id' => $carrier->id,
                'school_carrier_id' => $trainingSchool->userDriverDetail->carrier_id,
            ]);
            
            abort(403, 'No tienes permiso para eliminar esta escuela de entrenamiento.');
        }
        
        try {
            DB::beginTransaction();
            
            $schoolId = $trainingSchool->id;
            $driverId = $trainingSchool->user_driver_detail_id;
            
            // Obtener todos los documentos de Spatie Media Library
            $mediaDocuments = Media::where('model_type', DriverTrainingSchool::class)
                ->where('model_id', $schoolId)
                ->get();
            
            // Eliminar archivos físicos y registros de documentos
            foreach ($mediaDocuments as $media) {
                $diskName = $media->disk;
                $filePath = $media->id . '/' . $media->file_name;
                
                // Eliminar archivo físico
                if (Storage::disk($diskName)->exists($filePath)) {
                    Storage::disk($diskName)->delete($filePath);
                }
                
                // Eliminar directorio del media
                $dirPath = $media->id;
                if (Storage::disk($diskName)->exists($dirPath)) {
                    Storage::disk($diskName)->deleteDirectory($dirPath);
                }
                
                // Eliminar registro de la base de datos
                DB::table('media')->where('id', $media->id)->delete();
            }
            
            // Eliminar el registro de la escuela
            $trainingSchool->delete();
            
            DB::commit();
            
            Log::info('Registro de training school eliminado exitosamente', [
                'training_school_id' => $schoolId,
                'driver_id' => $driverId,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            session()->flash('success', 'Escuela de entrenamiento eliminada exitosamente.');
            
            return redirect()->route('carrier.training-schools.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al eliminar registro de training school', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_school_id' => $trainingSchool->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al eliminar la escuela de entrenamiento. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar todos los documentos de todas las escuelas de entrenamiento del carrier.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function documents(Request $request)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Vista de todos los documentos de training schools accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'driver_filter', 'school_filter', 'date_from', 'date_to']),
            ]);
            
            // Obtener IDs de escuelas del carrier
            $schoolIds = DriverTrainingSchool::query()
                ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                })
                ->pluck('id')
                ->toArray();
            
            // Usar Spatie Media Library
            $query = Media::where('model_type', DriverTrainingSchool::class)
                ->whereIn('model_id', $schoolIds);
            
            // Aplicar filtros
            if ($request->filled('search_term')) {
                $searchTerm = '%' . $request->search_term . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('file_name', 'like', $searchTerm);
                });
            }
            
            if ($request->filled('driver_filter')) {
                $driverId = $request->driver_filter;
                // Obtener IDs de escuelas asociadas a este conductor
                $driverSchoolIds = DriverTrainingSchool::where('user_driver_detail_id', $driverId)
                    ->pluck('id')
                    ->toArray();
                    
                $query->whereIn('model_id', $driverSchoolIds);
            }
            
            if ($request->filled('school_filter')) {
                $schoolId = $request->school_filter;
                $query->where('model_id', $schoolId);
            }
            
            if ($request->filled('date_from')) {
                try {
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_from)->startOfDay();
                    $query->whereDate('created_at', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_from', [
                        'date_from' => $request->date_from,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    session()->flash('warning', 'El formato de fecha "Desde" es inválido. Use MM/DD/YYYY.');
                }
            }
            
            if ($request->filled('date_to')) {
                try {
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_to)->endOfDay();
                    $query->whereDate('created_at', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_to', [
                        'date_to' => $request->date_to,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                    session()->flash('warning', 'El formato de fecha "Hasta" es inválido. Use MM/DD/YYYY.');
                }
            }
            
            // Ordenar resultados
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            $documents = $query->paginate(15);
            
            // Datos para filtros - solo del carrier
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->with('user')
                ->get();
            $schools = DriverTrainingSchool::query()
                ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                })
                ->orderBy('school_name')
                ->get();
            
            return view('carrier.drivers.training-schools.all_documents', compact('documents', 'drivers', 'schools', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar documentos de training schools', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.training-schools.index')
                ->with('error', 'Ocurrió un error al cargar los documentos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar los documentos de una escuela de entrenamiento específica.
     * 
     * @param DriverTrainingSchool $school
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showDocuments(DriverTrainingSchool $school)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que la escuela pertenezca al carrier del usuario autenticado
            if (!$this->validateSchoolOwnership($school, $carrier->id)) {
                $this->logUnauthorizedAccess('ver documentos de training school no autorizada', [
                    'training_school_id' => $school->id,
                    'carrier_id' => $carrier->id,
                    'school_carrier_id' => $school->userDriverDetail->carrier_id,
                ]);
                
                abort(403, 'No tienes permiso para ver los documentos de esta escuela de entrenamiento.');
            }
            
            Log::info('Vista de documentos de training school específica accedida', [
                'training_school_id' => $school->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            $school->load('userDriverDetail.user');
            
            // Obtener documentos asociados usando Spatie Media Library
            $documents = Media::where('model_type', DriverTrainingSchool::class)
                ->where('model_id', $school->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            // Obtener todas las escuelas y conductores del carrier para los filtros
            $schools = DriverTrainingSchool::query()
                ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                })
                ->orderBy('school_name')
                ->get();
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->with('user')
                ->get();
            
            return view('carrier.drivers.training-schools.documents', compact('school', 'schools', 'drivers', 'documents', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar documentos de training school específica', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_school_id' => $school->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.training-schools.index')
                ->with('error', 'Ocurrió un error al cargar los documentos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Eliminar un documento usando eliminación directa de DB para evitar problemas con Spatie Media Library.
     * 
     * @param int $id ID del documento a eliminar
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyDocument($id)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el documento existe en la tabla media
            $media = Media::findOrFail($id);

            // Verificar que el documento pertenece a una escuela de entrenamiento
            if ($media->model_type !== DriverTrainingSchool::class) {
                return redirect()->back()->with('error', 'Tipo de documento inválido');
            }

            $fileName = $media->file_name;
            $schoolId = $media->model_id;
            $school = DriverTrainingSchool::find($schoolId);

            if (!$school) {
                return redirect()->route('carrier.training-schools.index')
                    ->with('error', 'No se encontró la escuela de entrenamiento asociada al documento');
            }
            
            // Verificar que la escuela pertenezca al carrier del usuario autenticado
            if (!$this->validateSchoolOwnership($school, $carrier->id)) {
                $this->logUnauthorizedAccess('eliminar documento de training school no autorizada', [
                    'document_id' => $id,
                    'training_school_id' => $schoolId,
                    'carrier_id' => $carrier->id,
                    'school_carrier_id' => $school->userDriverDetail->carrier_id,
                ]);
                
                abort(403, 'No tienes permiso para eliminar documentos de esta escuela de entrenamiento.');
            }

            // Eliminar el archivo físico si existe
            $diskName = $media->disk;
            $filePath = $media->id . '/' . $media->file_name;
            
            if (Storage::disk($diskName)->exists($filePath)) {
                Storage::disk($diskName)->delete($filePath);
            }
            
            // Eliminar directorio del media si existe
            $dirPath = $media->id;
            if (Storage::disk($diskName)->exists($dirPath)) {
                Storage::disk($diskName)->deleteDirectory($dirPath);
            }
            
            // Eliminar el registro directamente de la base de datos
            $result = DB::table('media')->where('id', $id)->delete();

            if (!$result) {
                return redirect()->back()->with('error', 'No se pudo eliminar el documento');
            }
            
            Log::info('Documento de training school eliminado exitosamente', [
                'document_id' => $id,
                'training_school_id' => $schoolId,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);

            // Determinar la URL de retorno según el origen de la solicitud
            $referer = request()->headers->get('referer');
            
            // Si la URL contiene 'documents', redirigir a la página de documentos
            if (strpos($referer, 'documents') !== false) {
                return redirect()->route('carrier.training-schools.docs.show', $schoolId)
                    ->with('success', "Documento '{$fileName}' eliminado correctamente");
            }
            
            // Si no, redirigir a la página de edición
            return redirect()->route('carrier.training-schools.edit', $schoolId)
                ->with('success', "Documento '{$fileName}' eliminado correctamente");
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento de training school', [
                'document_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()->with('error', 'Error al eliminar documento: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un documento mediante AJAX.
     * Usa eliminación directa de DB para evitar problemas con Spatie Media Library.
     * 
     * @param Request $request La solicitud HTTP
     * @param int $id ID del documento a eliminar
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxDestroyDocument(Request $request, $id)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el documento existe en la tabla media
            $media = Media::findOrFail($id);
            
            // Verificar que el documento pertenece a una escuela de entrenamiento
            if ($media->model_type !== DriverTrainingSchool::class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de documento inválido'
                ], 400);
            }
            
            $fileName = $media->file_name;
            $schoolId = $media->model_id;
            $school = DriverTrainingSchool::find($schoolId);
            
            if (!$school) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la escuela de entrenamiento'
                ], 404);
            }
            
            // Verificar que la escuela pertenezca al carrier del usuario autenticado
            if (!$this->validateSchoolOwnership($school, $carrier->id)) {
                $this->logUnauthorizedAccess('eliminar documento AJAX de training school no autorizada', [
                    'document_id' => $id,
                    'training_school_id' => $schoolId,
                    'carrier_id' => $carrier->id,
                    'school_carrier_id' => $school->userDriverDetail->carrier_id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este documento'
                ], 403);
            }
            
            // Eliminar el archivo físico si existe
            $diskName = $media->disk;
            $filePath = $media->id . '/' . $media->file_name;
            
            if (Storage::disk($diskName)->exists($filePath)) {
                Storage::disk($diskName)->delete($filePath);
            }
            
            // Eliminar directorio del media si existe
            $dirPath = $media->id;
            if (Storage::disk($diskName)->exists($dirPath)) {
                Storage::disk($diskName)->deleteDirectory($dirPath);
            }
            
            // Eliminar el registro directamente de la base de datos
            $result = DB::table('media')->where('id', $id)->delete();
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo eliminar el documento'
                ], 500);
            }
            
            Log::info('Documento de training school eliminado exitosamente vía AJAX', [
                'document_id' => $id,
                'training_school_id' => $schoolId,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Documento '{$fileName}' eliminado correctamente"
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento vía AJAX', [
                'document_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar documento: ' . $e->getMessage()
            ], 500);
        }
    }
}
