<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: expand ENUM to include both old and new values
        DB::statement("ALTER TABLE vehicle_driver_assignments MODIFY COLUMN driver_type ENUM('owner_operator','third_party','company_driver','company') NULL");
        // Step 2: migrate existing data
        DB::statement("UPDATE vehicle_driver_assignments SET driver_type = 'company' WHERE driver_type = 'company_driver'");
        // Step 3: remove the old value from ENUM
        DB::statement("ALTER TABLE vehicle_driver_assignments MODIFY COLUMN driver_type ENUM('owner_operator','third_party','company') NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE vehicle_driver_assignments SET driver_type = 'company_driver' WHERE driver_type = 'company'");
        DB::statement("ALTER TABLE vehicle_driver_assignments MODIFY COLUMN driver_type ENUM('owner_operator','third_party','company_driver') NULL");
    }
};
