<?php

namespace App\Livewire\Driver;

use Livewire\Component;
use App\Models\Admin\Driver\DriverTraining;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrainingCard extends Component
{
    public $assignment;
    public $trainingId;

    public function mount($assignment)
    {
        $this->assignment = $assignment;
        $this->trainingId = $assignment->id;
    }

    public function startTraining()
    {
        try {
            $user = Auth::user();
            $driverDetail = $user->driverDetail;
            
            if (!$driverDetail) {
                $this->dispatch('error', message: 'Driver information not found');
                return;
            }

            $assignment = DriverTraining::where('id', $this->trainingId)
                ->where('user_driver_detail_id', $driverDetail->id)
                ->firstOrFail();

            // Only allow starting if status is 'assigned' or 'overdue'
            if (in_array($assignment->status, ['assigned', 'overdue'])) {
                $assignment->status = 'in_progress';
                $assignment->save();

                // Redirect to the training show page so the driver can view the content
                return redirect()->route('driver.trainings.show', $assignment->id)
                    ->with('success', 'Training started successfully!');
            }
        } catch (\Exception $e) {
            Log::error('Error starting training', [
                'training_id' => $this->trainingId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('error', message: 'Error starting training');
        }
    }

    public function openCompletionModal()
    {
        $this->dispatch('openCompletionModal', trainingId: $this->trainingId);
    }

    public function getDueDateStatusProperty()
    {
        if (!$this->assignment->due_date) {
            return ['color' => 'slate', 'text' => 'No due date'];
        }

        $dueDate = Carbon::parse($this->assignment->due_date);
        $now = Carbon::now();
        $daysRemaining = $now->diffInDays($dueDate, false);

        if ($this->assignment->status === 'completed') {
            return ['color' => 'success', 'text' => 'Completed', 'days' => null];
        }

        if ($daysRemaining < 0) {
            return ['color' => 'danger', 'text' => 'Overdue', 'days' => abs($daysRemaining)];
        } elseif ($daysRemaining <= 3) {
            return ['color' => 'danger', 'text' => 'Due soon', 'days' => $daysRemaining];
        } elseif ($daysRemaining <= 7) {
            return ['color' => 'warning', 'text' => 'Due in ' . $daysRemaining . ' days', 'days' => $daysRemaining];
        } else {
            return ['color' => 'success', 'text' => 'Due in ' . $daysRemaining . ' days', 'days' => $daysRemaining];
        }
    }

    public function getContentTypeInfoProperty()
    {
        $contentType = $this->assignment->training->content_type ?? 'file';

        return match($contentType) {
            'video' => ['icon' => 'Video', 'label' => 'Video Training', 'color' => 'text-purple-600'],
            'url' => ['icon' => 'ExternalLink', 'label' => 'Online Training', 'color' => 'text-blue-600'],
            'file' => ['icon' => 'FileText', 'label' => 'Document Training', 'color' => 'text-green-600'],
            default => ['icon' => 'BookOpen', 'label' => 'Training', 'color' => 'text-slate-600'],
        };
    }

    public function render()
    {
        return view('livewire.driver.training-card');
    }
}

