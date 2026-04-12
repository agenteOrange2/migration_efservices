<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Admin\Vehicles\EmergencyRepairController;
use App\Http\Controllers\Driver\Concerns\ResolvesDriverVehicleContext;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\EmergencyRepair;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DriverEmergencyRepairController extends EmergencyRepairController
{
    use ResolvesDriverVehicleContext;

    public function index(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::index($request));

        return Inertia::render('driver/emergency-repairs/Index', $this->decorateProps($props));
    }

    public function create(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::create($request));

        return Inertia::render('driver/emergency-repairs/Create', $this->decorateProps($props));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        $this->authorizeVehicle($vehicle);

        $repair = DB::transaction(function () use ($request, $validated, $vehicle) {
            $repair = EmergencyRepair::create($this->repairPayload($validated, $vehicle));
            $this->storeAttachments($repair, $request);

            return $repair;
        });

        return redirect()
            ->route('driver.emergency-repairs.show', $repair)
            ->with('success', 'Emergency repair created successfully.');
    }

    public function show(EmergencyRepair $emergencyRepair): InertiaResponse
    {
        $props = $this->extractProps(parent::show($emergencyRepair));

        return Inertia::render('driver/emergency-repairs/Show', $this->decorateProps($props));
    }

    public function edit(EmergencyRepair $emergencyRepair): InertiaResponse
    {
        $props = $this->extractProps(parent::edit($emergencyRepair));

        return Inertia::render('driver/emergency-repairs/Edit', $this->decorateProps($props));
    }

    public function update(Request $request, EmergencyRepair $emergencyRepair): RedirectResponse
    {
        $emergencyRepair->load('vehicle');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        $validated = $this->validatePayload($request);
        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        $this->authorizeVehicle($vehicle);

        DB::transaction(function () use ($request, $validated, $emergencyRepair, $vehicle) {
            $emergencyRepair->update($this->repairPayload($validated, $vehicle));
            $this->storeAttachments($emergencyRepair, $request);
        });

        return redirect()
            ->route('driver.emergency-repairs.show', $emergencyRepair)
            ->with('success', 'Emergency repair updated successfully.');
    }

    public function destroy(EmergencyRepair $emergencyRepair): RedirectResponse
    {
        $emergencyRepair->load('vehicle');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        DB::transaction(function () use ($emergencyRepair) {
            $emergencyRepair->clearMediaCollection('emergency_repair_files');
            $emergencyRepair->delete();
        });

        return redirect()
            ->route('driver.emergency-repairs.index')
            ->with('success', 'Emergency repair deleted successfully.');
    }

    public function vehicleIndex(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('driver.emergency-repairs.index', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function createForVehicle(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('driver.emergency-repairs.create', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    protected function repairBaseQuery(?int $carrierId, ?int $vehicleId = null): Builder
    {
        $query = parent::repairBaseQuery($carrierId, null)
            ->whereIn('vehicle_id', $this->accessibleVehicleIds());

        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }

        return $query;
    }

    protected function vehicleOptions(?int $carrierId = null): Collection
    {
        return $this->resolveAccessibleVehicles()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'carrier_id' => $vehicle->carrier_id,
                'carrier_name' => $vehicle->carrier?->name,
                'label' => $this->vehicleLabel($vehicle),
            ])
            ->values();
    }

    protected function authorizeVehicle(Vehicle $vehicle): void
    {
        $this->authorizeVehicleAccess($this->resolveDriver(), $vehicle);
    }

    protected function isSuperadmin(): bool
    {
        return false;
    }

    protected function currentCarrierId(): ?int
    {
        $driver = $this->resolveDriver();

        if ($driver->carrier_id) {
            return (int) $driver->carrier_id;
        }

        return $this->resolvePrimaryVehicle($driver)?->carrier_id
            ? (int) $this->resolvePrimaryVehicle($driver)?->carrier_id
            : null;
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'driver.emergency-repairs.index',
            'create' => 'driver.emergency-repairs.create',
            'store' => 'driver.emergency-repairs.store',
            'show' => 'driver.emergency-repairs.show',
            'edit' => 'driver.emergency-repairs.edit',
            'update' => 'driver.emergency-repairs.update',
            'destroy' => 'driver.emergency-repairs.destroy',
            'attachmentsStore' => 'driver.emergency-repairs.attachments.store',
            'attachmentsDestroy' => 'driver.emergency-repairs.attachments.destroy',
            'generateReport' => 'driver.emergency-repairs.generate-report',
            'deleteReport' => 'driver.emergency-repairs.delete-report',
            'vehicleShow' => 'driver.vehicles.show',
            'vehicleIndex' => 'driver.vehicles.repairs.index',
        ];
    }

    protected function driverPayload(): array
    {
        $driver = $this->resolveDriver();

        return [
            'id' => $driver->id,
            'full_name' => $driver->full_name,
            'carrier_id' => $driver->carrier_id,
        ];
    }

    private function decorateProps(array $props): array
    {
        return [
            ...$props,
            'driver' => $this->driverPayload(),
            'routeNames' => $this->routeNames(),
        ];
    }

    private function extractProps(InertiaResponse $response): array
    {
        return (fn () => $this->props)->call($response);
    }
}
