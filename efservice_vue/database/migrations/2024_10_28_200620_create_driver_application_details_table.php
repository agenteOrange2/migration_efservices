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
        Schema::create('driver_application_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_driver_assignment_id')->nullable();
            $table->foreignId('driver_application_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('applying_position');
            $table->string('applying_position_other')->nullable();
            $table->string('applying_location');
            $table->boolean('eligible_to_work')->default(false);
            $table->boolean('can_speak_english')->default(false);
            $table->boolean('has_twic_card')->default(false);
            $table->date('twic_expiration_date')->nullable();
            $table->string('how_did_hear');
            $table->string('how_did_hear_other')->nullable();
            $table->string('referral_employee_name')->nullable();
            $table->decimal('expected_pay', 10, 2);
            // Owner Operator fields
            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 30)->nullable();
            $table->string('owner_email')->nullable();
            // Third Party fields
            $table->string('third_party_name')->nullable();
            $table->string('third_party_phone', 30)->nullable();
            $table->string('third_party_email')->nullable();
            $table->string('third_party_dba')->nullable();
            $table->string('third_party_address')->nullable();
            $table->string('third_party_contact')->nullable();
            $table->string('third_party_fein', 30)->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamps();

            $table->foreign('vehicle_driver_assignment_id')->references('id')->on('vehicle_driver_assignments')->onDelete('set null');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_application_details');
    }
};
