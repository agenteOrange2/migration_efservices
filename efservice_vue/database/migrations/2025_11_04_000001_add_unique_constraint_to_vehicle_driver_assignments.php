<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clean up any duplicate pending assignments
        // Keep only the most recent one for each user_driver_detail_id
        DB::statement("
            DELETE FROM vehicle_driver_assignments
            WHERE id NOT IN (
                SELECT * FROM (
                    SELECT MAX(id)
                    FROM vehicle_driver_assignments
                    WHERE status = 'pending'
                    GROUP BY user_driver_detail_id
                ) AS keep_ids
            )
            AND status = 'pending'
        ");

        // Add composite unique index on user_driver_detail_id and status
        // This ensures only one assignment per driver per status
        // Note: MySQL/MariaDB doesn't support partial indexes with WHERE clause
        // So we use a composite unique index instead
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            $table->unique(['user_driver_detail_id', 'status'], 'unique_driver_status_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            $table->dropUnique('unique_driver_status_assignment');
        });
    }
};
