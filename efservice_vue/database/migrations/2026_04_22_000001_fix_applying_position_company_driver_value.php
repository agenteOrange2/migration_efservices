<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize 'company_driver' → 'driver' to match Constants::driverPositions() keys
        DB::table('driver_application_details')
            ->where('applying_position', 'company_driver')
            ->update(['applying_position' => 'driver']);
    }

    public function down(): void
    {
        DB::table('driver_application_details')
            ->where('applying_position', 'driver')
            ->update(['applying_position' => 'company_driver']);
    }
};
