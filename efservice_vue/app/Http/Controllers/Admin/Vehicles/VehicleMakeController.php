<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleMakeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $query = VehicleMake::query()->withCount('vehicles')->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $makes = $query->paginate(15)->withQueryString();
        $makes->through(fn (VehicleMake $make) => [
            'id' => $make->id,
            'name' => $make->name,
            'vehicles_count' => (int) $make->vehicles_count,
            'created_at' => $make->created_at?->format('n/j/Y'),
        ]);

        return Inertia::render('admin/vehicles/makes/Index', [
            'makes' => $makes,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'total' => VehicleMake::count(),
                'in_use' => VehicleMake::has('vehicles')->count(),
                'unused' => VehicleMake::doesntHave('vehicles')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_makes,name'],
        ]);

        VehicleMake::create($validated);

        return redirect()->route('admin.vehicle-makes.index')->with('success', 'Vehicle make created successfully.');
    }

    public function update(Request $request, VehicleMake $vehicle_make): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_makes,name,' . $vehicle_make->id],
        ]);

        $vehicle_make->update($validated);

        return redirect()->route('admin.vehicle-makes.index')->with('success', 'Vehicle make updated successfully.');
    }

    public function destroy(VehicleMake $vehicle_make): RedirectResponse
    {
        if ($vehicle_make->vehicles()->exists()) {
            return redirect()->route('admin.vehicle-makes.index')->with('error', 'This make cannot be deleted because it is currently assigned to one or more vehicles.');
        }

        $vehicle_make->delete();

        return redirect()->route('admin.vehicle-makes.index')->with('success', 'Vehicle make deleted successfully.');
    }
}
