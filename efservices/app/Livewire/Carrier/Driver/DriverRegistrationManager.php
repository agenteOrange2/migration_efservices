<?php

namespace App\Livewire\Carrier\Driver;

use App\Models\Carrier;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use App\Helpers\Constants;
use App\Models\UserDriverDetail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class DriverRegistrationManager extends Component
{
    // Carrier model
    public Carrier $carrier;
    
    // Current step
    public $currentStep = 1;
    public $totalSteps = 15;
    
    // Driver for edit mode
    public $driver = null;
    public $driverId = null;
    public $userDriverDetail = null;
    
    // Independent registration flag
    public $isIndependent = false;
    
    // Edit mode flag
    public $isEditMode = false;
    
    // Mounting the component
    public function mount(Carrier $carrier, UserDriverDetail $driver = null, $isIndependent = false)
    {
        $this->carrier = $carrier;
        $this->driver = $driver;
        $this->isIndependent = $isIndependent;
        $this->isEditMode = !is_null($driver) && $driver->exists;
        
        if ($this->driver && $this->driver->exists) {
            $this->driverId = $this->driver->id;
            $this->userDriverDetail = $this->driver;
            
            // Establecer el paso actual según el driver
            $this->currentStep = $this->driver->current_step ?: 1;
        }
    }
    
    // Ir a un tab específico
    public function goToTab($tabNumber)
    {
        if ($tabNumber >= 1 && $tabNumber <= $this->totalSteps) {
            $this->currentStep = $tabNumber;
        }
    }
    
    // Ir al siguiente paso
    #[On('nextStep')]
    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }
    
    // Ir al paso anterior
    #[On('prevStep')]
    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    // Cuando un driver es creado en el primer paso
    #[On('driverCreated')]
    public function handleDriverCreated($driverId)
    {
        $this->driverId = $driverId;
        $this->userDriverDetail = UserDriverDetail::find($driverId);
    }
    
    // Manejar guardar y salir desde cualquier paso
    #[On('saveAndExit')]
    public function handleSaveAndExit()
    {
        return redirect()->route('carrier.drivers.index')
            ->with('success', 'Información del conductor guardada correctamente.');
    }
    
    // Enviar formulario en el paso final
    public function submitForm()
    {
        if ($this->driverId) {
            UserDriverDetail::where('id', $this->driverId)->update([
                'application_completed' => true
            ]);
            session()->flash('success', 'Registro de conductor completado exitosamente.');
            return redirect()->route('carrier.drivers.index');
        }
    }
    
    // Render
    public function render()
    {
        return view('livewire.carrier.driver.driver-registration-manager', [
            'usStates' => Constants::usStates(),
            'driverPositions' => Constants::driverPositions(),
            'referralSources' => Constants::referralSources(),
        ]);
    }
}

