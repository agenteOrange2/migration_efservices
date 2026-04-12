<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Vehicles\EmergencyRepairController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\EmergencyRepair;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CarrierEmergencyRepairController extends EmergencyRepairController
{
    use ResolvesCarrierContext;

    public function index(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::index($request));

        return Inertia::render('carrier/emergency-repairs/Index', $this->decorateProps($props));
    }

    public function create(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::create($request));

        return Inertia::render('carrier/emergency-repairs/Create', $this->decorateProps($props));
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
            ->route('carrier.emergency-repairs.show', $repair)
            ->with('success', 'Emergency repair created successfully.');
    }

    public function show(EmergencyRepair $emergencyRepair): InertiaResponse
    {
        $props = $this->extractProps(parent::show($emergencyRepair));

        return Inertia::render('carrier/emergency-repairs/Show', $this->decorateProps($props));
    }

    public function edit(EmergencyRepair $emergencyRepair): InertiaResponse
    {
        $props = $this->extractProps(parent::edit($emergencyRepair));

        return Inertia::render('carrier/emergency-repairs/Edit', $this->decorateProps($props));
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
            ->route('carrier.emergency-repairs.show', $emergencyRepair)
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
            ->route('carrier.emergency-repairs.index')
            ->with('success', 'Emergency repair deleted successfully.');
    }

    public function vehicleIndex(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('carrier.emergency-repairs.index', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function createForVehicle(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('carrier.emergency-repairs.create', [
            'vehicle_id' => $vehicle->id,
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
            'index' => 'carrier.emergency-repairs.index',
            'create' => 'carrier.emergency-repairs.create',
            'store' => 'carrier.emergency-repairs.store',
            'show' => 'carrier.emergency-repairs.show',
            'edit' => 'carrier.emergency-repairs.edit',
            'update' => 'carrier.emergency-repairs.update',
            'destroy' => 'carrier.emergency-repairs.destroy',
            'attachmentsStore' => 'carrier.emergency-repairs.attachments.store',
            'attachmentsDestroy' => 'carrier.emergency-repairs.attachments.destroy',
            'generateReport' => 'carrier.emergency-repairs.generate-report',
            'deleteReport' => 'carrier.emergency-repairs.delete-report',
            'vehicleShow' => 'carrier.vehicles.show',
            'vehicleIndex' => 'carrier.vehicles.repairs.index',
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

    private function decorateProps(array $props): array
    {
        return [
            ...$props,
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ];
    }

    private function extractProps(InertiaResponse $response): array
    {
        return (fn () => $this->props)->call($response);
    }
}
