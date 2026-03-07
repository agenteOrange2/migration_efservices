<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds fields to support HOS auto-stop functionality:
     * - auto_stopped_at: When the trip was auto-stopped by the system
     * - auto_stop_reason: Why it was stopped (driving_limit_exceeded, duty_period_exceeded, etc.)
     * - hos_penalty_end_time: When the mandatory rest period ends
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->timestamp('auto_stopped_at')->nullable()->after('has_violations');
            $table->string('auto_stop_reason')->nullable()->after('auto_stopped_at');
            $table->timestamp('hos_penalty_end_time')->nullable()->after('auto_stop_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['auto_stopped_at', 'auto_stop_reason', 'hos_penalty_end_time']);
        });
    }
};
