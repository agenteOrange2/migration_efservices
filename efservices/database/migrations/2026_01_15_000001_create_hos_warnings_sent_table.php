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
        Schema::create('hos_warnings_sent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->string('warning_type', 50);
            $table->text('message')->nullable();
            $table->timestamp('sent_at');
            
            // Index for quick lookups
            $table->index(['driver_id', 'warning_type', 'sent_at']);
            
            // Foreign key
            $table->foreign('driver_id')
                ->references('id')
                ->on('user_driver_details')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hos_warnings_sent');
    }
};
