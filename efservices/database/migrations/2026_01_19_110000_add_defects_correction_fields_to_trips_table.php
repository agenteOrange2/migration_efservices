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
            // Pre-trip defects correction fields
            $table->boolean('pre_trip_defects_corrected')->default(false)->after('pre_trip_defects_found');
            $table->text('pre_trip_defects_corrected_notes')->nullable()->after('pre_trip_defects_corrected');
            $table->boolean('pre_trip_defects_not_need_correction')->default(false)->after('pre_trip_defects_corrected_notes');
            $table->text('pre_trip_defects_not_need_correction_notes')->nullable()->after('pre_trip_defects_not_need_correction');

            // Post-trip defects correction fields
            $table->boolean('post_trip_defects_corrected')->default(false)->after('post_trip_defects_found');
            $table->text('post_trip_defects_corrected_notes')->nullable()->after('post_trip_defects_corrected');
            $table->boolean('post_trip_defects_not_need_correction')->default(false)->after('post_trip_defects_corrected_notes');
            $table->text('post_trip_defects_not_need_correction_notes')->nullable()->after('post_trip_defects_not_need_correction');

            // Driver signature for inspection reports
            $table->text('pre_trip_driver_signature')->nullable()->after('pre_trip_defects_not_need_correction_notes');
            $table->text('post_trip_driver_signature')->nullable()->after('post_trip_defects_not_need_correction_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'pre_trip_defects_corrected',
                'pre_trip_defects_corrected_notes',
                'pre_trip_defects_not_need_correction',
                'pre_trip_defects_not_need_correction_notes',
                'post_trip_defects_corrected',
                'post_trip_defects_corrected_notes',
                'post_trip_defects_not_need_correction',
                'post_trip_defects_not_need_correction_notes',
                'pre_trip_driver_signature',
                'post_trip_driver_signature',
            ]);
        });
    }
};
