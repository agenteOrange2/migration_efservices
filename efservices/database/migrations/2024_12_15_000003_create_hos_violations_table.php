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
        Schema::create('hos_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained('user_driver_details')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->enum('violation_type', [
                'driving_limit_exceeded',
                'duty_limit_exceeded',
                'duty_period_exceeded',
                'weekly_cycle_exceeded',
                'missing_required_break',
                'forgot_to_close_trip'
            ]);
            $table->date('violation_date');
            $table->decimal('hours_exceeded', 5, 2);
            $table->foreignId('hos_entry_id')->nullable()->constrained('hos_entries')->onDelete('set null');
            $table->boolean('acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('acknowledged_at')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_driver_detail_id', 'violation_date']);
            $table->index(['carrier_id', 'violation_date']);
            $table->index('acknowledged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hos_violations');
    }
};
