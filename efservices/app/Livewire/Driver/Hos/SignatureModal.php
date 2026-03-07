<?php

namespace App\Livewire\Driver\Hos;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Hos\HosDailyLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SignatureModal extends Component
{
    public $showModal = false;
    public $signatureData = null;
    public $date;

    protected $listeners = [
        'openSignatureModal' => 'openModal',
        'saveSignatureData' => 'saveSignature',
    ];

    public function mount($date = null)
    {
        $this->date = $date ?? Carbon::today()->format('Y-m-d');
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->signatureData = null;
    }

    public function saveSignature($signatureData = null)
    {
        // Handle both direct call and event dispatch
        if (is_array($signatureData) && isset($signatureData['signatureData'])) {
            $signatureData = $signatureData['signatureData'];
        }
        
        if (!$signatureData) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No signature data received.',
            ]);
            return;
        }
        
        $this->processSignature($signatureData);
    }
    
    private function processSignature($signatureData)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Driver profile not found.',
            ]);
            return;
        }

        try {
            // Decode base64 signature
            $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
            $signatureData = str_replace(' ', '+', $signatureData);
            $imageData = base64_decode($signatureData);

            // Generate filename
            $filename = "signatures/driver_{$driver->id}_{$this->date}.png";

            // Store signature
            Storage::disk('public')->put($filename, $imageData);

            // Get or create daily log
            $dailyLog = HosDailyLog::forDriver($driver->id)
                ->whereDate('date', $this->date)
                ->first();

            if (!$dailyLog) {
                // Create daily log if it doesn't exist
                $dailyLog = HosDailyLog::create([
                    'user_driver_detail_id' => $driver->id,
                    'carrier_id' => $driver->carrier_id,
                    'date' => $this->date,
                    'total_driving_minutes' => 0,
                    'total_on_duty_minutes' => 0,
                    'total_off_duty_minutes' => 0,
                ]);
            }

            // Update signature
            $dailyLog->update([
                'driver_signature' => $filename,
                'signed_at' => Carbon::now(),
            ]);

            $this->closeModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Signature saved successfully.',
            ]);

            $this->dispatch('signatureSaved');
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to save signature. Please try again.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.driver.hos.signature-modal');
    }
}
