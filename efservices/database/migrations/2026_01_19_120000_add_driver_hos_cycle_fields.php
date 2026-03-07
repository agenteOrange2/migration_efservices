<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds HOS cycle management fields to user_driver_details table.
     * Allows drivers to have individual 60/70 hour cycle settings
     * with approval workflow for cycle changes.
     */
    public function up(): void
    {
        Schema::table('user_driver_details', function (Blueprint $table) {
            // HOS Cycle Type: '60_7' = 60h in 7 days, '70_8' = 70h in 8 days
            if (!Schema::hasColumn('user_driver_details', 'hos_cycle_type')) {
                $table->string('hos_cycle_type', 10)->default('70_8')->after('status')
                    ->comment('HOS weekly cycle type: 60_7 or 70_8');
            }

            // Cycle change request fields
            if (!Schema::hasColumn('user_driver_details', 'hos_cycle_change_requested')) {
                $table->boolean('hos_cycle_change_requested')->default(false)->after('hos_cycle_type')
                    ->comment('True if driver has a pending cycle change request');
            }

            if (!Schema::hasColumn('user_driver_details', 'hos_cycle_change_requested_to')) {
                $table->string('hos_cycle_change_requested_to', 10)->nullable()->after('hos_cycle_change_requested')
                    ->comment('Requested new cycle type (60_7 or 70_8)');
            }

            if (!Schema::hasColumn('user_driver_details', 'hos_cycle_change_requested_at')) {
                $table->timestamp('hos_cycle_change_requested_at')->nullable()->after('hos_cycle_change_requested_to')
                    ->comment('When the cycle change was requested');
            }

            if (!Schema::hasColumn('user_driver_details', 'hos_cycle_change_approved_at')) {
                $table->timestamp('hos_cycle_change_approved_at')->nullable()->after('hos_cycle_change_requested_at')
                    ->comment('When the cycle change was approved');
            }

            if (!Schema::hasColumn('user_driver_details', 'hos_cycle_change_approved_by')) {
                $table->foreignId('hos_cycle_change_approved_by')->nullable()->after('hos_cycle_change_approved_at')
                    ->constrained('users')->onDelete('set null')
                    ->comment('User who approved the cycle change (carrier or admin)');
            }

            // Add index for pending requests
            $table->index(['hos_cycle_change_requested', 'carrier_id'], 'idx_pending_cycle_requests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_driver_details', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('user_driver_details', 'hos_cycle_change_approved_by')) {
                $table->dropForeign(['hos_cycle_change_approved_by']);
            }

            // Drop index
            $table->dropIndex('idx_pending_cycle_requests');

            // Drop columns
            $columns = [
                'hos_cycle_type',
                'hos_cycle_change_requested',
                'hos_cycle_change_requested_to',
                'hos_cycle_change_requested_at',
                'hos_cycle_change_approved_at',
                'hos_cycle_change_approved_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('user_driver_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
