<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Permission::with('roles:id,name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('group')) {
            $group = $request->input('group');
            $query->where('name', 'like', "{$group}.%");
        }

        $permissions = $query->orderBy('name')
            ->paginate($request->input('per_page', 20))
            ->through(fn ($permission) => [
                'id'          => $permission->id,
                'name'        => $permission->name,
                'guard_name'  => $permission->guard_name,
                'group'       => explode('.', $permission->name)[0] ?? 'general',
                'roles'       => $permission->roles->pluck('name')->toArray(),
                'roles_count' => $permission->roles->count(),
                'created_at'  => $permission->created_at,
            ]);

        // Extract unique groups from permission names (e.g., "carriers.view" → "carriers")
        $groups = Permission::orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => explode('.', $name)[0])
            ->unique()
            ->sort()
            ->values();

        $stats = [
            'total'  => Permission::count(),
            'roles'  => Role::count(),
            'groups' => $groups->count(),
        ];

        return Inertia::render('admin/permissions/Index', [
            'permissions' => $permissions,
            'stats'       => $stats,
            'groups'      => $groups,
            'filters'     => $request->only(['search', 'group']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:150', Rule::unique('permissions', 'name')],
            'guard_name' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $permission = Permission::create([
                'name'       => $validated['name'],
                'guard_name' => $validated['guard_name'] ?? 'web',
            ]);

            Log::info('Permission created', ['permission' => $permission->name]);

            return back()->with('success', "Permission \"{$permission->name}\" created successfully.");
        } catch (\Exception $e) {
            Log::error('Error creating permission', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'An error occurred while creating the permission.']);
        }
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        try {
            $permission->update(['name' => $validated['name']]);

            Log::info('Permission updated', ['permission' => $permission->name]);

            return back()->with('success', "Permission \"{$permission->name}\" updated successfully.");
        } catch (\Exception $e) {
            Log::error('Error updating permission', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'An error occurred while updating the permission.']);
        }
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        try {
            $name = $permission->name;
            $permission->delete();

            Log::info('Permission deleted', ['permission' => $name]);

            return back()->with('success', "Permission \"{$name}\" deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Error deleting permission', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'An error occurred while deleting the permission.']);
        }
    }
}
