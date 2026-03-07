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
        // Update existing trips table with new FMCSA fields
        Schema::table('trips', function (Blueprint $table) {
            // Add vehicle_id if not exists
            if (!Schema::hasColumn('trips', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('carrier_id')->constrained('vehicles')->onDelete('set null');
            }
            
            // Add created_by/updated_by if not exists
            if (!Schema::hasColumn('trips', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('vehicle_id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('trips', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            }

            // Trip Basic Info
            if (!Schema::hasColumn('trips', 'trip_number')) {
                $table->string('trip_number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('trips', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('trip_number');
            }

            // Schedule
            if (!Schema::hasColumn('trips', 'scheduled_start_date')) {
                $table->datetime('scheduled_start_date')->nullable()->after('reference_number');
            }
            if (!Schema::hasColumn('trips', 'scheduled_end_date')) {
                $table->datetime('scheduled_end_date')->nullable()->after('scheduled_start_date');
            }
            if (!Schema::hasColumn('trips', 'estimated_duration_minutes')) {
                $table->integer('estimated_duration_minutes')->nullable()->after('scheduled_end_date');
            }

            // Actual Times
            if (!Schema::hasColumn('trips', 'actual_start_time')) {
                $table->datetime('actual_start_time')->nullable()->after('estimated_duration_minutes');
            }
            if (!Schema::hasColumn('trips', 'actual_end_time')) {
                $table->datetime('actual_end_time')->nullable()->after('actual_start_time');
            }
            if (!Schema::hasColumn('trips', 'actual_duration_minutes')) {
                $table->integer('actual_duration_minutes')->nullable()->after('actual_end_time');
            }

            // Origin & Destination
            if (!Schema::hasColumn('trips', 'origin_address')) {
                $table->text('origin_address')->nullable()->after('actual_duration_minutes');
            }
            if (!Schema::hasColumn('trips', 'origin_latitude')) {
                $table->decimal('origin_latitude', 10, 6)->nullable()->after('origin_address');
            }
            if (!Schema::hasColumn('trips', 'origin_longitude')) {
                $table->decimal('origin_longitude', 10, 6)->nullable()->after('origin_latitude');
            }
            if (!Schema::hasColumn('trips', 'destination_address')) {
                $table->text('destination_address')->nullable()->after('origin_longitude');
            }
            if (!Schema::hasColumn('trips', 'destination_latitude')) {
                $table->decimal('destination_latitude', 10, 6)->nullable()->after('destination_address');
            }
            if (!Schema::hasColumn('trips', 'destination_longitude')) {
                $table->decimal('destination_longitude', 10, 6)->nullable()->after('destination_latitude');
            }

            // Trip Details
            if (!Schema::hasColumn('trips', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('trips', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('trips', 'driver_notes')) {
                $table->text('driver_notes')->nullable();
            }

            // Load Information
            if (!Schema::hasColumn('trips', 'load_type')) {
                $table->string('load_type')->nullable();
            }
            if (!Schema::hasColumn('trips', 'load_weight')) {
                $table->decimal('load_weight', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('trips', 'load_unit')) {
                $table->string('load_unit')->default('lbs');
            }

            // Compliance & Tracking
            if (!Schema::hasColumn('trips', 'pre_trip_inspection_completed')) {
                $table->boolean('pre_trip_inspection_completed')->default(false);
            }
            if (!Schema::hasColumn('trips', 'pre_trip_inspection_at')) {
                $table->datetime('pre_trip_inspection_at')->nullable();
            }
            if (!Schema::hasColumn('trips', 'post_trip_inspection_completed')) {
                $table->boolean('post_trip_inspection_completed')->default(false);
            }
            if (!Schema::hasColumn('trips', 'post_trip_inspection_at')) {
                $table->datetime('post_trip_inspection_at')->nullable();
            }

            // GPS Tracking
            if (!Schema::hasColumn('trips', 'gps_tracking_enabled')) {
                $table->boolean('gps_tracking_enabled')->default(true);
            }
            if (!Schema::hasColumn('trips', 'gps_ping_interval_seconds')) {
                $table->integer('gps_ping_interval_seconds')->default(300);
            }

            // Violations & Penalties
            if (!Schema::hasColumn('trips', 'has_violations')) {
                $table->boolean('has_violations')->default(false);
            }
            if (!Schema::hasColumn('trips', 'forgot_to_close')) {
                $table->boolean('forgot_to_close')->default(false);
            }
            if (!Schema::hasColumn('trips', 'penalty_notes')) {
                $table->text('penalty_notes')->nullable();
            }

            // Metadata
            if (!Schema::hasColumn('trips', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable();
            }
            if (!Schema::hasColumn('trips', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('trips', 'cancelled_at')) {
                $table->datetime('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('trips', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Trip GPS Tracking Points
        if (!Schema::hasTable('trip_gps_points')) {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_gps_points');
        
        Schema::table('trips', function (Blueprint $table) {
            $columns = [
                'vehicle_id', 'created_by', 'updated_by', 'trip_number', 'reference_number',
                'scheduled_start_date', 'scheduled_end_date', 'estimated_duration_minutes',
                'actual_start_time', 'actual_end_time', 'actual_duration_minutes',
                'origin_address', 'origin_latitude', 'origin_longitude',
                'destination_address', 'destination_latitude', 'destination_longitude',
                'description', 'notes', 'driver_notes', 'load_type', 'load_weight', 'load_unit',
                'pre_trip_inspection_completed', 'pre_trip_inspection_at',
                'post_trip_inspection_completed', 'post_trip_inspection_at',
                'gps_tracking_enabled', 'gps_ping_interval_seconds',
                'has_violations', 'forgot_to_close', 'penalty_notes',
                'cancellation_reason', 'cancelled_by', 'cancelled_at', 'deleted_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('trips', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
