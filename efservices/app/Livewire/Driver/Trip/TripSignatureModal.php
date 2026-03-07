<?php

namespace App\Livewire\Driver\Trip;

use Livewire\Component;
use App\Models\Trip;
use App\Services\Hos\HosPdfService;
use Illuminate\Support\Facades\Auth;

class TripSignatureModal extends Component
{
    public Trip $trip;
    public bool $showModal = false;
    public bool $autoOpen = false;

    public function mount(Trip $trip, bool $autoOpen = false)
    {
        $this->trip = $trip;
        $this->autoOpen = $autoOpen;
    }

    #[\Livewire\Attributes\On('openTripSignatureModal')]
    public function openModal()
    {
        $this->showModal = true;
    }
    
    #[\Livewire\Attributes\On('saveTripSignatureData')]
    public function handleSignatureEvent($signatureData = null)
    {
        $this->saveSignature($signatureData);
    }

    public function closeModal()
    {
        $this->showModal = false;
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
    
    private function processSignature(string $signatureData)
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

        // Verify this is the driver's trip
        if ($this->trip->user_driver_detail_id != $driver->id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to sign this trip.',
            ]);
            return;
        }

        try {
            // Generate PDF with signature
            $pdfService = app(HosPdfService::class);
            $pdfPath = $pdfService->generateTripReport($this->trip, $signatureData);

            $this->closeModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Trip reports signed and PDFs generated successfully (Trip Summary, Pre-Trip Inspection, Post-Trip Inspection).',
            ]);

            $this->dispatch('tripSigned');
            
            // Refresh the page to show the PDF download button
            $this->redirect(route('driver.trips.show', $this->trip), navigate: false);
        } catch (\Exception $e) {
            \Log::error('Trip signature error: ' . $e->getMessage());
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to save signature and generate PDF. Please try again.',
            ]);
        }
    }

    public function skipSignature()
    {
        try {
            // Generate PDF without signature
            $pdfService = app(HosPdfService::class);
            $pdfPath = $pdfService->generateTripReport($this->trip);

            $this->closeModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Trip reports generated without signature (Trip Summary, Pre-Trip Inspection, Post-Trip Inspection).',
            ]);

            // Refresh the page to show the PDF download button
            $this->redirect(route('driver.trips.show', $this->trip), navigate: false);
        } catch (\Exception $e) {
            \Log::error('Trip PDF generation error: ' . $e->getMessage());
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to generate PDF. Please try again.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.driver.trip.trip-signature-modal');
    }
}
