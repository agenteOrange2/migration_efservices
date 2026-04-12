<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Admin\Vehicles\MaintenanceController;
use App\Http\Controllers\Driver\Concerns\ResolvesDriverVehicleContext;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DriverMaintenanceController extends MaintenanceController
{
    use ResolvesDriverVehicleContext;

    public function index(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::index($request));

        return Inertia::render('driver/maintenance/Index', $this->decorateProps($props));
    }

    public function create(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::create($request));

        return Inertia::render('driver/maintenance/Create', $this->decorateProps($props));
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
            ->route('driver.maintenance.show', $maintenance)
            ->with('success', 'Maintenance record created successfully.');
    }

    public function show(VehicleMaintenance $maintenance): InertiaResponse
    {
        $props = $this->extractProps(parent::show($maintenance));

        return Inertia::render('driver/maintenance/Show', $this->decorateProps($props));
    }

    public function edit(VehicleMaintenance $maintenance): InertiaResponse
    {
        $props = $this->extractProps(parent::edit($maintenance));

        return Inertia::render('driver/maintenance/Edit', $this->decorateProps($props));
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
            ->route('driver.maintenance.show', $maintenance)
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
            ->route('driver.maintenance.index')
            ->with('success', 'Maintenance record deleted successfully.');
    }

    public function calendar(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::calendar($request));
        $this->rewriteCalendarLinks($props);

        return Inertia::render('driver/maintenance/Calendar', $this->decorateProps($props));
    }

    public function reports(Request $request): InertiaResponse
    {
        $props = $this->extractProps(parent::reports($request));

        return Inertia::render('driver/maintenance/Reports', $this->decorateProps($props));
    }

    public function vehicleIndex(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('driver.maintenance.index', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function createForVehicle(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('driver.maintenance.create', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    protected function maintenanceBaseQuery(?int $carrierId, ?int $vehicleId = null): Builder
    {
        $query = parent::maintenanceBaseQuery($carrierId, null)
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

    protected function maintenanceSummaryCard(VehicleMaintenance $maintenance): array
    {
        $payload = parent::maintenanceSummaryCard($maintenance);
        $payload['show_url'] = route('driver.maintenance.show', $maintenance);

        return $payload;
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
            'index' => 'driver.maintenance.index',
            'create' => 'driver.maintenance.create',
            'store' => 'driver.maintenance.store',
            'show' => 'driver.maintenance.show',
            'edit' => 'driver.maintenance.edit',
            'update' => 'driver.maintenance.update',
            'destroy' => 'driver.maintenance.destroy',
            'calendar' => 'driver.maintenance.calendar',
            'reports' => 'driver.maintenance.reports',
            'toggleStatus' => 'driver.maintenance.toggle-status',
            'generateReport' => 'driver.maintenance.generate-report',
            'deleteReport' => 'driver.maintenance.delete-report',
            'attachmentsStore' => 'driver.maintenance.attachments.store',
            'attachmentsDestroy' => 'driver.maintenance.attachments.destroy',
            'reschedule' => 'driver.maintenance.reschedule',
            'vehicleShow' => 'driver.vehicles.show',
            'vehicleIndex' => 'driver.vehicles.maintenance.index',
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
                    $item['show_url'] = route('driver.maintenance.show', $item['id']);
                }
            }
        }
    }
}
