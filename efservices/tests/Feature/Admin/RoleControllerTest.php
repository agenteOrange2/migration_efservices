<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleControllerTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Permission::factory()->count(5)->create();
    }

    /** @test */
    public function superadmin_can_access_roles_index()
    {
        Role::factory()->count(3)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.index');
    }

    /** @test */
    public function roles_index_displays_roles()
    {
        Role::factory()->count(5)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertViewHas('roles');
    }

    /** @test */
    public function superadmin_can_view_role_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.roles.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.create');
        $response->assertViewHas('permissions');
    }

    /** @test */
    public function superadmin_can_create_role()
    {
        $roleData = [
            'name' => 'Test Role',
            'permissions' => Permission::pluck('id')->toArray(),
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.roles.store'), $roleData);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
        ]);
        
        $role = Role::where('name', 'Test Role')->first();
        $this->assertTrue($role->permissions->count() > 0);
    }

    /** @test */
    public function role_creation_requires_unique_name()
    {
        Role::factory()->create(['name' => 'Existing Role']);

        $roleData = [
            'name' => 'Existing Role',
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.roles.store'), $roleData);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function superadmin_can_view_role_edit_form()
    {
        $role = Role::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.roles.edit', $role));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.edit');
        $response->assertViewHasAll(['role', 'permissions', 'rolePermissions']);
    }

    /** @test */
    public function superadmin_can_update_role()
    {
        $role = Role::factory()->create();

        $updateData = [
            'name' => 'Updated Role Name',
            'permissions' => [],
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('admin.roles.update', $role), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role Name',
        ]);
    }

    /** @test */
    public function superadmin_can_sync_role_permissions()
    {
        $role = Role::factory()->create();
        $permissions = Permission::pluck('id')->take(3)->toArray();

        $updateData = [
            'name' => $role->name,
            'permissions' => $permissions,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('admin.roles.update', $role), $updateData);

        $response->assertRedirect();
        $role->refresh();
        $this->assertEquals(3, $role->permissions->count());
    }

    /** @test */
    public function superadmin_can_remove_all_role_permissions()
    {
        $role = Role::factory()->create();
        $permissions = Permission::pluck('id')->toArray();
        $role->syncPermissions($permissions);
        
        $this->assertTrue($role->permissions->count() > 0);

        $updateData = [
            'name' => $role->name,
            'permissions' => [],
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('admin.roles.update', $role), $updateData);

        $response->assertRedirect();
        $role->refresh();
        $this->assertEquals(0, $role->permissions->count());
    }

    /** @test */
    public function superadmin_can_delete_role()
    {
        $role = Role::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function superadmin_cannot_delete_superadmin_role()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.roles.destroy', $superadminRole));

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $superadminRole->id]);
    }

    /** @test */
    public function roles_index_has_custom_filters()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertViewHas('customFilters');
    }

    /** @test */
    public function guest_cannot_access_roles()
    {
        $response = $this->get(route('admin.roles.index'));

        $response->assertRedirect('/login');
    }
}
