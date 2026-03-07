<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Carrier;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UserDriverDetail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Mail\DriverConfirmationMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Http\Requests\Auth\RegisterDriverRequest;
use App\Notifications\Admin\Driver\NewDriverRegisteredNotification;

class DriverRegistrationController extends Controller
{
    /**
     * Redirige a usuarios autenticados a su dashboard correspondiente.
     * Retorna null si el usuario no está autenticado (puede continuar al registro).
     */
    private function redirectIfAuthenticated()
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if ($user->hasRole('superadmin')) {
            return redirect()->route('admin.dashboard')
                ->with('warning', 'You are already logged in. Please log out first to register as a driver.');
        }

        if ($user->hasRole('user_carrier')) {
            return redirect()->route('carrier.dashboard')
                ->with('warning', 'You are already logged in as a carrier. Please log out first to register as a driver.');
        }

        if ($user->hasRole('user_driver')) {
            $driverDetails = $user->driverDetails;
            
            if ($driverDetails && $driverDetails->application_completed) {
                return redirect()->route('driver.dashboard')
                    ->with('warning', 'You are already registered as a driver.');
            }

            // Driver en proceso de registro, permitir continuar
            return null;
        }

        return redirect()->route('login')
            ->with('warning', 'Please log out first to register as a driver.');
    }

    /**
     * Muestra el formulario de registro para drivers que llegan con referencia
     */
    public function showRegistrationForm(Request $request, Carrier $carrier)
    {
        // Si el usuario ya está logueado (carrier, admin, etc.), redirigir a su dashboard
        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }
        
        $token = $request->route('token') ?? $request->query('token');
        $isIndependent = empty($token);
        
        // Cargar los medios del carrier (logo)
        $carrier->load('media');
        
        // Verificar si el carrier está activo
        if ($carrier->status !== Carrier::STATUS_ACTIVE) {
            return view('auth.user_driver.carrier_inactive_error', [
                'carrier' => $carrier
            ]);
        }
        
        // Solo validamos el token si no es registro independiente
        if (!$isIndependent && !$this->validateTokenAndCarrier($carrier, $token)) {
            // Si el token es válido pero el carrier está inactivo o pendiente,
            // mostrar la vista específica en lugar de un error genérico
            if ($carrier->referrer_token === $token) {
                return view('auth.user_driver.carrier_inactive_error', [
                    'carrier' => $carrier
                ]);
            }
            
            return redirect()->route('driver.register.error');
        }
    
        return view('auth.user_driver.register', [
            'carrier' => $carrier,
            'isIndependent' => $isIndependent,
            'token' => $token
        ]);
    }

    /**
     * Muestra el formulario de selección de carrier para drivers independientes
     */
    public function showIndependentCarrierSelection()
    {
        // Si el usuario ya está logueado (carrier, admin, etc.), redirigir a su dashboard
        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        try {
            // Log para depuración
            Log::info('showIndependentCarrierSelection called');
            
            // Obtener carriers activos con información adicional
            $carriers = Carrier::where('status', Carrier::STATUS_ACTIVE)
                ->with(['membership', 'media'])
                ->get()
                ->map(function($carrier) {
                    // Agregar recuento de conductores y máximo permitido
                    $driver_count = $carrier->userDrivers()->count();
                    $max_drivers = $carrier->membership->max_drivers ?? 1;
                    
                    // Agregar estos datos al carrier
                    $carrier->driver_count = $driver_count;
                    $carrier->max_drivers = $max_drivers;
                    $carrier->is_full = $driver_count >= $max_drivers;
                    
                    return $carrier;
                });
            
            // Obtener estados únicos para el filtro
            $states = Carrier::where('status', Carrier::STATUS_ACTIVE)
                ->distinct()
                ->orderBy('state')
                ->pluck('state')
                ->filter()
                ->values();
            
            return view('auth.user_driver.select_carrier_registration', [
                'carriers' => $carriers,
                'states' => $states,
                'isRegistration' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Error en showIndependentCarrierSelection: ' . $e->getMessage());
            return redirect()->route('driver.register.error')
                ->with('error', 'Error loading carriers. Please try again later.');
        }
    }
    

    /**
     * Muestra el formulario de registro para drivers independientes (sin referencia)
     * pero ya con un carrier seleccionado
     */
    public function showIndependentRegistrationForm($carrier_slug)
    {
        // Si el usuario ya está logueado (carrier, admin, etc.), redirigir a su dashboard
        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        try {
            // Si es un driver en proceso de registro, permitir retomar
            if (Auth::check()) {
                $user = Auth::user();
                $driverDetails = $user->driverDetails;
                
                if ($driverDetails && !$driverDetails->application_completed) {
                    Log::info('Usuario autenticado retomando registro', [
                        'user_id' => $user->id,
                        'driver_id' => $driverDetails->id,
                        'current_step' => $driverDetails->current_step ?? 1
                    ]);
                    
                    return view('auth.user_driver.resume_registration', [
                        'carrier' => Carrier::where('slug', $carrier_slug)->first(),
                        'driverId' => $driverDetails->id,
                        'currentStep' => $driverDetails->current_step ?? 1
                    ]);
                }
            }
            
            // Buscar el carrier por slug y cargar medios
            $carrier = Carrier::where('slug', $carrier_slug)->with('media')->firstOrFail();
            
            // Verificar si el carrier está activo
            if ($carrier->status !== Carrier::STATUS_ACTIVE) {
                return view('auth.user_driver.carrier_inactive_error', [
                    'carrier' => $carrier
                ]);
            }
            
            // Verificar si el carrier ha alcanzado su límite de conductores
            $driver_count = $carrier->userDrivers()->count();
            $max_drivers = $carrier->membership->max_drivers ?? 1;
            
            if ($driver_count >= $max_drivers) {
                return view('auth.user_driver.carrier_limit_error', [
                    'carrier' => $carrier,
                    'driver_count' => $driver_count,
                    'max_drivers' => $max_drivers
                ]);
            }
            
            // Renderizar vista de registro
            return view('auth.user_driver.register', [
                'isIndependent' => true,
                'carrier' => $carrier,
                'token' => null
            ]);
        } catch (\Exception $e) {
            Log::error('Error en showIndependentRegistrationForm', [
                'carrier_slug' => $carrier_slug,
                'error_message' => $e->getMessage()
            ]);
            
            return redirect()->route('driver.register.error')
                ->with('error', 'No se pudo encontrar el carrier seleccionado.');
        }
    }

    /**
     * Procesa el registro de conductores que llegan con referencia
     */
    public function register(RegisterDriverRequest $request, $carrierSlug)
    {
        $carrier = Carrier::where('slug', $carrierSlug)
            ->where('referrer_token', $request->token)
            ->firstOrFail();

        $validated = $request->validated();

        // Crear el usuario y asignar rol
        $user = $this->createUser($validated);

        // Crear driver details con carrier asociado
        $driverDetails = $this->createDriverDetails($user, $validated, $carrier->id);

        // Notificar a admins y carrier
        $this->notifyNewDriverRegistration($user, $carrier);

        return redirect()->route('driver.registration.success')->with([
            'message' => 'Registration successful! Please check your email to confirm your account.',
            'carrier_name' => $carrier->name
        ]);
    }

    /**
     * Procesa el registro de conductores independientes (ahora con carrier seleccionado)
     */
    public function registerIndependent(RegisterDriverRequest $request)
    {
        Log::info('registerIndependent llamado');

        try {
            $validated = $request->validated();

            // Buscar carrier por slug
            $carrier = Carrier::where('slug', $validated['carrier_slug'])->firstOrFail();

            // Crear el usuario y asignar rol
            $user = $this->createUser($validated);

            // Crear driver details con carrier asociado
            $driverDetails = $this->createDriverDetails($user, $validated, $carrier->id);

            // Notificar a admins y carrier
            $this->notifyNewDriverRegistration($user, $carrier);

            return redirect()->route('driver.registration.success')->with([
                'message' => 'Registration successful! Please check your email to confirm your account.',
                'carrier_name' => $carrier->name
            ]);
        } catch (\Exception $e) {
            Log::error('Error en registerIndependent: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error processing registration. Please try again.']);
        }
    }
    /**
     * Confirma el correo electrónico del conductor
     */
    public function confirmEmail($token)
    {
        $driver = UserDriverDetail::where('confirmation_token', $token)->firstOrFail();

        $driver->update([
            'confirmation_token' => null,
            'email_verified_at' => now()
        ]);

        // Si es un registro independiente (sin carrier asignado)
        if (!$driver->carrier_id) {
            Auth::login($driver->user);
            return redirect()->route('driver.select_carrier')
                ->with('success', 'Email confirmed! Please select a carrier to work with.');
        }

        return redirect()->route('login')
            ->with('success', 'Email confirmed. Please log in to complete your registration.');
    }

    /**
     * Muestra la página para seleccionar un carrier (para registros independientes)
     */
    public function showSelectCarrier()
    {
        if (!Auth::check() || Auth::user()->role != 'driver') {
            return redirect()->route('login');
        }

        $driver = Auth::user()->driverDetails;

        // Si ya tiene un carrier asignado, redirigir
        if ($driver && $driver->carrier_id) {
            return redirect()->route('driver.dashboard');
        }

        // Obtener carriers activos con información adicional
        $carriers = Carrier::where('status', Carrier::STATUS_ACTIVE)
            ->with(['membership', 'media'])
            ->get()
            ->map(function($carrier) {
                // Agregar recuento de conductores y máximo permitido
                $driver_count = $carrier->userDrivers()->count();
                $max_drivers = $carrier->membership->max_drivers ?? 1;
                
                // Agregar estos datos al carrier
                $carrier->driver_count = $driver_count;
                $carrier->max_drivers = $max_drivers;
                $carrier->is_full = $driver_count >= $max_drivers;
                
                return $carrier;
            });

        return view('auth.user_driver.select_carrier', compact('carriers'));
    }

    /**
     * Procesa la selección de carrier
     */
    public function selectCarrier(Request $request)
    {
        $request->validate([
            'carrier_id' => 'required|exists:carriers,id'
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->route('driver.register.error');
        }

        $carrier = Carrier::findOrFail($request->carrier_id);

        // Verificar si el carrier puede aceptar más conductores
        if ($carrier->userDrivers()->count() >= ($carrier->membership->max_drivers ?? 1)) {
            return back()->with('error', 'This carrier has reached its maximum number of drivers.');
        }

        // Asignar carrier al driver
        $driver->update([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_PENDING
        ]);

        // Notificar a admins y carrier
        $this->notifyNewDriverRegistration($user, $carrier);

        return redirect()->route('driver.dashboard')
            ->with('success', "You have successfully joined {$carrier->name}. Your application is now pending approval.");
    }

    /**
     * Valida si un token y carrier son válidos para el registro
     */
    private function validateTokenAndCarrier(Carrier $carrier, $token)
    {
        // Primero verificamos si el token es válido
        if ($carrier->referrer_token !== $token) {
            return false;
        }
        
        // Verificamos si el carrier está activo
        if ($carrier->status !== Carrier::STATUS_ACTIVE) {
            return false;
        }
        
        // Verificamos si el carrier ha alcanzado su límite de conductores
        if ($carrier->userDrivers()->count() >= ($carrier->membership->max_drivers ?? 1)) {
            return false;
        }
        
        return true;
    }

    /**
     * Crea un nuevo usuario
     */
    private function createUser($data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);

        $user->assignRole('driver');

        return $user;
    }

    /**
     * Crea los detalles del conductor
     */
    private function createDriverDetails($user, $data, $carrierId = null)
    {
        return $user->driverDetails()->create([
            'carrier_id' => $carrierId,
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'],
            'license_number' => $data['license_number'] ?? null,
            'phone' => $data['phone'],
            'status' => UserDriverDetail::STATUS_PENDING,
            'confirmation_token' => Str::random(32),
            'current_step' => 1
        ]);
    }

    /**
     * Notifica a admins y usuarios del carrier cuando un nuevo driver se registra
     */
    private function notifyNewDriverRegistration(User $user, Carrier $carrier): void
    {
        try {
            $notification = new NewDriverRegisteredNotification($user, $carrier);

            // Notificar a superadmins
            $admins = User::role('superadmin')->get();
            foreach ($admins as $admin) {
                $admin->notify($notification);
            }

            // Notificar a usuarios del carrier
            $carrierUsers = $carrier->userCarriers()->with('user')->get();
            foreach ($carrierUsers as $carrierDetail) {
                if ($carrierDetail->user) {
                    $carrierDetail->user->notify($notification);
                }
            }

            Log::info('New driver registration notifications sent', [
                'driver_user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'admins_notified' => $admins->count(),
                'carrier_users_notified' => $carrierUsers->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new driver registration notifications', [
                'driver_user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}