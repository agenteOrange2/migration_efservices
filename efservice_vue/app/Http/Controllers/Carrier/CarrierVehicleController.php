<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Vehicles\VehicleController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CarrierVehicleController extends VehicleController
{
    use ResolvesCarrierContext;

    public function index(Request $request): InertiaResponse
    {
        $response = parent::index($request);

        return Inertia::render('carrier/vehicles/Index', [
            ...(fn() => $this->props)->call($response),
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $response = parent::create($request);

        return Inertia::render('carrier/vehicles/Create', [
            ...(fn() => $this->props)->call($response),
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $this->ensureCarrierAccess((int) $validated['carrier_id']);
        $this->ensureDriverBelongsToCarrier($validated);

        $vehicle = null;

        DB::transaction(function () use ($request, $validated, &$vehicle) {
            $vehicle = Vehicle::create($this->vehiclePayload($request, $validated));
            $this->syncCatalogs($vehicle);
            $this->syncAssignment($vehicle, $request, $validated);
        });

        return redirect()
            ->route('carrier.vehicles.show', $vehicle)
            ->with('success', 'Vehicle created successfully.');
    }

    public function show(Vehicle $vehicle): InertiaResponse
    {
        $response = parent::show($vehicle);

        return Inertia::render('carrier/vehicles/Show', [
            ...(fn() => $this->props)->call($response),
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function edit(Vehicle $vehicle): InertiaResponse
    {
        $response = parent::edit($vehicle);
        $props = (fn() => $this->props)->call($response);

        if (isset($props['vehicle']) && is_array($props['vehicle'])) {
            $props['vehicle']['documents_url'] = route('carrier.vehicles.documents.index', $vehicle);
            $props['vehicle']['history_url'] = route('carrier.vehicles.driver-assignment-history', $vehicle);
        }

        return Inertia::render('carrier/vehicles/Edit', [
            ...$props,
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        $validated = $this->validatePayload($request, $vehicle);
        $this->ensureCarrierAccess((int) $validated['carrier_id']);
        $this->ensureDriverBelongsToCarrier($validated);

        DB::transaction(function () use ($request, $validated, $vehicle) {
            $vehicle->update($this->vehiclePayload($request, $validated));
            $this->syncCatalogs($vehicle);
            $this->syncAssignment($vehicle, $request, $validated);
        });

        return redirect()
            ->route('carrier.vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        DB::transaction(function () use ($vehicle) {
            $vehicle->driverAssignments()->each(function ($assignment) {
                $assignment->ownerOperatorDetail()?->delete();
                $assignment->thirdPartyDetail()?->delete();
                $assignment->companyDriverDetail()?->delete();
            });

            $vehicle->delete();
        });

        return redirect()
            ->route('carrier.vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    public function driverAssignmentHistory(Vehicle $vehicle, Request $request): InertiaResponse
    {
        $response = parent::driverAssignmentHistory($vehicle, $request);

        return Inertia::render('carrier/vehicles/AssignmentHistory', [
            ...(fn() => $this->props)->call($response),
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    protected function isSuperadmin(): bool
    {
        return false;
    }

    protected function currentCarrierId(): ?int
    {
        return $this->resolveCarrierId();
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.vehicles.index',
            'create' => 'carrier.vehicles.create',
            'store' => 'carrier.vehicles.store',
            'show' => 'carrier.vehicles.show',
            'edit' => 'carrier.vehicles.edit',
            'update' => 'carrier.vehicles.update',
            'destroy' => 'carrier.vehicles.destroy',
            'assignmentHistory' => 'carrier.vehicles.driver-assignment-history',
            'documentsOverview' => 'carrier.vehicles-documents.index',
            'documentsIndex' => 'carrier.vehicles.documents.index',
            'documentsStore' => 'carrier.vehicles.documents.store',
            'documentsUpdate' => 'carrier.vehicles.documents.update',
            'documentsDestroy' => 'carrier.vehicles.documents.destroy',
            'maintenanceIndexByVehicle' => 'carrier.vehicles.maintenance.index',
            'maintenanceCreateByVehicle' => 'carrier.vehicles.maintenance.create',
            'maintenanceShow' => 'carrier.maintenance.show',
            'repairsIndexByVehicle' => 'carrier.vehicles.repairs.index',
            'repairsCreateByVehicle' => 'carrier.vehicles.repairs.create',
            'repairsShow' => 'carrier.emergency-repairs.show',
        ];
    }

    protected function carrierOption(): array
    {
        $carrier = $this->resolveCarrier();

        return [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ];
    }
}
