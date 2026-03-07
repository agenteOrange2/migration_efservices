<?php

namespace App\Livewire\Carrier;

use App\Models\Carrier;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverApplication;
use Illuminate\Support\Facades\Auth;

class CarrierDriversList extends Component
{
    use WithPagination;
    
    public $search = '';
    public $statusFilter = '';
    
    public function mount()
    {
        // No necesitamos guardar el carrier como propiedad pública
        // Lo obtendremos cuando sea necesario a través de propiedades computadas
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function getCarrierProperty()
    {
        return Auth::user()->carrierDetails->carrier;
    }
    
    public function getDriversProperty()
    {
        $carrierId = $this->carrier->id;

        $query = UserDriverDetail::query()
            ->where('carrier_id', $carrierId)
            ->with(['user', 'application']);
            
        // Aplicar filtros de búsqueda
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->whereHas('user', function($userQuery) {
                    $userQuery->where('name', 'like', "%{$this->search}%")
                              ->orWhere('email', 'like', "%{$this->search}%");
                })
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }
        
        // Filtrar por estado efectivo:
        // - draft/pending/rejected → application status tiene prioridad
        // - approved → recruitment terminado, usa driver status (active/inactive/pending)
        if ($this->statusFilter !== '') {
            $filter = $this->statusFilter;
            switch ($filter) {
                case UserDriverDetail::EFFECTIVE_STATUS_ACTIVE:
                    // Active = driver status active + application approved or no application
                    // (excludes draft/pending/rejected applications)
                    $query->where('status', UserDriverDetail::STATUS_ACTIVE)
                          ->where(function($q) {
                              $q->whereDoesntHave('application')
                                ->orWhereHas('application', function($app) {
                                    $app->whereNotIn('status', [
                                        DriverApplication::STATUS_DRAFT,
                                        DriverApplication::STATUS_PENDING,
                                        DriverApplication::STATUS_REJECTED,
                                    ]);
                                });
                          });
                    break;
                case UserDriverDetail::EFFECTIVE_STATUS_DRAFT:
                    $query->whereHas('application', function($app) {
                        $app->where('status', DriverApplication::STATUS_DRAFT);
                    });
                    break;
                case UserDriverDetail::EFFECTIVE_STATUS_PENDING_REVIEW:
                    // Pending = application pending, OR (approved/no app + driver pending)
                    $query->where(function($q) use ($carrierId) {
                        $q->whereHas('application', function($app) {
                            $app->where('status', DriverApplication::STATUS_PENDING);
                        })->orWhere(function($sub) use ($carrierId) {
                            $sub->where('carrier_id', $carrierId)
                                ->where('status', UserDriverDetail::STATUS_PENDING)
                                ->where(function($inner) {
                                    $inner->whereDoesntHave('application')
                                          ->orWhereHas('application', function($app) {
                                              $app->where('status', DriverApplication::STATUS_APPROVED);
                                          });
                                });
                        });
                    });
                    break;
                case UserDriverDetail::EFFECTIVE_STATUS_REJECTED:
                    $query->whereHas('application', function($app) {
                        $app->where('status', DriverApplication::STATUS_REJECTED);
                    });
                    break;
                case UserDriverDetail::EFFECTIVE_STATUS_INACTIVE:
                    // Inactive = driver inactive + application approved or no application
                    // (excludes draft/pending/rejected which have their own status)
                    $query->where('status', UserDriverDetail::STATUS_INACTIVE)
                          ->where(function($q) {
                              $q->whereDoesntHave('application')
                                ->orWhereHas('application', function($app) {
                                    $app->whereNotIn('status', [
                                        DriverApplication::STATUS_DRAFT,
                                        DriverApplication::STATUS_PENDING,
                                        DriverApplication::STATUS_REJECTED,
                                    ]);
                                });
                          });
                    break;
            }
        }
        
        return $query->orderBy('created_at', 'desc')->paginate(10);
    }
    
    public function getMembershipStatsProperty()
    {
        $maxDrivers = $this->carrier->membership->max_drivers ?? 1;
        $currentDrivers = UserDriverDetail::where('carrier_id', $this->carrier->id)->count();
        
        return [
            'maxDrivers' => $maxDrivers,
            'currentDrivers' => $currentDrivers,
            'percentage' => $maxDrivers > 0 ? ($currentDrivers / $maxDrivers) * 100 : 0,
            'exceededLimit' => $currentDrivers >= $maxDrivers
        ];
    }
    
    public function render()
    {
        return view('livewire.carrier.carrier-drivers-list', [
            'drivers' => $this->drivers,
            'membershipStats' => $this->membershipStats
        ]);
    }
}