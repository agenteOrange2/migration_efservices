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
        Schema::create('hos_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->unique()->constrained('carriers')->onDelete('cascade');
            $table->decimal('max_driving_hours', 4, 2)->default(12.00);
            $table->decimal('max_duty_hours', 4, 2)->default(14.00);
            $table->unsignedInteger('warning_threshold_minutes')->default(60);
            $table->unsignedInteger('violation_threshold_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            // FMCSA Texas Intrastate specific
            $table->boolean('fmcsa_texas_mode')->default(true);
            $table->boolean('allow_24_hour_reset')->default(true);
            // Break requirements
            $table->boolean('require_30_min_break')->default(true);
            $table->integer('break_after_hours')->default(8);
            // Weekly cycle limits
            $table->integer('weekly_limit_60_minutes')->default(3600);
            $table->integer('weekly_limit_70_minutes')->default(4200);
            // Ghost log detection
            $table->boolean('enable_ghost_log_detection')->default(true);
            $table->integer('ghost_log_threshold_minutes')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hos_configurations');
    }
};
