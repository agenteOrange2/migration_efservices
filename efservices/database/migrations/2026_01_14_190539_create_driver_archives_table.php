<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the driver_archives table for storing historical driver records.
     * When a driver migrates to a new carrier, their complete data is archived
     * in the source carrier for audit and legal compliance purposes.
     */
    public function up(): void
    {
        Schema::create('driver_archives', function (Blueprint $table) {
            $table->id();
            
            // Reference to the original user_driver_details record
            $table->unsignedBigInteger('original_user_driver_detail_id');
            
            // User who was the driver
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            
            // Carrier where this archive belongs (source carrier)
            $table->foreignId('carrier_id')
                ->constrained('carriers')
                ->onDelete('restrict');
            
            // Link to the migration record (nullable for non-migration archives)
            $table->foreignId('migration_record_id')
                ->nullable()
                ->constrained('migration_records')
                ->onDelete('restrict');
            
            // When the archive was created
            $table->timestamp('archived_at');
            
            // Reason for archiving: migration, termination, etc.
            $table->string('archive_reason', 50)->default('migration');
            
            // ============================================
            // JSON Snapshots of all driver data
            // ============================================
            
            // Personal information snapshot (name, phone, DOB, hire_date, etc.)
            $table->json('driver_data_snapshot');
            
            // Licenses and endorsements
            $table->json('licenses_snapshot')->nullable();
            
            // Medical qualification data
            $table->json('medical_snapshot')->nullable();
            
            // Certifications
            $table->json('certifications_snapshot')->nullable();
            
            // Employment history (companies, periods, etc.)
            $table->json('employment_history_snapshot')->nullable();
            
            // Training records
            $table->json('training_snapshot')->nullable();
            
            // Drug/alcohol testing records
            $table->json('testing_snapshot')->nullable();
            
            // Accident records
            $table->json('accidents_snapshot')->nullable();
            
            // Traffic convictions
            $table->json('convictions_snapshot')->nullable();
            
            // Inspection records
            $table->json('inspections_snapshot')->nullable();
            
            // Hours of Service summary
            $table->json('hos_snapshot')->nullable();
            
            // Vehicle assignment history
            $table->json('vehicle_assignments_snapshot')->nullable();
            
            // Archive status: archived or restored (for rollback)
            $table->enum('status', ['archived', 'restored'])->default('archived');
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('carrier_id', 'idx_driver_archives_carrier');
            $table->index('user_id', 'idx_driver_archives_user');
            $table->index('archived_at', 'idx_driver_archives_archived_at');
            $table->index('status', 'idx_driver_archives_status');
            $table->index('archive_reason', 'idx_driver_archives_reason');
            $table->index(['carrier_id', 'archived_at'], 'idx_driver_archives_carrier_date');
            $table->index(['carrier_id', 'status'], 'idx_driver_archives_carrier_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_archives');
    }
};
