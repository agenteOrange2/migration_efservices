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
        Schema::create('hos_daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained('user_driver_details')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->date('date');
            $table->unsignedInteger('total_driving_minutes')->default(0);
            $table->unsignedInteger('total_on_duty_minutes')->default(0);
            $table->unsignedInteger('total_off_duty_minutes')->default(0);
            $table->boolean('has_violations')->default(false);
            $table->text('driver_signature')->nullable();
            $table->datetime('signed_at')->nullable();
            $table->timestamps();

            // Unique constraint for one log per driver per day
            $table->unique(['user_driver_detail_id', 'date']);
            
            // Indexes for common queries
            $table->index(['carrier_id', 'date']);
            $table->index('has_violations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hos_daily_logs');
    }
};
