<?php

namespace App\Livewire\Carrier\Hos;

use Livewire\Component;
use App\Services\Hos\DriverAvailabilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class DriverAvailability extends Component
{
    public ?int $tripDurationMinutes = null;
    public Collection $drivers;

    public function mount(DriverAvailabilityService $availabilityService)
    {
        $carrierId = $this->getCarrierId();
        $this->drivers = collect();
        
        if ($carrierId) {
            $this->drivers = $availabilityService->getAvailableDrivers($carrierId, $this->tripDurationMinutes);
        }
    }

    public function filterByDuration(DriverAvailabilityService $availabilityService)
    {
        $carrierId = $this->getCarrierId();
        
        if ($carrierId) {
            $this->drivers = $availabilityService->getAvailableDrivers($carrierId, $this->tripDurationMinutes);
        }
    }

    protected function getCarrierId(): ?int
    {
        $user = Auth::user();
        
        if ($user->carrierDetails) {
            return $user->carrierDetails->carrier_id;
        }

        $carrier = $user->carriers()->first();
        return $carrier?->id;
    }

    public function render()
    {
        return view('livewire.carrier.hos.driver-availability');
    }
}
