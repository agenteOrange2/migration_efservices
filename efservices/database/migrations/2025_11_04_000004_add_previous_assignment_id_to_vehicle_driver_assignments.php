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
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            // Add field to track the previous assignment for audit trail
            $table->unsignedBigInteger('previous_assignment_id')->nullable()->after('user_driver_detail_id');
            
            // Add foreign key constraint (self-referencing)
            $table->foreign('previous_assignment_id')
                  ->references('id')
                  ->on('vehicle_driver_assignments')
                  ->onDelete('set null');
            
            // Add index for performance when querying assignment history
            $table->index('previous_assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_driver_assignments', function (Blueprint $table) {
            $table->dropForeign(['previous_assignment_id']);
            $table->dropIndex(['previous_assignment_id']);
            $table->dropColumn('previous_assignment_id');
        });
    }
};
