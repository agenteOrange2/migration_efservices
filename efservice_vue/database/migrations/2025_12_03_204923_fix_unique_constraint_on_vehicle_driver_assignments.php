<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes the unique constraint on vehicle_driver_assignments
     * to allow multiple inactive assignments per driver (for history tracking)
     * while still enforcing uniqueness for active and pending statuses.
     */
    public function up(): void
    {
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            // Drop the old constraint that was too restrictive
            $table->dropUnique('unique_driver_status_assignment');
        });
        
        // Add separate unique indexes for active and pending statuses only
        // Note: MySQL doesn't support partial indexes, so we'll handle this at the application level
        // and add regular indexes for performance
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            // Add index for active status lookups (not unique, will be enforced in application)
            $table->index(['user_driver_detail_id', 'status'], 'idx_driver_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            // Drop the new index
            $table->dropIndex('idx_driver_status');
        });
        
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            // Restore the old unique constraint
            $table->unique(['user_driver_detail_id', 'status'], 'unique_driver_status_assignment');
        });
    }
};
