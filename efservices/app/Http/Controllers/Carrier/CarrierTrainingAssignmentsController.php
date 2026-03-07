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

class CarrierTrainingAssignmentsController extends Controller
{
    /**
     * Mostrar lista de entrenamientos activos disponibles para asignar.
     * 
     * @return \Illuminate\View\View
     */
    public function assignSelect()
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Vista de selección de entrenamientos para asignación accedida', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Obtener solo entrenamientos activos
            $trainings = Training::where('status', 'active')
                ->with(['creator:id,name,email'])
                ->orderBy('title', 'asc')
                ->get();
            
            return view('carrier.drivers.trainings.assign-select', compact('trainings', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar vista de selección de entrenamientos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.trainings.index')
                ->with('error', 'Ocurrió un error al cargar los entrenamientos. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar formulario de asignación para un entrenamiento específico.
     * 
     * @param Training $training
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showAssignForm(Training $training)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Validar que el entrenamiento esté activo
            if ($training->status !== 'active') {
                Log::warning('Intento de asignar entrenamiento inactivo', [
                    'training_id' => $training->id,
                    'training_status' => $training->status,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return redirect()->route('carrier.trainings.assign.select')
                    ->with('error', 'No se puede asignar un entrenamiento inactivo.');
            }
            
            Log::info('Formulario de asignación de entrenamiento accedido', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'training_id' => $training->id,
            ]);
            
            // Cargar información del entrenamiento
            $training->load(['creator:id,name,email']);
            
            // Obtener conductores activos del carrier
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->where('status', UserDriverDetail::STATUS_ACTIVE)
                ->with(['user:id,name,email'])
                ->orderBy('id', 'asc')
                ->get();
            
            return view('carrier.drivers.trainings.assign-form', compact('training', 'carrier', 'drivers'));
            
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de asignación de entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'training_id' => $training->id ?? null,
            ]);
            
            return redirect()->route('carrier.trainings.assign.select')
                ->with('error', 'Ocurrió un error al cargar el formulario de asignación. Por favor, intente nuevamente.');
        }
    }

    /**
     * Asignar entrenamiento a conductores seleccionados.
     * 
     * @param Request $request
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assign(Request $request, Training $training)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Validar datos del formulario
        $validated = $request->validate([
            'driver_ids' => 'required|array|min:1',
            'driver_ids.*' => 'required|exists:user_driver_details,id',
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000',
        ], [
            'driver_ids.required' => 'Debe seleccionar al menos un conductor.',
            'driver_ids.min' => 'Debe seleccionar al menos un conductor.',
            'driver_ids.*.exists' => 'Uno o más conductores seleccionados no son válidos.',
            'due_date.required' => 'La fecha de vencimiento es requerida.',
            'due_date.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ]);

        try {
            // Validar que todos los conductores pertenezcan al carrier
            $driverIds = $validated['driver_ids'];
            $validDrivers = UserDriverDetail::whereIn('id', $driverIds)
                ->where('carrier_id', $carrier->id)
                ->pluck('id')
                ->toArray();
            
            if (count($validDrivers) !== count($driverIds)) {
                $this->logUnauthorizedAccess('Intento de asignar entrenamiento a conductores fuera del carrier', [
                    'training_id' => $training->id,
                    'carrier_id' => $carrier->id,
                    'requested_driver_ids' => $driverIds,
                    'valid_driver_ids' => $validDrivers,
                ]);
                
                return redirect()->back()
                    ->with('error', 'Uno o más conductores seleccionados no pertenecen a su organización.')
                    ->withInput();
            }
            
            // Validar que el entrenamiento esté activo
            if ($training->status !== 'active') {
                Log::warning('Intento de asignar entrenamiento inactivo', [
                    'training_id' => $training->id,
                    'training_status' => $training->status,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                return redirect()->route('carrier.trainings.assign.select')
                    ->with('error', 'No se puede asignar un entrenamiento inactivo.');
            }
            
            DB::beginTransaction();
            
            $assignedCount = 0;
            $alreadyAssignedCount = 0;
            $assignedDriverNames = [];
            $skippedDriverNames = [];
            
            foreach ($validDrivers as $driverId) {
                // Verificar si ya existe una asignación
                $exists = DriverTraining::where('user_driver_detail_id', $driverId)
                    ->where('training_id', $training->id)
                    ->exists();
                
                $driver = UserDriverDetail::with('user')->find($driverId);
                
                if (!$exists) {
                    // Crear nueva asignación
                    DriverTraining::create([
                        'user_driver_detail_id' => $driverId,
                        'training_id' => $training->id,
                        'assigned_date' => now(),
                        'due_date' => $validated['due_date'],
                        'status' => 'pending',
                        'completion_notes' => $validated['notes'] ?? null,
                        'assigned_by' => Auth::id(),
                    ]);
                    
                    $assignedCount++;
                    $assignedDriverNames[] = $driver->user->name ?? "Driver #{$driverId}";
                    
                    Log::info('Entrenamiento asignado a conductor', [
                        'training_id' => $training->id,
                        'driver_id' => $driverId,
                        'carrier_id' => $carrier->id,
                        'user_id' => Auth::id(),
                        'due_date' => $validated['due_date'],
                    ]);
                } else {
                    $alreadyAssignedCount++;
                    $skippedDriverNames[] = $driver->user->name ?? "Driver #{$driverId}";
                    
                    Log::info('Conductor ya tiene asignado este entrenamiento', [
                        'training_id' => $training->id,
                        'driver_id' => $driverId,
                        'carrier_id' => $carrier->id,
                    ]);
                }
            }
            
            DB::commit();
            
            // Construir mensaje de éxito
            $message = '';
            if ($assignedCount > 0) {
                $message = "Entrenamiento asignado exitosamente a {$assignedCount} conductor(es).";
            }
            if ($alreadyAssignedCount > 0) {
                $message .= " {$alreadyAssignedCount} conductor(es) ya tenían este entrenamiento asignado.";
            }
            
            Log::info('Proceso de asignación de entrenamiento completado', [
                'training_id' => $training->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'assigned_count' => $assignedCount,
                'already_assigned_count' => $alreadyAssignedCount,
            ]);
            
            return redirect()->route('carrier.trainings.show', $training->id)
                ->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al asignar entrenamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'training_id' => $training->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'data' => $validated ?? [],
            ]);
            
            return redirect()->back()
                ->with('error', 'Ocurrió un error al asignar el entrenamiento. Por favor, intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Obtener conductores por carrier (API endpoint para AJAX).
     * 
     * @param int $carrierId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDrivers($carrierId)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Validar que el carrier solicitado sea el del usuario autenticado
            if ($carrier->id != $carrierId) {
                $this->logUnauthorizedAccess('Intento de acceder a conductores de otro carrier', [
                    'requested_carrier_id' => $carrierId,
                    'user_carrier_id' => $carrier->id,
                ]);
                
                return response()->json([
                    'error' => 'No autorizado para acceder a conductores de otro carrier.'
                ], 403);
            }
            
            // Obtener conductores activos del carrier
            $drivers = UserDriverDetail::where('carrier_id', $carrierId)
                ->where('status', UserDriverDetail::STATUS_ACTIVE)
                ->with(['user:id,name,email'])
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($driver) {
                    return [
                        'id' => $driver->id,
                        'name' => $driver->user->name ?? 'Sin nombre',
                        'email' => $driver->user->email ?? '',
                        'full_name' => $driver->full_name ?? $driver->user->name ?? 'Sin nombre',
                    ];
                });
            
            Log::info('Conductores obtenidos vía API', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'drivers_count' => $drivers->count(),
            ]);
            
            return response()->json($drivers);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener conductores vía API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'error' => 'Ocurrió un error al cargar los conductores.'
            ], 500);
        }
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
}
