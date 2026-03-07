<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('roles:id,name')
            ->select(['id', 'name', 'email', 'status', 'created_at', 'updated_at']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15))
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $user->roles->pluck('name')->toArray(),
                'profile_photo_url' => $user->getFirstMediaUrl('profile_photos') ?: null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);

        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 1)->count(),
            'inactive' => User::where('status', 0)->count(),
        ];

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'role', 'per_page']),
            'stats' => $stats,
            'roles' => Role::select('id', 'name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/Create', [
            'roles' => Role::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'status' => 'required|boolean',
            'profile_photo' => 'nullable|image|max:2048',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->status = $validated['status'] ? 1 : 0;
            $user->save();

            if (!empty($validated['roles'])) {
                $roles = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
                $user->assignRole($roles);
            } else {
                $user->assignRole('superadmin');
            }

            if ($request->hasFile('profile_photo')) {
                $fileName = strtolower(str_replace(' ', '_', $user->name)) . '.webp';
                $user->addMediaFromRequest('profile_photo')
                    ->usingFileName($fileName)
                    ->toMediaCollection('profile_photos');
            }

            Log::info('User created by admin', [
                'new_user_id' => $user->id,
                'admin_user_id' => auth()->id(),
            ]);

            return redirect()->route('admin.users.edit', $user)
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error creating user: ' . $e->getMessage());
        }
    }

    public function show(User $user): Response
    {
        $user->load('roles:id,name');

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles->map(fn ($r) => $r->only(['id', 'name']))->values(),
            'profile_photo_url' => $user->getFirstMediaUrl('profile_photos') ?: null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        $carrierDetail = $user->carrierDetails;
        $driverDetail = $user->driverDetails;

        return Inertia::render('admin/users/Show', [
            'user' => $userData,
            'carrierInfo' => $carrierDetail ? [
                'carrier_id' => $carrierDetail->carrier_id,
                'carrier_name' => $carrierDetail->carrier?->name,
                'phone' => $carrierDetail->phone,
                'job_position' => $carrierDetail->job_position,
            ] : null,
            'driverInfo' => $driverDetail ? [
                'carrier_name' => $driverDetail->carrier?->name,
                'status' => $driverDetail->status,
            ] : null,
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('roles:id,name');

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles->pluck('id')->toArray(),
            'profile_photo_url' => $user->getFirstMediaUrl('profile_photos') ?: null,
            'created_at' => $user->created_at,
        ];

        return Inertia::render('admin/users/Edit', [
            'user' => $userData,
            'roles' => Role::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:8|confirmed',
            'status' => 'required|boolean',
            'profile_photo' => 'nullable|image|max:2048',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        try {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
            ]);

            $user->status = $validated['status'] ? 1 : 0;
            $user->save();

            if ($request->has('roles')) {
                $roles = Role::whereIn('id', $validated['roles'] ?? [])->pluck('name')->toArray();
                $user->syncRoles($roles);
            }

            if ($request->hasFile('profile_photo')) {
                $user->clearMediaCollection('profile_photos');
                $fileName = strtolower(str_replace(' ', '_', $user->name)) . '.webp';
                $user->addMediaFromRequest('profile_photo')
                    ->usingFileName($fileName)
                    ->toMediaCollection('profile_photos');
            }

            return redirect()->route('admin.users.edit', $user)
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error updating user: ' . $e->getMessage());
        }
    }

    public function deletePhoto(User $user): RedirectResponse
    {
        $user->clearMediaCollection('profile_photos');
        return back()->with('success', 'Profile photo deleted.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        try {
            $user->clearMediaCollection('profile_photos');
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return back()->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }
}
