<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MigrationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates permissions for driver migration functionality.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create migration-related permissions
        $permissions = [
            'driver.migrate' => 'Migrate drivers between carriers',
            'driver.archive.view' => 'View archived driver records',
            'migration.report.view' => 'View migration reports',
            'migration.rollback' => 'Rollback driver migrations',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Assign permissions to superadmin role
        $superAdmin = Role::where('name', 'superadmin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_keys($permissions));
        }

        // Assign view archive permission to carrier admin
        $carrierAdmin = Role::where('name', 'user_carrier')->first();
        if ($carrierAdmin) {
            $carrierAdmin->givePermissionTo([
                'driver.archive.view',
            ]);
        }

        $this->command->info('Migration permissions created successfully.');
    }
}
