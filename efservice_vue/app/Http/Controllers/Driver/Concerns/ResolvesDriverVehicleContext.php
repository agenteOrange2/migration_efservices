<?php

namespace App\Http\Controllers\Driver\Concerns;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\UserDriverDetail;
use Illuminate\Support\Collection;

trait ResolvesDriverVehicleContext
{
    protected function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        return $driver;
    }

    protected function resolveAccessibleVehicles(?UserDriverDetail $driver = null): Collection
    {
        $driver ??= $this->resolveDriver();

        $driver->loadMissing([
            'vehicles.carrier:id,name',
            'assignedVehicle.carrier:id,name',
            'activeVehicleAssignment.vehicle.carrier:id,name',
        ]);

        $vehicles = collect();

        if ($driver->activeVehicleAssignment?->vehicle) {
            $vehicles->push($driver->activeVehicleAssignment->vehicle);
        }

        if ($driver->assignedVehicle) {
            $vehicles->push($driver->assignedVehicle);
        }

        if ($driver->vehicles && $driver->vehicles->count() > 0) {
            $vehicles = $vehicles->merge($driver->vehicles);
        }

        return $vehicles
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function resolvePrimaryVehicle(?UserDriverDetail $driver = null): ?Vehicle
    {
        return $this->resolveAccessibleVehicles($driver)->first();
    }

    protected function accessibleVehicleIds(?UserDriverDetail $driver = null): array
    {
        return $this->resolveAccessibleVehicles($driver)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function authorizeVehicleAccess(UserDriverDetail $driver, Vehicle $vehicle): void
    {
        abort_unless(
            in_array((int) $vehicle->id, $this->accessibleVehicleIds($driver), true),
            403,
            'Unauthorized access to this vehicle.'
        );
    }
}
