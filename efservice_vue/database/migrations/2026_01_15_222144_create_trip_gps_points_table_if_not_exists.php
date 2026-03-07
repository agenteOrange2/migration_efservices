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
        if (!Schema::hasTable('trip_gps_points')) {
            Schema::create('trip_gps_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
                $table->decimal('latitude', 10, 6);
                $table->decimal('longitude', 10, 6);
                $table->decimal('speed', 5, 2)->nullable();
                $table->decimal('heading', 5, 2)->nullable();
                $table->string('formatted_address')->nullable();
                $table->datetime('recorded_at');
                $table->timestamps();

                $table->index(['trip_id', 'recorded_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_gps_points');
    }
};
