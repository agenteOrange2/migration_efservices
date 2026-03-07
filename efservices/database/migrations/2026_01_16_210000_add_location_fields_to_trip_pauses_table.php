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
        Schema::table('trip_pauses', function (Blueprint $table) {
            // Rename existing columns to match design
            $table->renameColumn('pause_start', 'started_at');
            $table->renameColumn('pause_end', 'ended_at');
        });

        Schema::table('trip_pauses', function (Blueprint $table) {
            // Add new location and tracking fields
            $table->decimal('latitude', 10, 6)->nullable()->after('ended_at');
            $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
            $table->string('formatted_address', 500)->nullable()->after('longitude');
            
            // Make reason nullable and increase length
            $table->string('reason', 255)->nullable()->change();
            
            // Add forced_by for admin/carrier forced pauses
            $table->foreignId('forced_by')->nullable()->after('reason')
                ->constrained('users')->nullOnDelete();
            
            // Add HOS entry reference
            $table->foreignId('hos_entry_id')->nullable()->after('forced_by')
                ->constrained('hos_entries')->nullOnDelete();
            
            // Add index for performance
            $table->index(['trip_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_pauses', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'started_at']);
            $table->dropForeign(['hos_entry_id']);
            $table->dropForeign(['forced_by']);
            $table->dropColumn(['latitude', 'longitude', 'formatted_address', 'forced_by', 'hos_entry_id']);
        });

        Schema::table('trip_pauses', function (Blueprint $table) {
            $table->renameColumn('started_at', 'pause_start');
            $table->renameColumn('ended_at', 'pause_end');
            $table->string('reason')->nullable(false)->change();
        });
    }
};
