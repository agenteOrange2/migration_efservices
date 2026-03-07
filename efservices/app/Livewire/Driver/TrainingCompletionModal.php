<?php

namespace App\Livewire\Driver;

use Livewire\Component;
use App\Models\Admin\Driver\DriverTraining;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TrainingCompletionModal extends Component
{
    public $trainingId;
    public $confirmed = false;
    public $notes = '';
    public $assignment;

    protected $rules = [
        'confirmed' => 'required|accepted',
        'notes' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'confirmed.accepted' => 'You must confirm that you have completed this training.',
    ];

    protected $listeners = [
        'openCompletionModal' => 'openModal',
    ];

    public function openModal($trainingId)
    {
        $this->trainingId = $trainingId;
        $this->loadAssignment();
        $this->resetForm();
    }

    public function loadAssignment()
    {
        try {
            $user = Auth::user();
            $driverDetail = $user->driverDetail;
            
            if (!$driverDetail) {
                return;
            }

            $this->assignment = DriverTraining::where('id', $this->trainingId)
                ->where('user_driver_detail_id', $driverDetail->id)
                ->with('training')
                ->first();
        } catch (\Exception $e) {
            Log::error('Error loading assignment for completion modal', [
                'training_id' => $this->trainingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function resetForm()
    {
        $this->confirmed = false;
        $this->notes = '';
        $this->resetValidation();
    }

    public function completeTraining()
    {
        $this->validate();

        try {
            $user = Auth::user();
            $driverDetail = $user->driverDetail;
            
            if (!$driverDetail) {
                $this->addError('general', 'Driver information not found');
                return;
            }

            $assignment = DriverTraining::where('id', $this->trainingId)
                ->where('user_driver_detail_id', $driverDetail->id)
                ->firstOrFail();

            // Validate that training is not already completed
            if ($assignment->status === 'completed') {
                $this->addError('general', 'This training has already been completed');
                return;
            }

            // Mark as completed
            $assignment->status = 'completed';
            $assignment->completed_date = now();
            $assignment->completion_notes = $this->notes;
            $assignment->save();

            $this->dispatch('trainingCompleted');
            $this->dispatch('success', message: 'Training completed successfully!');
            
        } catch (\Exception $e) {
            Log::error('Error completing training', [
                'training_id' => $this->trainingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('general', 'Error completing training. Please try again.');
        }
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->dispatch('closeModal');
    }

    public function render()
    {
        return view('livewire.driver.training-completion-modal');
    }
}

