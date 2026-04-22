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

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Role::with('permissions:id,name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $roles = $query->orderBy('name')
            ->paginate($request->input('per_page', 15))
            ->through(fn ($role) => [
                'id'               => $role->id,
                'name'             => $role->name,
                'guard_name'       => $role->guard_name,
                'permissions'      => $role->permissions->pluck('name')->toArray(),
                'permissions_count' => $role->permissions->count(),
                'created_at'       => $role->created_at,
            ]);

        $stats = [
            'total'       => Role::count(),
            'permissions' => Permission::count(),
        ];

        $permissions = Permission::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/roles/Index', [
            'roles'       => $roles,
            'stats'       => $stats,
            'permissions' => $permissions,
            'filters'     => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('roles', 'name')],
            'guard_name'  => ['nullable', 'string', 'max:50'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        try {
            $role = Role::create([
                'name'       => $validated['name'],
                'guard_name' => $validated['guard_name'] ?? 'web',
            ]);

            if (! empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            Log::info('Role created', ['role' => $role->name]);

            return back()->with('success', "Role \"{$role->name}\" created successfully.");
        } catch (\Exception $e) {
            Log::error('Error creating role', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'An error occurred while creating the role.']);
        }
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        try {
            $role->update(['name' => $validated['name']]);
            $role->permissions()->sync($validated['permissions'] ?? []);

            Log::info('Role updated', ['role' => $role->name]);

            return back()->with('success', "Role \"{$role->name}\" updated successfully.");
        } catch (\Exception $e) {
            Log::error('Error updating role', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'An error occurred while updating the role.']);
        }
    }

    public function destroy(Role $role): RedirectResponse
    {
        try {
            $name = $role->name;
            $role->delete();

            Log::info('Role deleted', ['role' => $name]);

            return back()->with('success', "Role \"{$name}\" deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Error deleting role', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'An error occurred while deleting the role.']);
        }
    }
}
