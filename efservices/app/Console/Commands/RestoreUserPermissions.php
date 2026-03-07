<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class RestoreUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:restore-permissions {userId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore superadmin permissions to a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }
        
        $this->info("Found user: {$user->name} ({$user->email})");
        
        // Show current roles
        $currentRoles = $user->roles->pluck('name')->toArray();
        $this->info("Current roles: " . (empty($currentRoles) ? 'None' : implode(', ', $currentRoles)));
        
        // Ask for confirmation
        if (!$this->confirm('Do you want to assign superadmin role to this user?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Remove all current roles
        $user->syncRoles([]);
        
        // Assign superadmin role
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $user->assignRole($superAdminRole);
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->info("✓ Superadmin role assigned successfully!");
        $this->info("✓ Permission cache cleared!");
        
        // Show new roles
        $newRoles = $user->fresh()->roles->pluck('name')->toArray();
        $this->info("New roles: " . implode(', ', $newRoles));
        
        return 0;
    }
}
