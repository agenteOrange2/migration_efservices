<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_application_details', function (Blueprint $table) {
            // Vehicle reference
            $table->unsignedBigInteger('vehicle_id')->nullable()->after('vehicle_driver_assignment_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();

            // Owner Operator fields
            $table->string('owner_name')->nullable()->after('vehicle_id');
            $table->string('owner_phone', 30)->nullable()->after('owner_name');
            $table->string('owner_email')->nullable()->after('owner_phone');

            // Third Party fields
            $table->string('third_party_name')->nullable()->after('owner_email');
            $table->string('third_party_phone', 30)->nullable()->after('third_party_name');
            $table->string('third_party_email')->nullable()->after('third_party_phone');
            $table->string('third_party_dba')->nullable()->after('third_party_email');
            $table->string('third_party_address')->nullable()->after('third_party_dba');
            $table->string('third_party_contact')->nullable()->after('third_party_address');
            $table->string('third_party_fein', 30)->nullable()->after('third_party_contact');
            $table->boolean('email_sent')->default(false)->after('third_party_fein');
        });
    }

    public function down(): void
    {
        Schema::table('driver_application_details', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumns([
                'vehicle_id',
                'owner_name', 'owner_phone', 'owner_email',
                'third_party_name', 'third_party_phone', 'third_party_email',
                'third_party_dba', 'third_party_address', 'third_party_contact',
                'third_party_fein', 'email_sent',
            ]);
        });
    }
};
