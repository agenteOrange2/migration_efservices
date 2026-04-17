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
        Schema::create('driver_unemployment_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained('user_driver_details')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('comments')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('user_driver_detail_id', 'idx_unemployment_driver_id');
            $table->index('end_date', 'idx_unemployment_end_date');
            $table->index(['user_driver_detail_id', 'end_date'], 'idx_unemployment_driver_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_unemployment_periods');
    }
};
