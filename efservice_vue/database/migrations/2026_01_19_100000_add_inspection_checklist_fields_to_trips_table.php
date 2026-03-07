<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Pre-trip inspection data (JSON) - stores all checklist items
            $table->json('pre_trip_inspection_data')->nullable()->after('pre_trip_inspection_at');

            // Post-trip inspection data (JSON) - stores all checklist items
            $table->json('post_trip_inspection_data')->nullable()->after('post_trip_inspection_at');

            // Trailer indicator - whether trip includes a trailer
            $table->boolean('has_trailer')->default(false)->after('post_trip_inspection_data');

            // Remarks/notes for inspections
            $table->text('pre_trip_remarks')->nullable()->after('has_trailer');
            $table->text('post_trip_remarks')->nullable()->after('pre_trip_remarks');

            // Defects found indicators
            $table->boolean('pre_trip_defects_found')->default(false)->after('post_trip_remarks');
            $table->boolean('post_trip_defects_found')->default(false)->after('pre_trip_defects_found');

            // Vehicle condition certification
            $table->boolean('vehicle_condition_satisfactory')->default(true)->after('post_trip_defects_found');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'pre_trip_inspection_data',
                'post_trip_inspection_data',
                'has_trailer',
                'pre_trip_remarks',
                'post_trip_remarks',
                'pre_trip_defects_found',
                'post_trip_defects_found',
                'vehicle_condition_satisfactory',
            ]);
        });
    }
};
