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
        Schema::create('hos_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained('user_driver_details')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('set null');
            $table->enum('status', ['on_duty_not_driving', 'on_duty_driving', 'off_duty']);
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('formatted_address')->nullable();
            $table->boolean('location_available')->default(true);
            $table->boolean('is_manual_entry')->default(false);
            $table->boolean('is_ghost_log')->default(false);
            $table->text('ghost_log_reason')->nullable();
            $table->text('manual_entry_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('date');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_driver_detail_id', 'date']);
            $table->index(['carrier_id', 'date']);
            $table->index('status');
            $table->index(['user_driver_detail_id', 'end_time']); // For finding open entries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hos_entries');
    }
};
