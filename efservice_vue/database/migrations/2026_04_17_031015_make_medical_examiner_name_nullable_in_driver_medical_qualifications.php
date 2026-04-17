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
        Schema::table('driver_medical_qualifications', function (Blueprint $table) {
            $table->string('medical_examiner_name')->nullable()->change();
            $table->string('medical_examiner_registry_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('driver_medical_qualifications', function (Blueprint $table) {
            $table->string('medical_examiner_name')->nullable(false)->change();
            $table->string('medical_examiner_registry_number')->nullable(false)->change();
        });
    }
};
