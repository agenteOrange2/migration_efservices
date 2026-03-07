<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Base TestCase for all Admin Feature Tests
 * Provides common setup for authentication and permissions
 */
abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear el rol de superadmin
        $this->superAdminRole = Role::create(['name' => 'superadmin']);

        // Crear el permiso requerido por el middleware CheckAdminStatus
        $permission = Permission::create(['name' => 'view admin dashboard']);
        $this->superAdminRole->givePermissionTo($permission);

        // Crear usuario superadmin con el rol y permisos asignados
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('superadmin');
    }

    /**
     * Helper method to act as the super admin user
     */
    protected function actingAsSuperAdmin()
    {
        return $this->actingAs($this->superAdmin);
    }

    /**
     * Helper method to create additional permissions if needed
     */
    protected function createPermission(string $name): Permission
    {
        $permission = Permission::create(['name' => $name]);
        $this->superAdminRole->givePermissionTo($permission);
        return $permission;
    }

    /**
     * Helper method to create a user with a specific role
     */
    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }
}
