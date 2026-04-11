<?php

namespace App\Http\Controllers\Carrier\Concerns;

use App\Models\Carrier;

trait ResolvesCarrierContext
{
    protected function resolveCarrierId(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $user->loadMissing(['carrierDetails', 'carriers']);

        if ($user->carrierDetails?->carrier_id) {
            return (int) $user->carrierDetails->carrier_id;
        }

        if ($user->carriers && $user->carriers->isNotEmpty()) {
            return (int) $user->carriers->first()->id;
        }

        return null;
    }

    protected function resolveCarrier(): Carrier
    {
        $carrierId = $this->resolveCarrierId();

        abort_unless($carrierId, 403, 'No carrier associated with this account.');

        return Carrier::query()->findOrFail($carrierId);
    }
}
