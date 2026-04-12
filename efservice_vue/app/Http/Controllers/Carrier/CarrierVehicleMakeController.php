<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Vehicles\VehicleMakeController;
use App\Models\Admin\Vehicle\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CarrierVehicleMakeController extends VehicleMakeController
{
    public function index(Request $request): InertiaResponse
    {
        $response = parent::index($request);

        return Inertia::render('carrier/vehicles/makes/Index', [
            ...(fn () => $this->props)->call($response),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_makes,name'],
        ]);

        VehicleMake::create($validated);

        return redirect()->route('carrier.vehicle-makes.index')->with('success', 'Vehicle make created successfully.');
    }

    public function update(Request $request, VehicleMake $vehicle_make): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_makes,name,' . $vehicle_make->id],
        ]);

        $vehicle_make->update($validated);

        return redirect()->route('carrier.vehicle-makes.index')->with('success', 'Vehicle make updated successfully.');
    }

    public function destroy(VehicleMake $vehicle_make): RedirectResponse
    {
        if ($vehicle_make->vehicles()->exists()) {
            return redirect()->route('carrier.vehicle-makes.index')->with('error', 'This make cannot be deleted because it is currently assigned to one or more vehicles.');
        }

        $vehicle_make->delete();

        return redirect()->route('carrier.vehicle-makes.index')->with('success', 'Vehicle make deleted successfully.');
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.vehicle-makes.index',
            'store' => 'carrier.vehicle-makes.store',
            'update' => 'carrier.vehicle-makes.update',
            'destroy' => 'carrier.vehicle-makes.destroy',
        ];
    }
}
