<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUserAccess extends Command
{
    protected $signature = 'user:check-access {userId}';
    protected $description = 'Check user access, roles and permissions';

    public function handle()
    {
        $userId = $this->argument('userId');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User not found!");
            return 1;
        }
        
        $this->info("=== USER INFORMATION ===");
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Status: {$user->status}");
        
        $this->info("\n=== ROLES ===");
        $roles = $user->roles->pluck('name')->toArray();
        if (empty($roles)) {
            $this->warn("No roles assigned!");
        } else {
            foreach ($roles as $role) {
                $this->line("✓ {$role}");
            }
        }
        
        $this->info("\n=== PERMISSIONS ===");
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        if (empty($permissions)) {
            $this->warn("No permissions!");
        } else {
            $this->line("Total permissions: " . count($permissions));
            foreach (array_slice($permissions, 0, 10) as $permission) {
                $this->line("✓ {$permission}");
            }
            if (count($permissions) > 10) {
                $this->line("... and " . (count($permissions) - 10) . " more");
            }
        }
        
        $this->info("\n=== ACCESS CHECKS ===");
        $this->line("Has superadmin role: " . ($user->hasRole('superadmin') ? '✓ YES' : '✗ NO'));
        $this->line("Can view admin dashboard: " . ($user->can('view admin dashboard') ? '✓ YES' : '✗ NO'));
        
        return 0;
    }
}
