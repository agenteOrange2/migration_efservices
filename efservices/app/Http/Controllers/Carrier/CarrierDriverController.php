<?php

namespace App\Http\Controllers\Carrier;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CarrierDriverController extends Controller
{
    /**
     * Muestra la página principal de gestión de conductores.
     */
    public function index(Request $request)
    {
        // Verifica que el usuario autenticado sea un carrier
        if (!Auth::user()->hasRole('user_carrier')) {
            return redirect()->route('login');
        }
        
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Construir la consulta base con eager loading para optimizar
        $query = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with(['user', 'primaryLicense', 'assignedVehicle', 'vehicles', 'application']);
        
        // Aplicar filtro de búsqueda por nombre o email
        if ($request->filled('search_term')) {
            $search = $request->input('search_term');
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        
        // Aplicar filtro por estado
        if ($request->filled('status_filter') && $request->input('status_filter') !== '') {
            $status = $request->input('status_filter');
            $query->where('status', $status);
        }
        
        // Obtener los drivers con paginación
        $drivers = $query->paginate(15)->withQueryString();
        
        Log::info('Carrier accessed drivers list', [
            'carrier_id' => $carrier->id,
            'user_id' => Auth::id(),
            'filters' => $request->only(['search_term', 'status_filter'])
        ]);
        
        return view('carrier.drivers.index', compact('drivers'));
    }
    
    /**
     * Muestra el formulario para crear un nuevo conductor.
     */
    public function create()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar si se ha alcanzado el límite de conductores
        $maxDrivers = $carrier->membership->max_drivers ?? 1;
        $currentDriversCount = UserDriverDetail::where('carrier_id', $carrier->id)->count();
        
        if ($currentDriversCount >= $maxDrivers) {
            Log::warning('Carrier attempted to create driver over membership limit', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'current_drivers' => $currentDriversCount,
                'max_drivers' => $maxDrivers
            ]);
            
            return redirect()->route('carrier.drivers.index')
                ->with('error', 'Has alcanzado el límite máximo de conductores para tu plan. Actualiza tu membresía para añadir más conductores.');
        }

        Log::info('Carrier accessed driver creation form', [
            'carrier_id' => $carrier->id,
            'user_id' => Auth::id(),
            'current_drivers' => $currentDriversCount,
            'max_drivers' => $maxDrivers
        ]);

        // Pasar el carrier y la bandera de "isIndependent" en false, ya que el conductor 
        // está siendo creado por el carrier, no es un registro independiente
        return view('carrier.drivers.create', [
            'carrier' => $carrier,
            'isIndependent' => false
        ]);
    }
    
    /**
     * Muestra el formulario para la gestión por pasos de un conductor.
     */
    public function edit(UserDriverDetail $driver)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        // Usar comparación loose (==) para evitar problemas de tipo
        if ($driver->carrier_id != $carrier->id) {
            Log::warning('Carrier attempted to access unauthorized driver for editing', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id
            ]);
            
            return redirect()->route('carrier.drivers.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        Log::info('Carrier accessed driver edit form', [
            'carrier_id' => $carrier->id,
            'user_id' => Auth::id(),
            'driver_id' => $driver->id
        ]);
        
        return view('carrier.drivers.edit', [
            'driver' => $driver,
            'carrier' => $carrier,
            'driverId' => $driver->id
        ]);
    }
    
    /**
     * Muestra los detalles de un conductor.
     */
    public function show(UserDriverDetail $driver)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        // Usar comparación loose (==) para evitar problemas de tipo
        if ($driver->carrier_id != $carrier->id) {
            Log::warning('Carrier attempted to access unauthorized driver details', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id
            ]);
            
            return redirect()->route('carrier.drivers.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        // Eager load all relationships needed for the view
        $driver->load([
            'user',
            'licenses.endorsements',
            'medicalQualification',
            'assignedVehicle',
            'activeVehicleAssignment.vehicle',
            'workHistories',
            'trainingSchools',
            'accidents',
            'trafficConvictions'
        ]);
        
        // Load HOS documents (daily_logs and monthly_summaries only)
        $hosDocuments = collect();
        $hosDocuments = $hosDocuments->merge($driver->getMedia('daily_logs'));
        $hosDocuments = $hosDocuments->merge($driver->getMedia('monthly_summaries'));
        $hosDocuments = $hosDocuments->sortByDesc(function ($doc) {
            return $doc->getCustomProperty('document_date') ?? $doc->created_at;
        });
        
        Log::info('Carrier viewed driver details', [
            'carrier_id' => $carrier->id,
            'user_id' => Auth::id(),
            'driver_id' => $driver->id
        ]);
        
        return view('carrier.drivers.show', compact('driver', 'hosDocuments'));
    }
    
    /**
     * Elimina un conductor.
     */
    public function destroy(UserDriverDetail $driver)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        // Usar comparación loose (==) para evitar problemas de tipo
        if ($driver->carrier_id != $carrier->id) {
            Log::warning('Carrier attempted to delete unauthorized driver', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id
            ]);
            
            return redirect()->route('carrier.drivers.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        try {
            $user = $driver->user;
            $driverId = $driver->id;
            $driverName = $user ? $user->name : 'Unknown';
            
            if ($user) {
                // Eliminar foto de perfil
                $driver->clearMediaCollection('profile_photo_driver');
                
                // Eliminar todas las colecciones de medios relacionadas con licencias
                $driver->licenses()->get()->each(function($license) {
                    $license->clearMediaCollection('license_front');
                    $license->clearMediaCollection('license_back');
                });
                
                // Eliminar colección de tarjeta médica
                if ($driver->medicalQualification) {
                    $driver->medicalQualification->clearMediaCollection('medical_card');
                }
                
                // Eliminar certificados de escuelas de entrenamiento
                $driver->trainingSchools()->get()->each(function($school) {
                    $school->clearMediaCollection('school_certificates');
                });
                
                // Eliminar firma de certificación
                if ($driver->certification) {
                    $driver->certification->clearMediaCollection('signature');
                }
                
                // Eliminar el usuario (que también eliminará el UserDriverDetail por cascada)
                $user->delete();
            }
            
            Log::info('Driver deleted successfully', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'driver_id' => $driverId,
                'driver_name' => $driverName
            ]);
            
            return redirect()->route('carrier.drivers.index')
                ->with('success', 'Conductor eliminado exitosamente.');
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar conductor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'driver_id' => $driver->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id()
            ]);
            
            return redirect()->route('carrier.drivers.index')
                ->with('error', 'Error al eliminar conductor: ' . $e->getMessage());
        }
    }
}