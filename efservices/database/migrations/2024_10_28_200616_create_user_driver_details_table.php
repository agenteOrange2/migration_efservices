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
        Schema::create('user_driver_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('cascade');        
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('phone');
            $table->date('date_of_birth');
            $table->unsignedTinyInteger('status')->default(0)->index();
            // HOS Cycle Type: '60_7' = 60h in 7 days, '70_8' = 70h in 8 days
            $table->string('hos_cycle_type', 10)->default('70_8')
                ->comment('HOS weekly cycle type: 60_7 or 70_8');
            $table->boolean('hos_cycle_change_requested')->default(false)
                ->comment('True if driver has a pending cycle change request');
            $table->string('hos_cycle_change_requested_to', 10)->nullable()
                ->comment('Requested new cycle type (60_7 or 70_8)');
            $table->timestamp('hos_cycle_change_requested_at')->nullable()
                ->comment('When the cycle change was requested');
            $table->timestamp('hos_cycle_change_approved_at')->nullable()
                ->comment('When the cycle change was approved');
            $table->foreignId('hos_cycle_change_approved_by')->nullable()
                ->constrained('users')->onDelete('set null')
                ->comment('User who approved the cycle change (carrier or admin)');
            $table->boolean('terms_accepted')->default(false);
            $table->string('confirmation_token', 64)->nullable();
            $table->boolean('application_completed')->default(false);
            $table->integer('current_step')->default(1);
            $table->integer('completion_percentage')->default(0);
            $table->boolean('use_custom_dates')->default(false);     
            $table->datetime('custom_created_at')->nullable();            
            $table->boolean('has_completed_employment_history')->default(false);
            $table->date('custom_registration_date')->nullable();
            $table->date('custom_completion_date')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('status', 'idx_user_driver_details_status');
            $table->index('carrier_id', 'idx_user_driver_details_carrier_id');
            $table->index(['status', 'carrier_id'], 'idx_user_driver_details_status_carrier_id');
            $table->index('created_at', 'idx_user_driver_created_at');
            $table->index(['hos_cycle_change_requested', 'carrier_id'], 'idx_pending_cycle_requests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_driver_details');
    }
};
