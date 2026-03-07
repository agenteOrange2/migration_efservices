<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\Training;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierTrainingsController extends Controller
{
    /**
     * Validar que un entrenamiento pertenezca al carrier del usuario autenticado.
     * Los entrenamientos se validan a través de las asignaciones a conductores del carrier.
     * 
     * @param Training $training
     * @param int $carrierId
     * @return bool
     */
    private function validateTrainingOwnership(Training $training, $carrierId)
    {
        // Verificar si el entrenamiento tiene asignaciones a conductores del carrier
        return $training->driverAssignments()
            ->whereHas('driver', function($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            })
            ->exists();
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
        return $driver->carrier_id === $carrierId;
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
     * Mostrar la lista de entrenamientos del carrier.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Vista de índice de entrenamientos accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'status_filter', 'content_type_filter', 'sort_by']),
            ]);
            
            // Consulta base con eager loading para optimizar rendimiento
            $query = Training::query()
                ->with([
                    'creator:id,name,email',
                    'driverAssignments'
                ]);

            // Aplicar filtro por término de búsqueda (título, descripción)
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
            }

            // Aplicar filtro por estado
            if ($request->filled('status_filter')) {
                $query->where('status', $request->status_filter);
            }

            // Aplicar filtro por tipo de contenido
            if ($request->filled('content_type_filter')) {
                $query->where('content_type', $request->content_type_filter);
            }

            // Aplicar ordenamiento
            $sortBy = $request->get('sort_by', 'created_at_desc');
            switch ($sortBy) {
                case 'title_asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('title', 'desc');
                    break;
                case 'created_at_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'created_at_desc':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Implementar paginación de 10 registros por página
            $trainings = $query->paginate(10)->withQueryString();
            
            return view('carrier.drivers.trainings.index', compact('trainings', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de índice de entrenamientos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al cargar la lista de entrenamientos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar el formulario para crear un nuevo entrenamiento.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Formulario de creación de entrenamiento accedido', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
                
            return view('carrier.drivers.trainings.create', compact('carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.trainings.index')
                ->with('error', 'Ocurrió un error al cargar el formulario. Por favor, intente nuevamente.');
        }
    }

    /**
     * Almacenar un nuevo entrenamiento.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Validar todos los campos requeridos
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content_type' => 'required|string|in:file,video,url',
            'video_url' => 'nullable|url|required_if:content_type,video',
            'url' => 'nullable|url|required_if:content_type,url',
            'status' => 'required|string|in:active,inactive',
            'files_data' => 'nullable|string', // JSON de archivos del componente Livewire
        ]);

        try {
            DB::beginTransaction();
            
            // Crear registro de entrenamiento en base de datos
            $training = new Training();
            $training->title = $validated['title'];
            $training->description = $validated['description'];
            $training->content_type = $validated['content_type'];
            $training->video_url = $validated['video_url'] ?? null;
            $training->url = $validated['url'] ?? null;
            $training->status = $validated['status'];
            $training->created_by = Auth::id();
            $training->save();

            // Procesar archivos si existen
            if ($request->filled('files_data')) {
                $this->processLivewireFiles($training, $request->files_data, 'training_files');
            }

            DB::commit();
            
            Log::info('Entrenamiento creado exitosamente', [
                'training_id' => $training->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.trainings.show', $training->id)
                ->with('success', 'Entrenamiento creado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al crear el entrenamiento. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Mostrar los detalles de un entrenamiento.
     * 
     * @param Training $training
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Training $training)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Cargar datos del entrenamiento con relaciones
            $training->load([
                'creator:id,name,email',
                'driverAssignments.driver.user',
                'media'
            ]);
            
            // Calcular estadísticas de asignaciones
            $assignmentStats = [
                'total' => $training->driverAssignments->count(),
                'completed' => $training->driverAssignments->where('status', 'completed')->count(),
                'in_progress' => $training->driverAssignments->where('status', 'in_progress')->count(),
                'pending' => $training->driverAssignments->where('status', 'pending')->count(),
                'overdue' => $training->driverAssignments->filter(function($assignment) {
                    return $assignment->isOverdue();
                })->count(),
            ];
            
            // Obtener archivos asociados
            $trainingFiles = $training->getMedia('training_files');
            
            Log::info('Vista de detalle de entrenamiento accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'training_id' => $training->id,
                'files_count' => $trainingFiles->count(),
            ]);
            
            return view('carrier.drivers.trainings.show', compact('training', 'carrier', 'assignmentStats', 'trainingFiles'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de detalle de entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'training_id' => $training->id ?? null,
            ]);
            
            return redirect()->route('carrier.trainings.index')
                ->with('error', 'Ocurrió un error al cargar el entrenamiento. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar el formulario para editar un entrenamiento.
     * 
     * @param Training $training
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Training $training)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Cargar datos del entrenamiento con relaciones
            $training->load([
                'creator:id,name,email',
                'media'
            ]);
            
            // Obtener archivos existentes
            $existingFiles = $training->getMedia('training_files');
            
            Log::info('Formulario de edición de entrenamiento accedido', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'training_id' => $training->id,
                'files_count' => $existingFiles->count(),
            ]);
            
            return view('carrier.drivers.trainings.edit', compact('training', 'carrier', 'existingFiles'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición de entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'training_id' => $training->id ?? null,
            ]);
            
            return redirect()->route('carrier.trainings.index')
                ->with('error', 'Ocurrió un error al cargar el formulario de edición. Por favor, intente nuevamente.');
        }
    }

    /**
     * Actualizar un entrenamiento existente.
     * 
     * @param Request $request
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Training $training)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Validar datos modificados
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content_type' => 'required|string|in:file,video,url',
            'video_url' => 'nullable|url|required_if:content_type,video',
            'url' => 'nullable|url|required_if:content_type,url',
            'status' => 'required|string|in:active,inactive',
            'files_data' => 'nullable|string', // JSON de archivos del componente Livewire
        ]);

        try {
            DB::beginTransaction();
            
            // Actualizar registro en base de datos
            $training->title = $validated['title'];
            $training->description = $validated['description'];
            $training->content_type = $validated['content_type'];
            $training->video_url = $validated['video_url'] ?? null;
            $training->url = $validated['url'] ?? null;
            $training->status = $validated['status'];
            $training->save();

            // Procesar nuevos archivos si existen
            // Mantener archivos existentes
            if ($request->filled('files_data')) {
                $this->processLivewireFiles($training, $request->files_data, 'training_files');
            }

            DB::commit();
            
            Log::info('Entrenamiento actualizado exitosamente', [
                'training_id' => $training->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.trainings.show', $training->id)
                ->with('success', 'Entrenamiento actualizado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
                'training_id' => $training->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al actualizar el entrenamiento. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Eliminar un entrenamiento.
     * 
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Training $training)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        try {
            // Verificar si el entrenamiento tiene asignaciones
            $assignmentsCount = $training->driverAssignments()->count();
            
            if ($assignmentsCount > 0) {
                Log::warning('Intento de eliminar entrenamiento con asignaciones', [
                    'training_id' => $training->id,
                    'assignments_count' => $assignmentsCount,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return redirect()->route('carrier.trainings.index')
                    ->with('error', "No se puede eliminar el entrenamiento porque tiene {$assignmentsCount} asignación(es) activa(s).");
            }
            
            // Usar transacciones
            DB::beginTransaction();
            
            $trainingId = $training->id;
            $trainingTitle = $training->title;
            
            // Limpiar archivos físicos
            $files = $training->getMedia('training_files');
            
            foreach ($files as $media) {
                try {
                    $filePath = $media->getPath();
                    
                    // Eliminar archivo físico del disco si existe
                    if (file_exists($filePath)) {
                        Storage::disk($media->disk)->delete($media->id . '/' . $media->file_name);
                        
                        // Eliminar directorio del media si existe y está vacío
                        $dirPath = dirname($filePath);
                        if (is_dir($dirPath) && count(scandir($dirPath)) == 2) { // Solo . y ..
                            Storage::disk($media->disk)->deleteDirectory($media->id);
                        }
                    }
                    
                    Log::info('Archivo eliminado durante eliminación de entrenamiento', [
                        'training_id' => $trainingId,
                        'media_id' => $media->id,
                        'file_name' => $media->file_name,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error al eliminar archivo físico', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'media_id' => $media->id,
                        'training_id' => $trainingId,
                    ]);
                    // Continuar con la eliminación aunque falle un archivo
                }
            }
            
            // Eliminar entrenamiento (cascada elimina archivos en la base de datos)
            $training->delete();
            
            DB::commit();
            
            Log::info('Entrenamiento eliminado exitosamente', [
                'training_id' => $trainingId,
                'training_title' => $trainingTitle,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'files_deleted' => $files->count(),
            ]);
            
            return redirect()->route('carrier.trainings.index')
                ->with('success', 'Entrenamiento eliminado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al eliminar entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_id' => $training->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.trainings.index')
                ->with('error', 'Ocurrió un error al eliminar el entrenamiento. Por favor, intente nuevamente.');
        }
    }

    /**
     * Eliminar documento vía AJAX.
     * 
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyDocument(int $documentId)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Buscar el media por ID
            $media = Media::find($documentId);
            
            if (!$media) {
                Log::warning('Media no encontrado para eliminación', [
                    'media_id' => $documentId,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found.'
                ], 404);
            }
            
            // Obtener el entrenamiento asociado
            $training = Training::find($media->model_id);
            
            if (!$training) {
                Log::warning('Entrenamiento no encontrado para media en eliminación', [
                    'media_id' => $documentId,
                    'model_id' => $media->model_id,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Associated training not found.'
                ], 404);
            }
            
            DB::beginTransaction();
            
            try {
                $fileName = $media->file_name;
                $filePath = $media->getPath();
                
                // Eliminar archivo físico del disco
                if (file_exists($filePath)) {
                    Storage::disk($media->disk)->delete($media->id . '/' . $media->file_name);
                    
                    // Eliminar directorio del media si existe
                    $dirPath = dirname($filePath);
                    if (is_dir($dirPath) && count(scandir($dirPath)) == 2) { // Solo . y ..
                        Storage::disk($media->disk)->deleteDirectory($media->id);
                    }
                }
                
                // Eliminar registro de base de datos
                $media->delete();
                
                DB::commit();
                
                Log::info('Documento de entrenamiento eliminado exitosamente', [
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                    'training_id' => $training->id,
                    'media_id' => $documentId,
                    'file_name' => $fileName,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Document '{$fileName}' deleted successfully."
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento de entrenamiento vía AJAX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'media_id' => $documentId,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the document. Please try again.'
            ], 500);
        }
    }

    /**
     * Previsualizar o descargar documento.
     * 
     * @param int $documentId
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function previewDocument(int $documentId)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Buscar el media por ID
            $media = Media::findOrFail($documentId);
            
            // Obtener el entrenamiento asociado
            $training = Training::find($media->model_id);
            
            if (!$training) {
                Log::warning('Entrenamiento no encontrado para media', [
                    'media_id' => $documentId,
                    'model_id' => $media->model_id,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return redirect()->route('carrier.trainings.index')
                    ->with('error', 'Documento no encontrado.');
            }
            
            Log::info('Documento de entrenamiento previsualizando', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'training_id' => $training->id,
                'media_id' => $documentId,
                'file_name' => $media->file_name,
            ]);
            
            // Retornar archivo con headers apropiados
            $filePath = $media->getPath();
            
            if (!file_exists($filePath)) {
                Log::error('Archivo físico no encontrado', [
                    'media_id' => $documentId,
                    'file_path' => $filePath,
                    'carrier_id' => $carrier->id,
                ]);
                
                return redirect()->route('carrier.trainings.show', $training->id)
                    ->with('error', 'El archivo no se encuentra disponible.');
            }
            
            return response()->file($filePath, [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al previsualizar documento de entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'media_id' => $documentId,
            ]);
            
            return redirect()->route('carrier.trainings.index')
                ->with('error', 'Ocurrió un error al previsualizar el documento.');
        }
    }

    /**
     * Procesar archivos de Livewire (privado).
     * 
     * @param Training $training
     * @param string $filesJson
     * @param string $collection
     * @return int
     */
    private function processLivewireFiles(Training $training, string $filesJson, string $collection): int
    {
        $filesProcessed = 0;
        
        try {
            $filesData = json_decode($filesJson, true);
            
            if (!is_array($filesData)) {
                Log::warning('Datos de archivos no válidos', ['files_json' => $filesJson]);
                return 0;
            }
            
            foreach ($filesData as $fileData) {
                if (empty($fileData['name'])) {
                    Log::warning('Archivo sin nombre', ['file' => $fileData]);
                    continue;
                }
                
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
                        Log::info('Archivo no encontrado en ruta original, intentando alternativas', [
                            'original_path' => $tempPath
                        ]);
                        
                        // Intentar buscar en la carpeta livewire-tmp
                        $altPath1 = 'livewire-tmp/' . basename($tempPath);
                        if (Storage::exists($altPath1)) {
                            $tempPath = $altPath1;
                            Log::info('Archivo encontrado en livewire-tmp', ['path' => $tempPath]);
                        } else {
                            // Intentar buscar en la carpeta temp directamente
                            $altPath2 = 'temp/' . basename($tempPath);
                            if (Storage::exists($altPath2)) {
                                $tempPath = $altPath2;
                                Log::info('Archivo encontrado en temp', ['path' => $tempPath]);
                            } else {
                                Log::error('Archivo temporal no encontrado en ninguna ubicación', [
                                    'original_path' => $fileData['tempPath'] ?? $fileData['path'] ?? 'unknown',
                                    'tried_paths' => [$tempPath, $altPath1, $altPath2],
                                    'original_name' => $fileData['name']
                                ]);
                                continue;
                            }
                        }
                    }
                    
                    // Obtener la ruta completa del archivo temporal
                    $fullTempPath = Storage::path($tempPath);
                    
                    // Validar formato de archivo permitido
                    $allowedMimeTypes = [
                        'application/pdf',
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                        'image/gif',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'video/mp4',
                        'video/quicktime',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                    ];
                    
                    $fileMimeType = mime_content_type($fullTempPath);
                    
                    if (!in_array($fileMimeType, $allowedMimeTypes)) {
                        Log::warning('Formato de archivo no permitido', [
                            'file_name' => $fileData['name'],
                            'mime_type' => $fileMimeType
                        ]);
                        continue;
                    }
                    
                    // Añadir el archivo a la colección de Spatie Media Library
                    $media = $training->addMedia($fullTempPath)
                        ->usingName($fileData['name'])
                        ->usingFileName($fileData['name'])
                        ->withCustomProperties([
                            'document_type' => 'training_file',
                            'uploaded_by' => Auth::id(),
                            'description' => 'Training File Document'
                        ])
                        ->toMediaCollection($collection);
                    
                    $filesProcessed++;
                    
                    Log::info('Documento guardado correctamente con Spatie Media Library', [
                        'media_id' => $media->id,
                        'file_name' => $fileData['name'],
                        'training_id' => $training->id,
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error al procesar archivo con Spatie Media Library', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $fileData,
                        'training_id' => $training->id,
                    ]);
                    
                    // Re-throw exception to trigger transaction rollback
                    throw $e;
                }
            }
            
            return $filesProcessed;
            
        } catch (\Exception $e) {
            Log::error('Error general al procesar archivos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_id' => $training->id,
            ]);
            
            // Re-throw to ensure transaction rollback
            throw $e;
        }
    }
}
