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
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('unit');
            // service_date uses DEFAULT CURRENT_TIMESTAMP only (no ON UPDATE) to prevent auto-updates
            $table->timestamp('service_date')->useCurrent();
            $table->timestamp('next_service_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('service_tasks');
            $table->string('vendor_mechanic');
            $table->text('description')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->integer('odometer')->nullable();
            $table->boolean('status')->default(false);
            $table->boolean('is_historical')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
