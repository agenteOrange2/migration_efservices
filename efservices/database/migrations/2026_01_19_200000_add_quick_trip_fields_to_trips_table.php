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
            // Quick Trip flags
            if (!Schema::hasColumn('trips', 'is_quick_trip')) {
                $table->boolean('is_quick_trip')->default(false)->after('status');
            }

            if (!Schema::hasColumn('trips', 'requires_completion')) {
                $table->boolean('requires_completion')->default(false)->after('is_quick_trip');
            }

            // Tracking when info was completed
            if (!Schema::hasColumn('trips', 'completed_info_at')) {
                $table->datetime('completed_info_at')->nullable()->after('requires_completion');
            }

            if (!Schema::hasColumn('trips', 'completed_info_by')) {
                $table->foreignId('completed_info_by')->nullable()->after('completed_info_at')
                    ->constrained('users')->onDelete('set null');
            }

            // Add index for filtering incomplete trips
            $table->index(['is_quick_trip', 'requires_completion'], 'trips_quick_trip_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('trips_quick_trip_status_index');

            // Drop foreign key and columns
            if (Schema::hasColumn('trips', 'completed_info_by')) {
                $table->dropForeign(['completed_info_by']);
                $table->dropColumn('completed_info_by');
            }

            if (Schema::hasColumn('trips', 'completed_info_at')) {
                $table->dropColumn('completed_info_at');
            }

            if (Schema::hasColumn('trips', 'requires_completion')) {
                $table->dropColumn('requires_completion');
            }

            if (Schema::hasColumn('trips', 'is_quick_trip')) {
                $table->dropColumn('is_quick_trip');
            }
        });
    }
};
