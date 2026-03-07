<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the archive_access_logs table for tracking all access to driver archives.
     * This table provides an audit trail for compliance purposes (FMCSA, DOT).
     */
    public function up(): void
    {
        Schema::create('archive_access_logs', function (Blueprint $table) {
            $table->id();
            
            // Archive being accessed
            $table->foreignId('driver_archive_id')
                ->constrained('driver_archives')
                ->onDelete('cascade');
            
            // User who accessed the archive
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // Carrier context (for filtering and reporting)
            $table->foreignId('carrier_id')
                ->constrained('carriers')
                ->onDelete('cascade');
            
            // Type of access: 'view' or 'download'
            $table->enum('action_type', ['view', 'download']);
            
            // Request metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Additional metadata (JSON)
            // For downloads: file_size, download_duration, etc.
            $table->json('metadata')->nullable();
            
            // Timestamp of access
            $table->timestamp('accessed_at');
            
            // Indexes for efficient querying
            $table->index(['driver_archive_id', 'accessed_at'], 'idx_archive_access_logs_archive_date');
            $table->index(['carrier_id', 'accessed_at'], 'idx_archive_access_logs_carrier_date');
            $table->index(['user_id', 'accessed_at'], 'idx_archive_access_logs_user_date');
            $table->index('action_type', 'idx_archive_access_logs_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_access_logs');
    }
};
