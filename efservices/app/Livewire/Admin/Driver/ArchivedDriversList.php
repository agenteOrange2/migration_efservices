<?php

namespace App\Livewire\Admin\Driver;

use App\Models\Carrier;
use App\Models\DriverArchive;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

/**
 * Livewire component for listing archived drivers.
 */
#[Layout('layouts.admin')]
class ArchivedDriversList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';
    
    #[Url]
    public ?int $carrierId = null;
    
    #[Url]
    public ?string $dateFrom = null;
    
    #[Url]
    public ?string $dateTo = null;
    
    #[Url]
    public string $archiveReason = '';
    
    #[Url]
    public string $sortField = 'archived_at';
    
    #[Url]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    protected $queryString = [
        'search' => ['except' => ''],
        'carrierId' => ['except' => null],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'archiveReason' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCarrierId(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'carrierId', 'dateFrom', 'dateTo', 'archiveReason']);
        $this->resetPage();
    }

    public function getArchivedDriversProperty()
    {
        $query = DriverArchive::query()
            ->with(['carrier', 'user', 'migrationRecord.targetCarrier'])
            ->archived();

        // Filter by carrier if user is carrier admin
        if ($this->carrierId) {
            $query->forCarrier($this->carrierId);
        } elseif (!auth()->user()->hasRole('superadmin')) {
            // Non-superadmin users can only see their carrier's archives
            $carrierDetail = auth()->user()->carrierDetails;
            if ($carrierDetail) {
                $query->forCarrier($carrierDetail->carrier_id);
            }
        }

        // Search by name
        if ($this->search) {
            $query->searchByName($this->search);
        }

        // Filter by date range
        if ($this->dateFrom && $this->dateTo) {
            $query->betweenDates($this->dateFrom, $this->dateTo);
        } elseif ($this->dateFrom) {
            $query->where('archived_at', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->where('archived_at', '<=', $this->dateTo);
        }

        // Filter by archive reason
        if ($this->archiveReason) {
            $query->byReason($this->archiveReason);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    public function getCarriersProperty()
    {
        // Only superadmin can see all carriers
        if (auth()->user()->hasRole('superadmin')) {
            return Carrier::active()->orderBy('name')->get();
        }
        return collect();
    }

    public function getArchiveReasonsProperty(): array
    {
        return [
            DriverArchive::REASON_MIGRATION => 'Migration',
            DriverArchive::REASON_TERMINATION => 'Termination',
            DriverArchive::REASON_MANUAL => 'Manual Archive',
        ];
    }

    public function viewArchive(int $archiveId): void
    {
        // Determine route based on user role
        if (auth()->user()->hasRole('superadmin')) {
            $this->redirect(route('admin.drivers.archived.show', $archiveId));
        } else {
            $this->redirect(route('carrier.drivers.inactive.show', $archiveId));
        }
    }

    public function render()
    {
        return view('livewire.admin.driver.archived-drivers-list', [
            'archivedDrivers' => $this->archivedDrivers,
            'carriers' => $this->carriers,
            'archiveReasons' => $this->archiveReasons,
        ]);
    }
}
