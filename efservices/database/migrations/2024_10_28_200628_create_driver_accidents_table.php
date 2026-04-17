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
        Schema::create('driver_accidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained()->onDelete('cascade');
            $table->date('accident_date');
            $table->string('nature_of_accident');
            $table->boolean('had_injuries')->default(false);
            $table->integer('number_of_injuries')->nullable();
            $table->boolean('had_fatalities')->default(false);
            $table->integer('number_of_fatalities')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('user_driver_detail_id', 'idx_driver_accidents_driver_id');
            $table->index('accident_date', 'idx_driver_accidents_accident_date');
            $table->index(['user_driver_detail_id', 'accident_date'], 'idx_driver_accidents_driver_date');
            $table->index('had_injuries', 'idx_driver_accidents_had_injuries');
            $table->index('had_fatalities', 'idx_driver_accidents_had_fatalities');
            $table->index('created_at', 'idx_driver_accidents_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_accidents');
    }
};
