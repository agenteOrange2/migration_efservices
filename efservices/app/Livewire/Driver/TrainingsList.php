<?php

namespace App\Livewire\Driver;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Driver\DriverTraining;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TrainingsList extends Component
{
    use WithPagination;

    public $statusFilter = 'all';
    public $selectedTrainingId = null;
    public $showCompletionModal = false;

    protected $listeners = [
        'trainingStatusUpdated' => '$refresh',
        'trainingCompleted' => 'handleTrainingCompleted',
        'openCompletionModal' => 'openCompletionModal',
    ];

    public function mount()
    {
        $this->checkOverdueTrainings();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function checkOverdueTrainings()
    {
        try {
            $user = Auth::user();
            $driverDetail = $user->driverDetail;
            
            if (!$driverDetail) {
                return;
            }

            // Update overdue trainings
            DriverTraining::where('user_driver_detail_id', $driverDetail->id)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->update(['status' => 'overdue']);
        } catch (\Exception $e) {
            Log::error('Error checking overdue trainings', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function openCompletionModal($trainingId)
    {
        $this->selectedTrainingId = $trainingId;
        $this->showCompletionModal = true;
    }

    public function handleTrainingCompleted()
    {
        $this->showCompletionModal = false;
        $this->selectedTrainingId = null;
        $this->dispatch('trainingStatusUpdated');
        
        session()->flash('success', 'Training marked as completed successfully!');
    }

    public function getTrainingsProperty()
    {
        $user = Auth::user();
        $driverDetail = $user->driverDetail;
        
        if (!$driverDetail) {
            return collect();
        }

        $query = DriverTraining::where('user_driver_detail_id', $driverDetail->id)
            ->with(['training', 'training.media']);

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Order by: overdue first, then by due date, then by created date
        $query->orderByRaw("CASE 
            WHEN status = 'overdue' THEN 1 
            WHEN status = 'in_progress' THEN 2 
            WHEN status = 'assigned' THEN 3 
            WHEN status = 'completed' THEN 4 
            ELSE 5 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc');

        return $query->paginate(9);
    }

    public function getStatsProperty()
    {
        $user = Auth::user();
        $driverDetail = $user->driverDetail;
        
        if (!$driverDetail) {
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'pending' => 0,
                'overdue' => 0,
                'completion_percentage' => 0,
            ];
        }

        $total = DriverTraining::where('user_driver_detail_id', $driverDetail->id)->count();
        $completed = DriverTraining::where('user_driver_detail_id', $driverDetail->id)
            ->where('status', 'completed')->count();
        $in_progress = DriverTraining::where('user_driver_detail_id', $driverDetail->id)
            ->where('status', 'in_progress')->count();
        $pending = DriverTraining::where('user_driver_detail_id', $driverDetail->id)
            ->where('status', 'assigned')->count();
        $overdue = DriverTraining::where('user_driver_detail_id', $driverDetail->id)
            ->where('status', 'overdue')->count();

        $completion_percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $in_progress,
            'pending' => $pending,
            'overdue' => $overdue,
            'completion_percentage' => $completion_percentage,
        ];
    }

    public function render()
    {
        return view('livewire.driver.trainings-list', [
            'trainings' => $this->trainings,
            'stats' => $this->stats,
        ]);
    }
}

