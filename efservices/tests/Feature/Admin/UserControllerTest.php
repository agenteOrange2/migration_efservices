<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserControllerTest extends AdminTestCase
{
    protected $regularAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);

        $this->regularAdmin = User::factory()->create();
        $this->regularAdmin->assignRole('admin');
    }

    /** @test */
    public function superadmin_can_access_users_index()
    {
        User::factory()->count(5)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
    }

    /** @test */
    public function users_index_displays_users()
    {
        User::factory()->count(10)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewHas('users');
    }

    /** @test */
    public function superadmin_can_view_user_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
        $response->assertViewHas('roles');
    }

    /** @test */
    public function superadmin_can_create_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 1,
            'roles' => [],
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.users.store'), $userData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 1,
        ]);
        
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('superadmin'));
    }

    /** @test */
    public function user_creation_requires_unique_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function user_creation_requires_password_confirmation()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function superadmin_can_view_user_details()
    {
        $user = User::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.users.show', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.show');
    }

    /** @test */
    public function superadmin_can_view_user_edit_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.users.edit', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.edit');
        $response->assertViewHasAll(['user', 'profilePhotoUrl', 'roles', 'userRoles']);
    }

    /** @test */
    public function superadmin_can_update_user()
    {
        $user = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function superadmin_can_update_user_password()
    {
        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function superadmin_can_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function superadmin_cannot_delete_self()
    {
        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.users.destroy', $this->superAdmin));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    /** @test */
    public function guest_cannot_access_users()
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect('/login');
    }
}
