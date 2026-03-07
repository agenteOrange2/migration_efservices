<?php

namespace App\Observers;

use App\Models\UserDriverDetail;
use App\Services\CacheInvalidationService;

class UserDriverDetailObserver
{
    /**
     * Handle the UserDriverDetail "created" event.
     */
    public function created(UserDriverDetail $userDriverDetail): void
    {
        CacheInvalidationService::invalidateDriverDetailCache(
            $userDriverDetail->id,
            $userDriverDetail->carrier_id
        );
    }

    /**
     * Handle the UserDriverDetail "updated" event.
     */
    public function updated(UserDriverDetail $userDriverDetail): void
    {
        CacheInvalidationService::invalidateDriverDetailCache(
            $userDriverDetail->id,
            $userDriverDetail->carrier_id
        );
        
        // If carrier_id changed, invalidate the old carrier's cache too
        if ($userDriverDetail->isDirty('carrier_id')) {
            $originalCarrierId = $userDriverDetail->getOriginal('carrier_id');
            if ($originalCarrierId) {
                CacheInvalidationService::invalidateDriverDetailCache(
                    $userDriverDetail->id,
                    $originalCarrierId
                );
            }
        }
    }

    /**
     * Handle the UserDriverDetail "deleted" event.
     */
    public function deleted(UserDriverDetail $userDriverDetail): void
    {
        CacheInvalidationService::invalidateDriverDetailCache(
            $userDriverDetail->id,
            $userDriverDetail->carrier_id
        );
    }

    /**
     * Handle the UserDriverDetail "restored" event.
     */
    public function restored(UserDriverDetail $userDriverDetail): void
    {
        CacheInvalidationService::invalidateDriverDetailCache(
            $userDriverDetail->id,
            $userDriverDetail->carrier_id
        );
    }

    /**
     * Handle the UserDriverDetail "force deleted" event.
     */
    public function forceDeleted(UserDriverDetail $userDriverDetail): void
    {
        CacheInvalidationService::invalidateDriverDetailCache(
            $userDriverDetail->id,
            $userDriverDetail->carrier_id
        );
    }
}
