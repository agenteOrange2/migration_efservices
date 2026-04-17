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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->nullable()->unique();
            $table->string('reference_number')->nullable();
            $table->foreignId('user_driver_detail_id')->constrained('user_driver_details')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('carriers')->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            // Schedule
            $table->datetime('scheduled_start_date')->nullable();
            $table->datetime('scheduled_end_date')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();

            // Actual Times
            $table->datetime('actual_start_time')->nullable();
            $table->datetime('actual_end_time')->nullable();
            $table->integer('actual_duration_minutes')->nullable();

            // Legacy time fields
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('destination')->nullable();
            $table->time('estimated_duration')->nullable();
            $table->time('total_duration')->nullable();

            // Origin & Destination
            $table->text('origin_address')->nullable();
            $table->decimal('origin_latitude', 10, 6)->nullable();
            $table->decimal('origin_longitude', 10, 6)->nullable();
            $table->text('destination_address')->nullable();
            $table->decimal('destination_latitude', 10, 6)->nullable();
            $table->decimal('destination_longitude', 10, 6)->nullable();

            // Status & Timeline
            $table->string('status')->default('In Progress');
            $table->boolean('is_quick_trip')->default(false);
            $table->boolean('requires_completion')->default(false);
            $table->datetime('completed_info_at')->nullable();
            $table->foreignId('completed_info_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('accepted_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Trip Details
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->text('driver_notes')->nullable();

            // Load Information
            $table->string('load_type')->nullable();
            $table->decimal('load_weight', 10, 2)->nullable();
            $table->string('load_unit')->default('lbs');

            // Compliance & Tracking
            $table->boolean('pre_trip_inspection_completed')->default(false);
            $table->datetime('pre_trip_inspection_at')->nullable();
            $table->json('pre_trip_inspection_data')->nullable();
            $table->text('pre_trip_remarks')->nullable();
            $table->boolean('pre_trip_defects_found')->default(false);
            $table->boolean('pre_trip_defects_corrected')->default(false);
            $table->text('pre_trip_defects_corrected_notes')->nullable();
            $table->boolean('pre_trip_defects_not_need_correction')->default(false);
            $table->text('pre_trip_defects_not_need_correction_notes')->nullable();
            $table->text('pre_trip_driver_signature')->nullable();

            $table->boolean('post_trip_inspection_completed')->default(false);
            $table->datetime('post_trip_inspection_at')->nullable();
            $table->json('post_trip_inspection_data')->nullable();
            $table->text('post_trip_remarks')->nullable();
            $table->boolean('post_trip_defects_found')->default(false);
            $table->boolean('post_trip_defects_corrected')->default(false);
            $table->text('post_trip_defects_corrected_notes')->nullable();
            $table->boolean('post_trip_defects_not_need_correction')->default(false);
            $table->text('post_trip_defects_not_need_correction_notes')->nullable();
            $table->text('post_trip_driver_signature')->nullable();

            $table->boolean('has_trailer')->default(false);
            $table->boolean('vehicle_condition_satisfactory')->default(true);

            // GPS Tracking
            $table->boolean('gps_tracking_enabled')->default(true);
            $table->integer('gps_ping_interval_seconds')->default(300);

            // Violations & Penalties
            $table->boolean('has_violations')->default(false);
            $table->boolean('forgot_to_close')->default(false);
            $table->text('penalty_notes')->nullable();

            // HOS Auto-stop fields
            $table->timestamp('auto_stopped_at')->nullable();
            $table->string('auto_stop_reason')->nullable();
            $table->timestamp('hos_penalty_end_time')->nullable();

            // Metadata
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('cancelled_at')->nullable();
            $table->softDeletes();

            $table->timestamps();

            // Performance indexes
            $table->index('user_driver_detail_id', 'idx_trips_driver_id');
            $table->index('vehicle_id', 'idx_trips_vehicle_id');
            $table->index('status', 'idx_trips_status');
            $table->index('scheduled_start_date', 'idx_trips_start_date');
            $table->index(['user_driver_detail_id', 'status'], 'idx_trips_driver_status');
            $table->index(['is_quick_trip', 'requires_completion'], 'trips_quick_trip_status_index');
        });

        // Trip GPS Tracking Points
        Schema::create('trip_gps_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->decimal('speed', 5, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            $table->string('formatted_address')->nullable();
            $table->datetime('recorded_at');
            $table->timestamps();

            $table->index(['trip_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_gps_points');
        Schema::dropIfExists('trips');
    }
};
