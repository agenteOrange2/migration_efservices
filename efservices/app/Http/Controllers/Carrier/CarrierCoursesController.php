<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverCourse;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierCoursesController extends Controller
{
    /**
     * Validar que un curso pertenezca al carrier del usuario autenticado.
     * 
     * @param DriverCourse $course
     * @param int $carrierId
     * @return bool
     */
    private function validateCourseOwnership(DriverCourse $course, $carrierId)
    {
        return (int) $course->driverDetail->carrier_id === (int) $carrierId;
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
     * Mostrar la lista de cursos de los conductores del carrier.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Vista de índice de cursos accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'driver_filter', 'date_from', 'date_to', 'status_filter']),
            ]);
            
            // Consulta base con eager loading para optimizar rendimiento
            // Prevenir problema N+1 cargando todas las relaciones necesarias
            $query = DriverCourse::query()
                ->with([
                    'driverDetail:id,carrier_id,user_id,middle_name,last_name', // Incluir middle_name y last_name
                    'driverDetail.user:id,name,email'     // Solo cargar campos necesarios del usuario
                ])
                ->whereHas('driverDetail', function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                });

            // Aplicar filtro por término de búsqueda (organización, experiencia, ciudad, estado)
            // Asegurar que filtros se aplican a nivel de base de datos (no en PHP)
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('organization_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('experience', 'like', '%' . $searchTerm . '%')
                      ->orWhere('city', 'like', '%' . $searchTerm . '%')
                      ->orWhere('state', 'like', '%' . $searchTerm . '%');
                });
            }

            // Aplicar filtro por conductor específico (usa índice compuesto)
            if ($request->filled('driver_filter')) {
                $query->where('user_driver_detail_id', $request->driver_filter);
            }

            // Aplicar filtro por rango de fechas con formato MM/DD/YYYY
            // Usa índice en certification_date para optimizar
            if ($request->filled('date_from')) {
                try {
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_from)->startOfDay();
                    $query->whereDate('certification_date', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_from', [
                        'date_from' => $request->date_from,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($request->filled('date_to')) {
                try {
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_to)->endOfDay();
                    $query->whereDate('certification_date', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Formato de fecha inválido en date_to', [
                        'date_to' => $request->date_to,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Aplicar filtro por estado (usa índice en status)
            if ($request->filled('status_filter')) {
                $query->where('status', $request->status_filter);
            }

            // Ordenar por fecha de certificación descendente por defecto
            // Usa índice en certification_date para optimizar el ordenamiento
            $query->orderBy('certification_date', 'desc');

            // Implementar paginación de 10 registros por página
            $courses = $query->paginate(10)->withQueryString();
            
            // Obtener lista de conductores para el filtro (con eager loading optimizado)
            // Usar with() para cargar relaciones y prevenir problema N+1
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->with('user:id,name,email') // Solo cargar campos necesarios
                ->select('id', 'carrier_id', 'user_id', 'middle_name', 'last_name') // Incluir middle_name y last_name
                ->get();

            return view('carrier.drivers.courses.index', compact('courses', 'drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de índice de cursos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al cargar la lista de cursos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar el formulario para crear un nuevo curso.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Cargar solo conductores del carrier
            // Usar with() para cargar relaciones y prevenir problema N+1
            $drivers = UserDriverDetail::with('user:id,name,email') // Solo cargar campos necesarios
                ->where('carrier_id', $carrier->id)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->select('id', 'carrier_id', 'user_id', 'middle_name', 'last_name') // Incluir middle_name y last_name
                ->get();
            
            Log::info('Formulario de creación de curso accedido', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'available_drivers_count' => $drivers->count(),
            ]);
                
            return view('carrier.drivers.courses.create', compact('drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de curso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.courses.index')
                ->with('error', 'Ocurrió un error al cargar el formulario. Por favor, intente nuevamente.');
        }
    }

    /**
     * Almacenar un nuevo curso.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Validar todos los campos requeridos
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'organization_name' => 'required|string|max:255',
            'other_organization_name' => 'nullable|string|max:255|required_if:organization_name,Other',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'certification_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'experience' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Active,Inactive',
            'course_files' => 'nullable|string', // JSON de archivos del componente Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if (!$this->validateDriverOwnership($driver, $carrier->id)) {
            $this->logUnauthorizedAccess('crear curso para conductor no autorizado', [
                'carrier_id' => $carrier->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
            ]);
            
            return redirect()->back()
                ->with('error', 'No tienes permiso para crear cursos para este conductor.')
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Manejar campo "Other" para organización
            $organizationName = $validated['organization_name'];
            if ($organizationName === 'Other' && !empty($validated['other_organization_name'])) {
                $organizationName = $validated['other_organization_name'];
            }
            
            // Crear registro de curso en base de datos
            $course = new DriverCourse();
            $course->user_driver_detail_id = $validated['user_driver_detail_id'];
            $course->organization_name = $organizationName;
            $course->city = $validated['city'] ?? null;
            $course->state = $validated['state'] ?? null;
            $course->certification_date = $validated['certification_date'] ?? null;
            $course->expiration_date = $validated['expiration_date'] ?? null;
            $course->experience = $validated['experience'] ?? null;
            $course->status = $validated['status'] ?? 'Active';
            $course->save();

            // Procesar archivos si existen
            if ($request->filled('course_files')) {
                $this->processLivewireFiles($course, $request->course_files, 'course_certificates');
            }

            DB::commit();
            
            Log::info('Registro de curso creado exitosamente', [
                'course_id' => $course->id,
                'driver_id' => $course->user_driver_detail_id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Redirigir a página de edición después de crear curso
            return redirect()->route('carrier.courses.edit', $course->id)
                ->with('success', 'Curso añadido exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear registro de curso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al crear el curso. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Mostrar el formulario para editar un curso.
     * 
     * @param DriverCourse $course
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(DriverCourse $course)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el curso pertenece a un conductor del carrier
            if (!$this->validateCourseOwnership($course, $carrier->id)) {
                $this->logUnauthorizedAccess('editar curso no autorizado', [
                    'carrier_id' => $carrier->id,
                    'course_id' => $course->id,
                    'driver_id' => $course->user_driver_detail_id,
                    'driver_carrier_id' => $course->driverDetail->carrier_id,
                ]);
                
                return redirect()->route('carrier.courses.index')
                    ->with('error', 'No tienes permiso para editar este curso.');
            }
            
            // Cargar datos actuales del curso con relaciones
            // Usar with() para cargar relaciones y prevenir problema N+1
            $course->load([
                'driverDetail:id,carrier_id,user_id,middle_name,last_name', // Incluir middle_name y last_name
                'driverDetail.user:id,name,email',
                'media' // Cargar certificados existentes
            ]);
            
            // Cargar lista de conductores del carrier
            // Usar with() para cargar relaciones y prevenir problema N+1
            $drivers = UserDriverDetail::with('user:id,name,email') // Solo cargar campos necesarios
                ->where('carrier_id', $carrier->id)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->select('id', 'carrier_id', 'user_id', 'middle_name', 'last_name') // Incluir middle_name y last_name
                ->get();
            
            // Obtener certificados existentes
            $existingCertificates = $course->getMedia('course_certificates');
            
            Log::info('Formulario de edición de curso accedido', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'course_id' => $course->id,
                'driver_id' => $course->user_driver_detail_id,
                'certificates_count' => $existingCertificates->count(),
            ]);
            
            return view('carrier.drivers.courses.edit', compact('course', 'drivers', 'carrier', 'existingCertificates'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición de curso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'course_id' => $course->id ?? null,
            ]);
            
            return redirect()->route('carrier.courses.index')
                ->with('error', 'Ocurrió un error al cargar el formulario de edición. Por favor, intente nuevamente.');
        }
    }

    /**
     * Actualizar un curso existente.
     * 
     * @param Request $request
     * @param DriverCourse $course
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, DriverCourse $course)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el curso pertenece a un conductor del carrier
        if (!$this->validateCourseOwnership($course, $carrier->id)) {
            $this->logUnauthorizedAccess('actualizar curso no autorizado', [
                'carrier_id' => $carrier->id,
                'course_id' => $course->id,
                'driver_id' => $course->user_driver_detail_id,
                'driver_carrier_id' => $course->driverDetail->carrier_id,
            ]);
            
            return redirect()->route('carrier.courses.index')
                ->with('error', 'No tienes permiso para actualizar este curso.');
        }
        
        // Validar datos modificados
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'organization_name' => 'required|string|max:255',
            'other_organization_name' => 'nullable|string|max:255|required_if:organization_name,Other',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'certification_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'experience' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Active,Inactive',
            'course_files' => 'nullable|string', // JSON de archivos del componente Livewire
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if (!$this->validateDriverOwnership($driver, $carrier->id)) {
            $this->logUnauthorizedAccess('actualizar curso con conductor no autorizado', [
                'carrier_id' => $carrier->id,
                'course_id' => $course->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
            ]);
            
            return redirect()->back()
                ->with('error', 'No tienes permiso para asignar este curso a ese conductor.')
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Manejar campo "Other" para organización
            $organizationName = $validated['organization_name'];
            if ($organizationName === 'Other' && !empty($validated['other_organization_name'])) {
                $organizationName = $validated['other_organization_name'];
            }
            
            // Actualizar registro en base de datos
            $course->user_driver_detail_id = $validated['user_driver_detail_id'];
            $course->organization_name = $organizationName;
            $course->city = $validated['city'] ?? null;
            $course->state = $validated['state'] ?? null;
            $course->certification_date = $validated['certification_date'] ?? null;
            $course->expiration_date = $validated['expiration_date'] ?? null;
            $course->experience = $validated['experience'] ?? null;
            $course->status = $validated['status'] ?? 'Active';
            $course->save();

            // Procesar nuevos certificados si existen
            // Mantener certificados existentes
            if ($request->filled('course_files')) {
                $this->processLivewireFiles($course, $request->course_files, 'course_certificates');
            }

            DB::commit();
            
            Log::info('Registro de curso actualizado exitosamente', [
                'course_id' => $course->id,
                'driver_id' => $course->user_driver_detail_id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Mostrar mensaje de éxito después de actualizar
            return redirect()->route('carrier.courses.edit', $course->id)
                ->with('success', 'Curso actualizado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar registro de curso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
                'course_id' => $course->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Preservar datos en caso de error de validación
            return redirect()->back()
                ->with('error', 'Ocurrió un error al actualizar el curso. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Eliminar un curso.
     * 
     * @param DriverCourse $course
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DriverCourse $course)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar propiedad del curso
        if (!$this->validateCourseOwnership($course, $carrier->id)) {
            $this->logUnauthorizedAccess('eliminar curso no autorizado', [
                'carrier_id' => $carrier->id,
                'course_id' => $course->id,
                'driver_id' => $course->user_driver_detail_id,
                'driver_carrier_id' => $course->driverDetail->carrier_id,
            ]);
            
            // Mostrar mensaje de error en eliminación fallida
            return redirect()->route('carrier.courses.index')
                ->with('error', 'No tienes permiso para eliminar este curso.');
        }
        
        try {
            // Usar transacciones
            DB::beginTransaction();
            
            $courseId = $course->id;
            $driverId = $course->user_driver_detail_id;
            $organizationName = $course->organization_name;
            
            // Limpiar archivos físicos
            // Obtener todos los certificados antes de eliminar el curso
            $certificates = $course->getMedia('course_certificates');
            
            foreach ($certificates as $media) {
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
                    
                    Log::info('Certificado eliminado durante eliminación de curso', [
                        'course_id' => $courseId,
                        'media_id' => $media->id,
                        'file_name' => $media->file_name,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error al eliminar archivo físico de certificado', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'media_id' => $media->id,
                        'course_id' => $courseId,
                    ]);
                    // Continuar con la eliminación aunque falle un archivo
                }
            }
            
            // Eliminar curso (cascada elimina certificados en la base de datos)
            $course->delete();
            
            DB::commit();
            
            Log::info('Curso eliminado exitosamente', [
                'course_id' => $courseId,
                'driver_id' => $driverId,
                'organization_name' => $organizationName,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'certificates_deleted' => $certificates->count(),
            ]);
            
            // Mostrar mensaje de éxito en eliminación exitosa
            return redirect()->route('carrier.courses.index')
                ->with('success', 'Curso eliminado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al eliminar curso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'course_id' => $course->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Mostrar mensaje de error en eliminación fallida
            return redirect()->route('carrier.courses.index')
                ->with('error', 'Ocurrió un error al eliminar el curso. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar página de documentos de un curso.
     * 
     * @param DriverCourse $course
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function getFiles(DriverCourse $course)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verificar que el curso pertenece a un conductor del carrier
            if (!$this->validateCourseOwnership($course, $carrier->id)) {
                $this->logUnauthorizedAccess('ver documentos de curso no autorizado', [
                    'carrier_id' => $carrier->id,
                    'course_id' => $course->id,
                    'driver_id' => $course->user_driver_detail_id,
                    'driver_carrier_id' => $course->driverDetail->carrier_id,
                ]);
                
                return redirect()->route('carrier.courses.index')
                    ->with('error', 'No tienes permiso para ver los documentos de este curso.');
            }
            
            // Cargar datos del curso con relaciones
            // Usar with() para cargar relaciones y prevenir problema N+1
            $course->load([
                'driverDetail:id,carrier_id,user_id,middle_name,last_name', // Incluir middle_name y last_name
                'driverDetail.user:id,name,email'
            ]);
            
            // Obtener todos los certificados del curso
            $certificates = $course->getMedia('course_certificates');
            
            Log::info('Página de documentos de curso accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'course_id' => $course->id,
                'driver_id' => $course->user_driver_detail_id,
                'certificates_count' => $certificates->count(),
            ]);
            
            return view('carrier.drivers.courses.documents', compact('course', 'certificates', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar página de documentos de curso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'course_id' => $course->id ?? null,
            ]);
            
            return redirect()->route('carrier.courses.index')
                ->with('error', 'Ocurrió un error al cargar los documentos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Eliminar documento vía AJAX.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxDestroyDocument(int $id)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Buscar el media por ID
            $media = Media::find($id);
            
            if (!$media) {
                Log::warning('Media no encontrado para eliminación', [
                    'media_id' => $id,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.'
                ], 404);
            }
            
            // Obtener el curso asociado
            $course = DriverCourse::find($media->model_id);
            
            if (!$course) {
                Log::warning('Curso no encontrado para media en eliminación', [
                    'media_id' => $id,
                    'model_id' => $media->model_id,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Curso asociado no encontrado.'
                ], 404);
            }
            
            // Verificar propiedad del curso
            if (!$this->validateCourseOwnership($course, $carrier->id)) {
                $this->logUnauthorizedAccess('eliminar documento de curso no autorizado', [
                    'carrier_id' => $carrier->id,
                    'course_id' => $course->id,
                    'media_id' => $id,
                    'driver_id' => $course->user_driver_detail_id,
                    'driver_carrier_id' => $course->driverDetail->carrier_id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este documento.'
                ], 403);
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
                // Mantener intacto el registro del curso
                $media->delete();
                
                DB::commit();
                
                Log::info('Documento de curso eliminado exitosamente', [
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                    'course_id' => $course->id,
                    'media_id' => $id,
                    'file_name' => $fileName,
                ]);
                
                // Retornar respuesta JSON
                return response()->json([
                    'success' => true,
                    'message' => 'Certificado eliminado exitosamente.'
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento de curso vía AJAX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'media_id' => $id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al eliminar el certificado. Por favor, intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Previsualizar documento.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function previewDocument(int $id)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Buscar el media por ID
            $media = Media::findOrFail($id);
            
            // Obtener el curso asociado
            $course = DriverCourse::find($media->model_id);
            
            if (!$course) {
                Log::warning('Curso no encontrado para media', [
                    'media_id' => $id,
                    'model_id' => $media->model_id,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return redirect()->route('carrier.courses.index')
                    ->with('error', 'Documento no encontrado.');
            }
            
            // Verificar propiedad del curso
            if (!$this->validateCourseOwnership($course, $carrier->id)) {
                $this->logUnauthorizedAccess('previsualizar documento de curso no autorizado', [
                    'carrier_id' => $carrier->id,
                    'course_id' => $course->id,
                    'media_id' => $id,
                    'driver_id' => $course->user_driver_detail_id,
                    'driver_carrier_id' => $course->driverDetail->carrier_id,
                ]);
                
                return redirect()->route('carrier.courses.index')
                    ->with('error', 'No tienes permiso para ver este documento.');
            }
            
            Log::info('Documento de curso previsualizando', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'course_id' => $course->id,
                'media_id' => $id,
                'file_name' => $media->file_name,
            ]);
            
            // Retornar archivo con headers apropiados
            $filePath = $media->getPath();
            
            if (!file_exists($filePath)) {
                Log::error('Archivo físico no encontrado', [
                    'media_id' => $id,
                    'file_path' => $filePath,
                    'carrier_id' => $carrier->id,
                ]);
                
                return redirect()->route('carrier.courses.documents', $course->id)
                    ->with('error', 'El archivo no se encuentra disponible.');
            }
            
            return response()->file($filePath, [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al previsualizar documento de curso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'media_id' => $id,
            ]);
            
            return redirect()->route('carrier.courses.index')
                ->with('error', 'Ocurrió un error al previsualizar el documento.');
        }
    }

    /**
     * Procesar archivos de Livewire (privado).
     * 
     * @param DriverCourse $course
     * @param string $filesJson
     * @param string $collection
     * @return int
     */
    private function processLivewireFiles(DriverCourse $course, string $filesJson, string $collection): int
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
                    
                    // Validar formato de archivo permitido (PDF, imágenes, documentos)
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
                    // Almacenar archivos en ruta driver/{driver_id}/courses/{course_id}/
                    $media = $course->addMedia($fullTempPath)
                        ->usingName($fileData['name'])
                        ->usingFileName($fileData['name'])
                        ->withCustomProperties([
                            'document_type' => 'course_certificate',
                            'uploaded_by' => Auth::id(),
                            'description' => 'Course Certificate Document'
                        ])
                        ->toMediaCollection($collection);
                    
                    $filesProcessed++;
                    
                    Log::info('Documento guardado correctamente con Spatie Media Library', [
                        'media_id' => $media->id,
                        'file_name' => $fileData['name'],
                        'course_id' => $course->id,
                        'driver_id' => $course->user_driver_detail_id,
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error al procesar archivo con Spatie Media Library', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $fileData,
                        'course_id' => $course->id,
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
                'course_id' => $course->id,
            ]);
            
            // Re-throw to ensure transaction rollback
            throw $e;
        }
    }
}
