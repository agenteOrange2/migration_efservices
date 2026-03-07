<?php
namespace App\Livewire\Driver;

use App\Models\Carrier;
use Livewire\Component;
use App\Helpers\Constants;
use Illuminate\Support\Facades\Log;
use App\Models\UserDriverDetail;
use Livewire\Attributes\On;

class DriverRegistrationManager extends Component
{
    // Carrier model (puede ser null para registro independiente)
    public $carrier;
    
    // Token de referencia (opcional para registro independiente)
    public $token;
    
    // Tipo de registro: 'referred' o 'independent'
    public $registrationType;
    
    // Current step
    public $currentStep = 1;
    public $totalSteps = 15;
    
    // Driver ID para modo edición
    public $driverId = null;
    public $userDriverDetail = null;
    
    // Mounting the component
    public function mount($carrier = null, $token = null, $driverId = null, $currentStep = null)
    {
        $this->carrier = $carrier;
        $this->token = $token;
        
        // Si recibimos un driverId, estamos en modo edición/continuación
        if ($driverId) {
            $this->driverId = $driverId;
            $this->userDriverDetail = UserDriverDetail::find($driverId);
            $this->registrationType = $this->userDriverDetail->carrier_id ? 'referred' : 'independent';
        } else {
            // Determinar el tipo de registro para nuevos registros
            $this->registrationType = ($carrier && $token) ? 'referred' : 'independent';
        }
        
        // Si se proporciona un paso específico, usarlo
        if ($currentStep) {
            $this->currentStep = $currentStep;
        }
        
    }
    
    // Go to the next step
    #[On('nextStep')]
    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {
            $previousStep = $this->currentStep;
            $this->currentStep++;

            if ($this->driverId) {
                // Actualizar el paso actual en la base de datos
                $this->updateCurrentStep($this->currentStep);
                
                // Si venimos del paso 1 (registro inicial), redirigir a la URL de continuación
                if ($previousStep === 1) {
                    return redirect()->route('driver.registration.continue', ['step' => $this->currentStep]);
                }
            }
        }
    }

    private function updateCurrentStep($step)
    {
        if ($this->driverId) {
            $driver = UserDriverDetail::find($this->driverId);
            if ($driver && $driver->current_step < $step) {
                $driver->update(['current_step' => $step]);
            }
        }
    }
    
    // Ir al paso anterior
    #[On('prevStep')]
    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            
            // Si el driver ya está registrado y estamos en el paso 1, redirigir a la URL de continuación
            if ($this->currentStep === 1 && $this->driverId) {
                return redirect()->route('driver.registration.continue', ['step' => 1]);
            }
        }
    }
    
    // Cuando un driver es creado en el primer paso
    #[On('driverCreated')]
    public function handleDriverCreated($driverId)
    {
        $this->driverId = $driverId;
        $this->userDriverDetail = UserDriverDetail::find($driverId);
        
        // No avanzamos automáticamente aquí porque el StepGeneral
        // mostrará un modal de credenciales y el usuario debe hacer clic
        // en "Continue Registration" para avanzar al siguiente paso
    }
    
    // Manejar guardar y salir desde cualquier paso
    #[On('saveAndExit')]
    public function handleSaveAndExit()
    {
        // Cerrar sesión del usuario
        \Illuminate\Support\Facades\Auth::logout();
        
        // Invalidar la sesión y regenerar el token
        session()->invalidate();
        session()->regenerateToken();
        
        // Mensaje de éxito
        session()->flash('success', 'Your progress has been saved. You can continue your registration later by logging in.');
        
        
        // Redirigir a la página de login
        return redirect()->route('login');
    }
    
    // Enviar formulario en el paso final
    public function submitForm()
    {
        if ($this->driverId) {
            UserDriverDetail::where('id', $this->driverId)->update([
                'application_completed' => true
            ]);
            
            session()->flash('success', 'Driver registration completed successfully.');
            
            // Redireccionar según tipo de registro
            if ($this->registrationType === 'independent') {
                return redirect()->route('driver.select_carrier');
            } else {
                return redirect()->route('admin.carrier.user_drivers.index', $this->carrier);
            }
        }
    }
    
    // Render
    public function render()
    {
        return view('livewire.driver.driver-registration-manager', [
            'usStates' => Constants::usStates(),
            'driverPositions' => Constants::driverPositions(),
            'referralSources' => Constants::referralSources(),
            'isIndependent' => $this->registrationType === 'independent'
        ]);
    }
}