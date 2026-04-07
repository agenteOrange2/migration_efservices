<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_driver_details', function (Blueprint $table) {
            $table->date('hire_date')->nullable()->after('date_of_birth');
            $table->date('termination_date')->nullable()->after('hire_date');
        });
    }

    public function down(): void
    {
        Schema::table('user_driver_details', function (Blueprint $table) {
            $table->dropColumn(['hire_date', 'termination_date']);
        });
    }
};
