<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the migration_records table for tracking driver migrations between carriers.
     * This table serves as an immutable audit trail for compliance purposes (FMCSA, DOT).
     */
    public function up(): void
    {
        Schema::create('migration_records', function (Blueprint $table) {
            $table->id();
            
            // Driver being migrated (references users table, not user_driver_details)
            $table->foreignId('driver_user_id')
                ->constrained('users')
                ->onDelete('restrict');
            
            // Source carrier (where driver is coming from)
            $table->foreignId('source_carrier_id')
                ->constrained('carriers')
                ->onDelete('restrict');
            
            // Target carrier (where driver is going to)
            $table->foreignId('target_carrier_id')
                ->constrained('carriers')
                ->onDelete('restrict');
            
            // Migration timestamp
            $table->timestamp('migrated_at');
            
            // Admin who performed the migration
            $table->foreignId('migrated_by_user_id')
                ->constrained('users')
                ->onDelete('restrict');
            
            // Optional reason for migration
            $table->string('reason', 255)->nullable();
            
            // Optional additional notes
            $table->text('notes')->nullable();
            
            // Complete snapshot of driver data at migration time (JSON)
            $table->json('driver_snapshot');
            
            // Migration status: completed or rolled_back
            $table->enum('status', ['completed', 'rolled_back'])->default('completed');
            
            // Rollback information (only populated if rolled back)
            $table->timestamp('rolled_back_at')->nullable();
            $table->foreignId('rolled_back_by_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->text('rollback_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('driver_user_id', 'idx_migration_records_driver');
            $table->index('source_carrier_id', 'idx_migration_records_source');
            $table->index('target_carrier_id', 'idx_migration_records_target');
            $table->index('migrated_at', 'idx_migration_records_date');
            $table->index('status', 'idx_migration_records_status');
            $table->index(['source_carrier_id', 'migrated_at'], 'idx_migration_records_source_date');
            $table->index(['target_carrier_id', 'migrated_at'], 'idx_migration_records_target_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_records');
    }
};
