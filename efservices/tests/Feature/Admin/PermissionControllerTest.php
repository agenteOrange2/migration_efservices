<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionControllerTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function superadmin_can_access_permissions_index()
    {
        Permission::factory()->count(5)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.permissions.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function permissions_index_displays_permissions()
    {
        Permission::factory()->count(10)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.permissions.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_permission_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.permissions.create'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_create_permission()
    {
        $permissionData = [
            'name' => 'test permission',
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.permissions.store'), $permissionData);

        $response->assertRedirect();
        $this->assertDatabaseHas('permissions', [
            'name' => 'test permission',
        ]);
    }

    /** @test */
    public function permission_creation_requires_unique_name()
    {
        Permission::factory()->create(['name' => 'existing.permission']);

        $permissionData = [
            'name' => 'existing.permission',
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.permissions.store'), $permissionData);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function superadmin_can_view_permission_edit_form()
    {
        $permission = Permission::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.permissions.edit', $permission));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_update_permission()
    {
        $permission = Permission::factory()->create();

        $updateData = [
            'name' => 'updated.permission.name',
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('admin.permissions.update', $permission), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'updated.permission.name',
        ]);
    }

    /** @test */
    public function superadmin_can_delete_permission()
    {
        $permission = Permission::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.permissions.destroy', $permission));

        $response->assertRedirect();
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    /** @test */
    public function guest_cannot_access_permissions()
    {
        $response = $this->get(route('admin.permissions.index'));

        $response->assertRedirect('/login');
    }
}
