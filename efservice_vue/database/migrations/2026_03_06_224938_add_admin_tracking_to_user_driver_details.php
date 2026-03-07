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
        Schema::table('user_driver_details', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_admin')->nullable()->after('confirmation_token');
            $table->unsignedBigInteger('updated_by_admin')->nullable()->after('created_by_admin');

            $table->foreign('created_by_admin')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_admin')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_driver_details', function (Blueprint $table) {
            $table->dropForeign(['created_by_admin']);
            $table->dropForeign(['updated_by_admin']);
            $table->dropColumn(['created_by_admin', 'updated_by_admin']);
        });
    }
};
