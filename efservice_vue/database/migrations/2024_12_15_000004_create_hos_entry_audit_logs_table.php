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
        Schema::create('hos_entry_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hos_entry_id')->constrained('hos_entries')->onDelete('cascade');
            $table->foreignId('modified_by')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('original_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            // Index for querying audit history
            $table->index('hos_entry_id');
            $table->index('modified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hos_entry_audit_logs');
    }
};
