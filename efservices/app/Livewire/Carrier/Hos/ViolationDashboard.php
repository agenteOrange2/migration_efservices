<?php

namespace App\Livewire\Carrier\Hos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Hos\HosViolation;
use App\Models\UserDriverDetail;
use Illuminate\Support\Facades\Auth;

class ViolationDashboard extends Component
{
    use WithPagination;

    public ?int $driverId = null;
    public ?string $severity = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    protected $queryString = ['driverId', 'severity', 'dateFrom', 'dateTo'];

    public function updatingDriverId()
    {
        $this->resetPage();
    }

    public function updatingSeverity()
    {
        $this->resetPage();
    }

    public function acknowledge(int $violationId)
    {
        $violation = HosViolation::find($violationId);
        $carrierId = $this->getCarrierId();
        
        if ($violation && $violation->driver->carrier_id === $carrierId) {
            $violation->acknowledge(Auth::id());
            session()->flash('message', 'Violation acknowledged successfully.');
        }
    }

    public function render()
    {
        $carrierId = $this->getCarrierId();
        
        $query = HosViolation::whereHas('driver', function ($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->with(['driver.user']);

        if ($this->driverId) {
            $query->where('user_driver_detail_id', $this->driverId);
        }

        if ($this->severity) {
            $query->where('violation_severity', $this->severity);
        }

        if ($this->dateFrom) {
            $query->whereDate('violation_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('violation_date', '<=', $this->dateTo);
        }

        $violations = $query->orderBy('violation_date', 'desc')->paginate(15);
        
        $drivers = UserDriverDetail::where('carrier_id', $carrierId)
            ->with('user')
            ->get();

        return view('livewire.carrier.hos.violation-dashboard', [
            'violations' => $violations,
            'drivers' => $drivers,
        ]);
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
}
