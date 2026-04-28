<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('registration_expiration_date')->nullable()->change();
            $table->string('fuel_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('registration_expiration_date')->nullable(false)->change();
            $table->string('fuel_type')->nullable(false)->change();
        });
    }
};
