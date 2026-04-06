<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $query = VehicleType::query()->withCount('vehicles')->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $types = $query->paginate(15)->withQueryString();
        $types->through(fn (VehicleType $type) => [
            'id' => $type->id,
            'name' => $type->name,
            'vehicles_count' => (int) $type->vehicles_count,
            'created_at' => $type->created_at?->format('n/j/Y'),
        ]);

        return Inertia::render('admin/vehicles/types/Index', [
            'types' => $types,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'total' => VehicleType::count(),
                'in_use' => VehicleType::has('vehicles')->count(),
                'unused' => VehicleType::doesntHave('vehicles')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_types,name'],
        ]);

        VehicleType::create($validated);

        return redirect()->route('admin.vehicle-types.index')->with('success', 'Vehicle type created successfully.');
    }

    public function update(Request $request, VehicleType $vehicle_type): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_types,name,' . $vehicle_type->id],
        ]);

        $vehicle_type->update($validated);

        return redirect()->route('admin.vehicle-types.index')->with('success', 'Vehicle type updated successfully.');
    }

    public function destroy(VehicleType $vehicle_type): RedirectResponse
    {
        if ($vehicle_type->vehicles()->exists()) {
            return redirect()->route('admin.vehicle-types.index')->with('error', 'This type cannot be deleted because it is currently assigned to one or more vehicles.');
        }

        $vehicle_type->delete();

        return redirect()->route('admin.vehicle-types.index')->with('success', 'Vehicle type deleted successfully.');
    }
}
