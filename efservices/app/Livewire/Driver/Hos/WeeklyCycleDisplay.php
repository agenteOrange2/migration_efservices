<?php

namespace App\Livewire\Driver\Hos;

use Livewire\Component;
use App\Services\Hos\HosWeeklyCycleService;
use Illuminate\Support\Facades\Auth;

class WeeklyCycleDisplay extends Component
{
    public array $cycleStatus = [];
    public array $dailyBreakdown = [];

    public function mount(HosWeeklyCycleService $cycleService)
    {
        $driverId = Auth::user()->driverDetail?->id;
        
        if ($driverId) {
            $this->cycleStatus = $cycleService->getWeeklyCycleStatus($driverId);
            $this->dailyBreakdown = $cycleService->getDailyBreakdown($driverId, 
                $this->cycleStatus['cycle_type'] === '60_7' ? 7 : 8
            );
        }
    }

    public function getProgressColorProperty(): string
    {
        $percentage = $this->cycleStatus['percentage_used'] ?? 0;
        
        if ($percentage >= 90) {
            return 'danger';
        } elseif ($percentage >= 75) {
            return 'warning';
        }
        return 'success';
    }

    public function render()
    {
        return view('livewire.driver.hos.weekly-cycle-display');
    }
}
