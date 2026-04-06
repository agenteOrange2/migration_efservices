<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    public function index(): Response
    {
        $memberships = Membership::withCount('carriers')->get()->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'description' => $m->description,
            'pricing_type' => $m->pricing_type,
            'price' => $m->price,
            'carrier_price' => $m->carrier_price,
            'driver_price' => $m->driver_price,
            'vehicle_price' => $m->vehicle_price,
            'max_carrier' => $m->max_carrier,
            'max_drivers' => $m->max_drivers,
            'max_vehicles' => $m->max_vehicles,
            'status' => $m->status,
            'show_in_register' => $m->show_in_register,
            'carriers_count' => $m->carriers_count,
            'image_url' => $m->getFirstMediaUrl('image_membership') ?: null,
        ]);

        return Inertia::render('admin/memberships/Index', [
            'memberships' => $memberships,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/memberships/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:300',
            'pricing_type' => 'required|string|in:plan,individual',
            'max_carrier' => 'required|integer|min:1',
            'max_drivers' => 'required|integer|min:1',
            'max_vehicles' => 'required|integer|min:1',
            'image_membership' => 'nullable|image|max:2048',
            'status' => 'nullable|boolean',
            'show_in_register' => 'nullable|boolean',
        ];

        if ($request->input('pricing_type') === 'plan') {
            $rules['price'] = 'required|numeric|min:0';
        } else {
            $rules['carrier_price'] = 'required|numeric|min:0';
            $rules['driver_price'] = 'required|numeric|min:0';
            $rules['vehicle_price'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules);
        $validated['status'] = $request->boolean('status') ? 1 : 0;
        $validated['show_in_register'] = $request->boolean('show_in_register') ? 1 : 0;

        $membership = Membership::create($validated);

        if ($request->hasFile('image_membership')) {
            $fileName = strtolower(str_replace(' ', '_', $membership->name)) . '.webp';
            $membership->addMediaFromRequest('image_membership')
                ->usingFileName($fileName)
                ->toMediaCollection('image_membership');
        }

        return redirect()->route('admin.memberships.edit', $membership)
            ->with('success', 'Membership created successfully.');
    }

    public function show(Membership $membership): Response
    {
        $allCarriers = $membership->carriers()
            ->with(['users:users.id,email', 'media'])
            ->withCount(['userDrivers', 'vehicles'])
            ->get();

        $stats = [
            'total_carriers' => $allCarriers->count(),
            'active_carriers' => $allCarriers->where('status', 1)->count(),
            'total_drivers' => (int) $allCarriers->sum('user_drivers_count'),
            'total_vehicles' => (int) $allCarriers->sum('vehicles_count'),
        ];

        $carriers = $membership->carriers()
            ->with(['users:users.id,email', 'media'])
            ->withCount(['userDrivers', 'vehicles'])
            ->select(['id', 'name', 'slug', 'mc_number', 'status', 'id_plan', 'created_at'])
            ->paginate(10)
            ->through(fn ($carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'slug' => $carrier->slug,
                'mc_number' => $carrier->mc_number,
                'status' => $carrier->status,
                'drivers_count' => $carrier->user_drivers_count,
                'vehicles_count' => $carrier->vehicles_count,
                'contact_email' => $carrier->users->first()?->email,
                'logo_url' => $carrier->getFirstMediaUrl('logo_carrier') ?: null,
            ]);

        $membershipData = $membership->only([
            'id', 'name', 'description', 'pricing_type', 'price',
            'carrier_price', 'driver_price', 'vehicle_price',
            'max_carrier', 'max_drivers', 'max_vehicles',
            'status', 'show_in_register', 'created_at', 'updated_at',
        ]);
        $membershipData['image_url'] = $membership->getFirstMediaUrl('image_membership') ?: null;

        return Inertia::render('admin/memberships/Show', [
            'membership' => $membershipData,
            'carriers' => $carriers,
            'stats' => $stats,
        ]);
    }

    public function edit(Membership $membership): Response
    {
        $membershipData = $membership->only([
            'id', 'name', 'description', 'pricing_type', 'price',
            'carrier_price', 'driver_price', 'vehicle_price',
            'max_carrier', 'max_drivers', 'max_vehicles',
            'status', 'show_in_register', 'created_at', 'updated_at',
        ]);
        $membershipData['image_url'] = $membership->getFirstMediaUrl('image_membership') ?: null;

        return Inertia::render('admin/memberships/Edit', [
            'membership' => $membershipData,
        ]);
    }

    public function update(Request $request, Membership $membership): RedirectResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:300',
            'pricing_type' => 'required|string|in:plan,individual',
            'max_carrier' => 'required|integer|min:1',
            'max_drivers' => 'required|integer|min:1',
            'max_vehicles' => 'required|integer|min:1',
            'image_membership' => 'nullable|image|max:2048',
            'status' => 'nullable|boolean',
            'show_in_register' => 'nullable|boolean',
        ];

        if ($request->input('pricing_type') === 'plan') {
            $rules['price'] = 'required|numeric|min:0';
        } else {
            $rules['carrier_price'] = 'required|numeric|min:0';
            $rules['driver_price'] = 'required|numeric|min:0';
            $rules['vehicle_price'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules);
        $validated['status'] = $request->boolean('status') ? 1 : 0;
        $validated['show_in_register'] = $request->boolean('show_in_register') ? 1 : 0;

        if ($request->hasFile('image_membership')) {
            $fileName = strtolower(str_replace(' ', '_', $membership->name)) . '.webp';
            $membership->clearMediaCollection('image_membership');
            $membership->addMediaFromRequest('image_membership')
                ->usingFileName($fileName)
                ->toMediaCollection('image_membership');
        }

        $membership->update($validated);

        return redirect()->route('admin.memberships.edit', $membership)
            ->with('success', 'Membership updated successfully.');
    }

    public function destroy(Membership $membership): RedirectResponse
    {
        $membership->delete();
        return redirect()->route('admin.memberships.index')
            ->with('success', 'Membership deleted successfully.');
    }
}
