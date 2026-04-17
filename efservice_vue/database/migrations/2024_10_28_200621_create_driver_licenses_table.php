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
        Schema::create('driver_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained()->onDelete('cascade');            
            $table->string('license_number');
            $table->string('state_of_issue');
            $table->string('license_class');
            $table->date('expiration_date');
            $table->boolean('is_cdl')->default(false);
            $table->text('restrictions')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked', 'suspended'])->default('active');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Performance indexes
            $table->index('user_driver_detail_id', 'idx_driver_licenses_driver_id');
            $table->index('expiration_date', 'idx_driver_licenses_expiration');
            $table->index('status', 'idx_driver_licenses_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_licenses');
    }
};
