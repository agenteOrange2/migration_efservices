<?php

namespace App\Observers;

use App\Models\Carrier;
use App\Services\CacheInvalidationService;
use App\Services\CarrierDocumentService;
use Illuminate\Support\Facades\Log;

class CarrierObserver
{
    protected $documentService;

    public function __construct(CarrierDocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Handle the Carrier "created" event.
     */
    public function created(Carrier $carrier): void
    {
        // Invalidar caché
        CacheInvalidationService::invalidateCarrierCache($carrier->id);
        
        // Generar documentos base automáticamente
        try {
            Log::info('CarrierObserver: Generating base documents for new carrier', [
                'carrier_id' => $carrier->id,
                'carrier_name' => $carrier->name
            ]);
            
            $this->documentService->generateBaseDocuments($carrier);
            
            Log::info('CarrierObserver: Base documents generated successfully', [
                'carrier_id' => $carrier->id
            ]);
        } catch (\Exception $e) {
            Log::error('CarrierObserver: Error generating base documents', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Carrier "updated" event.
     */
    public function updated(Carrier $carrier): void
    {
        CacheInvalidationService::invalidateCarrierCache($carrier->id);
    }

    /**
     * Handle the Carrier "deleted" event.
     */
    public function deleted(Carrier $carrier): void
    {
        CacheInvalidationService::invalidateCarrierCache($carrier->id);
    }

    /**
     * Handle the Carrier "restored" event.
     */
    public function restored(Carrier $carrier): void
    {
        CacheInvalidationService::invalidateCarrierCache($carrier->id);
    }

    /**
     * Handle the Carrier "force deleted" event.
     */
    public function forceDeleted(Carrier $carrier): void
    {
        CacheInvalidationService::invalidateCarrierCache($carrier->id);
    }
}
