<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Vehicles\VehicleTypeController;
use App\Models\Admin\Vehicle\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CarrierVehicleTypeController extends VehicleTypeController
{
    public function index(Request $request): InertiaResponse
    {
        $response = parent::index($request);

        return Inertia::render('carrier/vehicles/types/Index', [
            ...(fn () => $this->props)->call($response),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_types,name'],
        ]);

        VehicleType::create($validated);

        return redirect()->route('carrier.vehicle-types.index')->with('success', 'Vehicle type created successfully.');
    }

    public function update(Request $request, VehicleType $vehicle_type): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_types,name,' . $vehicle_type->id],
        ]);

        $vehicle_type->update($validated);

        return redirect()->route('carrier.vehicle-types.index')->with('success', 'Vehicle type updated successfully.');
    }

    public function destroy(VehicleType $vehicle_type): RedirectResponse
    {
        if ($vehicle_type->vehicles()->exists()) {
            return redirect()->route('carrier.vehicle-types.index')->with('error', 'This type cannot be deleted because it is currently assigned to one or more vehicles.');
        }

        $vehicle_type->delete();

        return redirect()->route('carrier.vehicle-types.index')->with('success', 'Vehicle type deleted successfully.');
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.vehicle-types.index',
            'store' => 'carrier.vehicle-types.store',
            'update' => 'carrier.vehicle-types.update',
            'destroy' => 'carrier.vehicle-types.destroy',
        ];
    }
}
