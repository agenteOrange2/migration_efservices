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
