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
        // Add FMCSA tracking fields to hos_daily_logs
        Schema::table('hos_daily_logs', function (Blueprint $table) {
            // Duty Period Tracking (14-hour window)
            $table->datetime('duty_period_start')->nullable()->after('date');
            $table->datetime('duty_period_end')->nullable()->after('duty_period_start');
            $table->integer('duty_period_minutes')->default(0)->after('duty_period_end');

            // Break tracking
            $table->integer('break_minutes')->default(0)->after('total_off_duty_minutes');
            $table->boolean('thirty_minute_break_taken')->default(false)->after('break_minutes');

            // 10-hour reset tracking
            $table->datetime('last_10_hour_reset_at')->nullable()->after('thirty_minute_break_taken');
            $table->integer('consecutive_off_duty_minutes')->default(0)->after('last_10_hour_reset_at');
        });

        // Create weekly cycle tracking table
        Schema::create('hos_weekly_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained('user_driver_details')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('cascade');

            // Week identification
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->integer('year');
            $table->integer('week_number');

            // Hours tracking
            $table->integer('total_driving_minutes')->default(0);
            $table->integer('total_on_duty_minutes')->default(0);
            $table->integer('total_duty_minutes')->default(0); // driving + on_duty

            // Cycle type (60/7 or 70/8)
            $table->enum('cycle_type', ['60_7', '70_8'])->default('60_7');
            $table->integer('cycle_limit_minutes')->default(3600); // 60h * 60min = 3600min

            // Reset tracking
            $table->boolean('has_24_hour_reset')->default(false);
            $table->datetime('reset_started_at')->nullable();
            $table->datetime('reset_completed_at')->nullable();

            // Cycle status
            $table->boolean('is_over_limit')->default(false);
            $table->boolean('is_current_week')->default(true);

            $table->timestamps();

            // Indexes
            $table->unique(['user_driver_detail_id', 'week_start_date']);
            $table->index(['carrier_id', 'week_start_date']);
            $table->index(['user_driver_detail_id', 'is_current_week']);
        });

        // Enhanced violations table
        Schema::table('hos_violations', function (Blueprint $table) {
            // Add FMCSA specific violation types
            $table->enum('violation_severity', ['minor', 'moderate', 'critical'])->default('moderate')->after('violation_type');

            // Penalty tracking
            $table->boolean('has_penalty')->default(false)->after('acknowledged');
            $table->enum('penalty_type', ['warning', 'suspension', 'mandatory_rest', 'none'])->default('none')->after('has_penalty');
            $table->integer('penalty_hours')->nullable()->after('penalty_type'); // Hours of mandatory rest
            $table->datetime('penalty_start')->nullable()->after('penalty_hours');
            $table->datetime('penalty_end')->nullable()->after('penalty_start');
            $table->text('penalty_notes')->nullable()->after('penalty_end');

            // Trip relation (if violation occurred during a trip)
            $table->foreignId('trip_id')->nullable()->after('hos_entry_id')->constrained('trips')->onDelete('set null');

            // FMCSA rule reference
            $table->string('fmcsa_rule_reference')->nullable()->after('violation_type'); // e.g., "37 TAC §4.11(a)"
        });

        // Update hos_entries to link with trips
        Schema::table('hos_entries', function (Blueprint $table) {
            $table->foreignId('trip_id')->nullable()->after('carrier_id')->constrained('trips')->onDelete('set null');

            // Ghost log detection
            $table->boolean('is_ghost_log')->default(false)->after('is_manual_entry');
            $table->text('ghost_log_reason')->nullable()->after('is_ghost_log');
        });

        // Update hos_configurations for FMCSA rules
        Schema::table('hos_configurations', function (Blueprint $table) {
            // FMCSA Texas Intrastate specific
            $table->boolean('fmcsa_texas_mode')->default(true)->after('is_active');
            $table->boolean('allow_24_hour_reset')->default(true)->after('fmcsa_texas_mode'); // For construction/oilfield

            // Break requirements
            $table->boolean('require_30_min_break')->default(true)->after('allow_24_hour_reset');
            $table->integer('break_after_hours')->default(8)->after('require_30_min_break'); // After 8h driving

            // Weekly cycle limits
            $table->integer('weekly_limit_60_minutes')->default(3600)->after('break_after_hours'); // 60h
            $table->integer('weekly_limit_70_minutes')->default(4200)->after('weekly_limit_60_minutes'); // 70h

            // Ghost log detection
            $table->boolean('enable_ghost_log_detection')->default(true)->after('weekly_limit_70_minutes');
            $table->integer('ghost_log_threshold_minutes')->default(30)->after('enable_ghost_log_detection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hos_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'fmcsa_texas_mode',
                'allow_24_hour_reset',
                'require_30_min_break',
                'break_after_hours',
                'weekly_limit_60_minutes',
                'weekly_limit_70_minutes',
                'enable_ghost_log_detection',
                'ghost_log_threshold_minutes',
            ]);
        });

        Schema::table('hos_entries', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
            $table->dropColumn(['trip_id', 'is_ghost_log', 'ghost_log_reason']);
        });

        Schema::table('hos_violations', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
            $table->dropColumn([
                'violation_severity',
                'has_penalty',
                'penalty_type',
                'penalty_hours',
                'penalty_start',
                'penalty_end',
                'penalty_notes',
                'trip_id',
                'fmcsa_rule_reference',
            ]);
        });

        Schema::dropIfExists('hos_weekly_cycles');

        Schema::table('hos_daily_logs', function (Blueprint $table) {
            $table->dropColumn([
                'duty_period_start',
                'duty_period_end',
                'duty_period_minutes',
                'break_minutes',
                'thirty_minute_break_taken',
                'last_10_hour_reset_at',
                'consecutive_off_duty_minutes',
            ]);
        });
    }
};
