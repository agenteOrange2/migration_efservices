<?php

namespace App\Livewire\Carrier;

use App\Models\DriverArchive;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Inactive Drivers List Component
 * 
 * Displays a paginated, searchable, and filterable list of inactive drivers
 * for the authenticated carrier. Supports search by name/email, date range
 * filtering, and sorting.
 */
class InactiveDriversList extends Component
{
    use WithPagination;

    // Search and filter properties
    public string $search = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    
    // Sorting properties
    public string $sortField = 'archived_at';
    public string $sortDirection = 'desc';

    /**
     * Reset pagination when search is updated.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when dateFrom is updated.
     */
    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when dateTo is updated.
     */
    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Sort by a specific field.
     * 
     * @param string $field
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            // Toggle direction if same field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Set new field with default ascending direction
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    /**
     * Get the carrier ID from the authenticated user.
     *
     * @return int|null
     */
    protected function getCarrierId(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Load carrierDetails relationship if not loaded
        if (!$user->relationLoaded('carrierDetails')) {
            $user->load('carrierDetails');
        }

        // Try to get carrier ID from user's carrier details (for carrier employees)
        if ($user->carrierDetails && $user->carrierDetails->carrier_id) {
            return $user->carrierDetails->carrier_id;
        }

        // Load carriers relationship if not loaded
        if (!$user->relationLoaded('carriers')) {
            $user->load('carriers');
        }

        // Try to get carrier ID from carriers relationship (for carrier owners/managers)
        if ($user->carriers && $user->carriers->isNotEmpty()) {
            return $user->carriers->first()->id;
        }

        return null;
    }

    /**
     * Get the inactive drivers for the current carrier.
     * Applies search, filtering, sorting, and pagination.
     * 
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInactiveDriversProperty()
    {
        $carrierId = $this->getCarrierId();

        if (!$carrierId) {
            return collect()->paginate(15);
        }

        $query = DriverArchive::query()
            ->where('carrier_id', $carrierId)
            ->where('status', DriverArchive::STATUS_ARCHIVED);

        // Apply search filter (name or email)
        if (!empty($this->search)) {
            $searchTerm = $this->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("JSON_EXTRACT(driver_data_snapshot, '$.name') LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_EXTRACT(driver_data_snapshot, '$.last_name') LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_EXTRACT(driver_data_snapshot, '$.middle_name') LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_EXTRACT(driver_data_snapshot, '$.email') LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        // Apply date range filter
        if (!empty($this->dateFrom)) {
            try {
                // Try to parse MM/DD/YYYY format from Litepicker
                $date = \Carbon\Carbon::createFromFormat('m/d/Y', $this->dateFrom);
                $query->whereDate('archived_at', '>=', $date->format('Y-m-d'));
            } catch (\Exception $e) {
                // Fallback to standard parsing if format doesn't match
                try {
                    $date = \Carbon\Carbon::parse($this->dateFrom);
                    $query->whereDate('archived_at', '>=', $date->format('Y-m-d'));
                } catch (\Exception $e2) {
                    // Skip filter if date is invalid
                }
            }
        }

        if (!empty($this->dateTo)) {
            try {
                // Try to parse MM/DD/YYYY format from Litepicker
                $date = \Carbon\Carbon::createFromFormat('m/d/Y', $this->dateTo);
                $query->whereDate('archived_at', '<=', $date->format('Y-m-d'));
            } catch (\Exception $e) {
                // Fallback to standard parsing if format doesn't match
                try {
                    $date = \Carbon\Carbon::parse($this->dateTo);
                    $query->whereDate('archived_at', '<=', $date->format('Y-m-d'));
                } catch (\Exception $e2) {
                    // Skip filter if date is invalid
                }
            }
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginate results (15 per page as per requirements)
        return $query->paginate(15);
    }

    /**
     * Render the component.
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.carrier.inactive-drivers-list', [
            'inactiveDrivers' => $this->inactiveDrivers,
        ]);
    }
}
