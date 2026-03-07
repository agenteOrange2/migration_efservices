<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix: service_date had ON UPDATE CURRENT_TIMESTAMP() which caused it
     * to auto-update to NOW() whenever any other column (like status) was modified.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE vehicle_maintenances MODIFY service_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE vehicle_maintenances MODIFY service_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
