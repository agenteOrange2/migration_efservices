<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Driver\Training;
use Illuminate\Support\Facades\Log;

class TrainingsList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $contentTypeFilter = '';
    public $viewMode = 'cards'; // 'cards' or 'table'
    public $selectedTrainings = [];
    public $selectAll = false;

    protected $queryString = ['search', 'statusFilter', 'contentTypeFilter', 'viewMode'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingContentTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTrainings = $this->trainings->pluck('id')->toArray();
        } else {
            $this->selectedTrainings = [];
        }
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'cards' ? 'table' : 'cards';
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->contentTypeFilter = '';
        $this->resetPage();
    }

    public function bulkActivate()
    {
        if (empty($this->selectedTrainings)) {
            session()->flash('error', 'No trainings selected');
            return;
        }

        Training::whereIn('id', $this->selectedTrainings)->update(['status' => 'active']);
        $this->selectedTrainings = [];
        $this->selectAll = false;
        session()->flash('success', 'Selected trainings activated successfully');
    }

    public function bulkDeactivate()
    {
        if (empty($this->selectedTrainings)) {
            session()->flash('error', 'No trainings selected');
            return;
        }

        Training::whereIn('id', $this->selectedTrainings)->update(['status' => 'inactive']);
        $this->selectedTrainings = [];
        $this->selectAll = false;
        session()->flash('success', 'Selected trainings deactivated successfully');
    }

    public function getTrainingsProperty()
    {
        $query = Training::withCount('driverAssignments');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->contentTypeFilter) {
            $query->where('content_type', $this->contentTypeFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(12);
    }

    public function render()
    {
        return view('livewire.admin.trainings-list', [
            'trainings' => $this->trainings
        ]);
    }
}

