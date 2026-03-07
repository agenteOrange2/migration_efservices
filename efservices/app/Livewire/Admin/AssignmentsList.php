<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Driver\Training;
use App\Models\Carrier;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AssignmentsList extends Component
{
    use WithPagination;

    public $search = '';
    public $trainingFilter = '';
    public $carrierFilter = '';
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $selectedAssignments = [];
    public $selectAll = false;

    protected $queryString = ['search', 'trainingFilter', 'carrierFilter', 'statusFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTrainingFilter()
    {
        $this->resetPage();
    }

    public function updatingCarrierFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedAssignments = $this->assignments->pluck('id')->toArray();
        } else {
            $this->selectedAssignments = [];
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->trainingFilter = '';
        $this->carrierFilter = '';
        $this->statusFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function bulkMarkComplete()
    {
        if (empty($this->selectedAssignments)) {
            session()->flash('error', 'No assignments selected');
            return;
        }

        DriverTraining::whereIn('id', $this->selectedAssignments)
            ->update([
                'status' => 'completed',
                'completed_date' => Carbon::now()
            ]);

        $this->selectedAssignments = [];
        $this->selectAll = false;
        session()->flash('success', 'Selected assignments marked as completed');
    }

    public function bulkMarkInProgress()
    {
        if (empty($this->selectedAssignments)) {
            session()->flash('error', 'No assignments selected');
            return;
        }

        DriverTraining::whereIn('id', $this->selectedAssignments)
            ->update(['status' => 'in_progress']);

        $this->selectedAssignments = [];
        $this->selectAll = false;
        session()->flash('success', 'Selected assignments marked as in progress');
    }

    public function bulkDelete()
    {
        if (empty($this->selectedAssignments)) {
            session()->flash('error', 'No assignments selected');
            return;
        }

        DriverTraining::whereIn('id', $this->selectedAssignments)->delete();

        $this->selectedAssignments = [];
        $this->selectAll = false;
        session()->flash('success', 'Selected assignments deleted successfully');
    }

    public function getAssignmentsProperty()
    {
        $query = DriverTraining::with(['driver.user', 'driver.carrier', 'training']);

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('driver.user', function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('training', function($query) {
                    $query->where('title', 'like', '%' . $this->search . '%');
                });
            });
        }

        if ($this->trainingFilter) {
            $query->where('training_id', $this->trainingFilter);
        }

        if ($this->carrierFilter) {
            $query->whereHas('driver', function($q) {
                $q->where('carrier_id', $this->carrierFilter);
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFrom) {
            $query->where('assigned_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('assigned_date', '<=', $this->dateTo);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function getTrainingsProperty()
    {
        return Training::where('status', 'active')->orderBy('title')->get();
    }

    public function getCarriersProperty()
    {
        return Carrier::where('status', Carrier::STATUS_ACTIVE)->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.assignments-list', [
            'assignments' => $this->assignments,
            'trainings' => $this->trainings,
            'carriers' => $this->carriers
        ]);
    }
}

