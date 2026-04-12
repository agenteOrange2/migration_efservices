<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Vehicles\MaintenanceController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CarrierMaintenanceController extends MaintenanceController
{
    use ResolvesCarrierContext;

    public function index(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::index($request));

        return Inertia::render('carrier/maintenance/Index', $this->decorateProps($props));
    }

    public function create(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::create($request));

        return Inertia::render('carrier/maintenance/Create', $this->decorateProps($props));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        $this->authorizeVehicle($vehicle);

        $maintenance = DB::transaction(function () use ($request, $validated, $vehicle) {
            $maintenance = VehicleMaintenance::create($this->maintenancePayload($request, $validated, $vehicle));

            $this->storeAttachments($maintenance, $request);

            return $maintenance;
        });

        return redirect()
            ->route('carrier.maintenance.show', $maintenance)
            ->with('success', 'Maintenance record created successfully.');
    }

    public function show(VehicleMaintenance $maintenance): InertiaResponse
    {
        $props = $this->extractProps(parent::show($maintenance));

        return Inertia::render('carrier/maintenance/Show', $this->decorateProps($props));
    }

    public function edit(VehicleMaintenance $maintenance): InertiaResponse
    {
        $props = $this->extractProps(parent::edit($maintenance));

        return Inertia::render('carrier/maintenance/Edit', $this->decorateProps($props));
    }

    public function update(Request $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        $validated = $this->validatePayload($request, $maintenance);
        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        $this->authorizeVehicle($vehicle);

        DB::transaction(function () use ($request, $validated, $maintenance, $vehicle) {
            $maintenance->update($this->maintenancePayload($request, $validated, $vehicle));
            $this->storeAttachments($maintenance, $request);
        });

        return redirect()
            ->route('carrier.maintenance.show', $maintenance)
            ->with('success', 'Maintenance record updated successfully.');
    }

    public function destroy(VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        DB::transaction(function () use ($maintenance) {
            $maintenance->clearMediaCollection('maintenance_files');
            $maintenance->delete();
        });

        return redirect()
            ->route('carrier.maintenance.index')
            ->with('success', 'Maintenance record deleted successfully.');
    }

    public function calendar(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::calendar($request));
        $this->rewriteCalendarLinks($props);

        return Inertia::render('carrier/maintenance/Calendar', $this->decorateProps($props));
    }

    public function reports(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::reports($request));

        return Inertia::render('carrier/maintenance/Reports', $this->decorateProps($props));
    }

    public function vehicleIndex(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('carrier.maintenance.index', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function createForVehicle(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('carrier.maintenance.create', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    protected function maintenanceSummaryCard(VehicleMaintenance $maintenance): array
    {
        $payload = parent::maintenanceSummaryCard($maintenance);
        $payload['show_url'] = route('carrier.maintenance.show', $maintenance);

        return $payload;
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
            'index' => 'carrier.maintenance.index',
            'create' => 'carrier.maintenance.create',
            'store' => 'carrier.maintenance.store',
            'show' => 'carrier.maintenance.show',
            'edit' => 'carrier.maintenance.edit',
            'update' => 'carrier.maintenance.update',
            'destroy' => 'carrier.maintenance.destroy',
            'calendar' => 'carrier.maintenance.calendar',
            'reports' => 'carrier.maintenance.reports',
            'toggleStatus' => 'carrier.maintenance.toggle-status',
            'generateReport' => 'carrier.maintenance.generate-report',
            'deleteReport' => 'carrier.maintenance.delete-report',
            'attachmentsStore' => 'carrier.maintenance.attachments.store',
            'attachmentsDestroy' => 'carrier.maintenance.attachments.destroy',
            'reschedule' => 'carrier.maintenance.reschedule',
            'vehicleShow' => 'carrier.vehicles.show',
            'vehicleIndex' => 'carrier.vehicles.maintenance.index',
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

    private function rewriteCalendarLinks(array &$props): void
    {
        if (! isset($props['calendar']['days']) || ! is_array($props['calendar']['days'])) {
            return;
        }

        foreach ($props['calendar']['days'] as &$day) {
            if (! isset($day['items']) || ! is_array($day['items'])) {
                continue;
            }

            foreach ($day['items'] as &$item) {
                if (isset($item['id'])) {
                    $item['show_url'] = route('carrier.maintenance.show', $item['id']);
                }
            }
        }
    }
}
