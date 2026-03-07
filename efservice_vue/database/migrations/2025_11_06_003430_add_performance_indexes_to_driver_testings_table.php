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
        Schema::table('driver_testings', function (Blueprint $table) {
            // Add index on carrier_id for faster carrier-based queries
            $table->index('carrier_id', 'idx_driver_testings_carrier_id');
            
            // Add index on user_driver_detail_id for faster driver-based queries
            $table->index('user_driver_detail_id', 'idx_driver_testings_user_driver_detail_id');
            
            // Add index on test_date for date-based filtering and sorting
            $table->index('test_date', 'idx_driver_testings_test_date');
            
            // Add composite index on status and test_date for common filtered queries
            $table->index(['status', 'test_date'], 'idx_driver_testings_status_test_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_testings', function (Blueprint $table) {
            // Drop indexes in reverse order
            $table->dropIndex('idx_driver_testings_status_test_date');
            $table->dropIndex('idx_driver_testings_test_date');
            $table->dropIndex('idx_driver_testings_user_driver_detail_id');
            $table->dropIndex('idx_driver_testings_carrier_id');
        });
    }
};
