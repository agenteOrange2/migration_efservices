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
        Schema::table('driver_accidents', function (Blueprint $table) {
            // Index for filtering by driver
            $table->index('user_driver_detail_id', 'idx_driver_accidents_driver_id');
            
            // Index for filtering and sorting by accident date
            $table->index('accident_date', 'idx_driver_accidents_accident_date');
            
            // Composite index for common query patterns (driver + date)
            $table->index(['user_driver_detail_id', 'accident_date'], 'idx_driver_accidents_driver_date');
            
            // Indexes for filtering by injuries and fatalities
            $table->index('had_injuries', 'idx_driver_accidents_had_injuries');
            $table->index('had_fatalities', 'idx_driver_accidents_had_fatalities');
            
            // Index for created_at (used in ordering documents)
            $table->index('created_at', 'idx_driver_accidents_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_accidents', function (Blueprint $table) {
            $table->dropIndex('idx_driver_accidents_driver_id');
            $table->dropIndex('idx_driver_accidents_accident_date');
            $table->dropIndex('idx_driver_accidents_driver_date');
            $table->dropIndex('idx_driver_accidents_had_injuries');
            $table->dropIndex('idx_driver_accidents_had_fatalities');
            $table->dropIndex('idx_driver_accidents_created_at');
        });
    }
};
