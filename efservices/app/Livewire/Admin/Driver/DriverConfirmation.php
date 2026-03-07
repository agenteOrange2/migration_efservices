<?php

namespace App\Livewire\Admin\Driver;

use Livewire\Component;
use App\Models\UserDriverDetail;
use App\Services\Driver\StepCompletionCalculator;

class DriverConfirmation extends Component
{
    // Driver ID para referencia a pasos anteriores
    public $driverId;
    
    // Estado del botón
    public $loading = false;
    
    // Resumen de completitud
    public $completionSummary = [];
    public $stepsNeedingAttention = [];
    public $totalCompletionPercentage = 0;
    public $isComplete = false;
    
    // Step names for display
    protected $stepNames = [
        1 => 'General Info',
        2 => 'Address',
        3 => 'Application',
        4 => 'License',
        5 => 'Medical',
        6 => 'Training',
        7 => 'Traffic',
        8 => 'Accident',
        9 => 'FMCSR',
        10 => 'Employment',
        11 => 'Policy',
        12 => 'Criminal',
        13 => 'Certification',
        14 => 'Confirmation',
    ];
    
    public function mount($driverId = null)
    {
        $this->driverId = $driverId;
        $this->loadCompletionSummary();
    }
    
    /**
     * Cargar resumen de completitud usando StepCompletionCalculator
     */
    protected function loadCompletionSummary(): void
    {
        if (!$this->driverId) {
            return;
        }

        $calculator = app(StepCompletionCalculator::class);
        $summary = $calculator->getCompletionSummary($this->driverId);
        
        $this->completionSummary = $summary['steps'];
        $this->totalCompletionPercentage = $summary['total_percentage'];
        $this->stepsNeedingAttention = collect($summary['steps_needing_attention'])
            ->map(function ($step) {
                $step['name'] = $this->stepNames[$step['step']] ?? 'Step ' . $step['step'];
                return $step;
            })
            ->toArray();
        
        $this->isComplete = empty($this->stepsNeedingAttention);
    }
    
    /**
     * Obtener el nombre de un step
     */
    public function getStepName(int $step): string
    {
        return $this->stepNames[$step] ?? 'Step ' . $step;
    }
    
    // Método para finalizar el registro y redirigir
    public function finish()
    {
        $this->loading = true;
        
        // Obtenemos el driver y redirectionamos según su carrier
        $userDriverDetail = UserDriverDetail::with('carrier')->find($this->driverId);
        
        if ($userDriverDetail && $userDriverDetail->carrier) {
            $carrierSlug = $userDriverDetail->carrier->slug;
            return redirect()->route('admin.carrier.user_drivers.index', ['carrier' => $carrierSlug])
                ->with('success', 'La solicitud ha sido enviada para revisión.');
        }

        // Si no tenemos carrier en el modelo, intentamos obtenerlo de la ruta
        $carrierSlug = request()->route('carrier');
        return redirect()->route('admin.carrier.user_drivers.index', ['carrier' => $carrierSlug])
            ->with('success', 'La solicitud ha sido enviada para revisión.');
    }
    
    // Método para volver al paso anterior
    public function previous()
    {
        $this->dispatch('prevStep');
    }
    
    // Método para ir a un step específico
    public function goToStep(int $step)
    {
        $this->dispatch('goToTab', $step);
    }
    
    public function render()
    {
        return view('livewire.admin.driver.steps.driver-confirmation');
    }
}