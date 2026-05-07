<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_document_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('driver_id')->constrained('user_driver_details')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('category', 100);
            $table->string('status', 50)->default('pending');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'category']);
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_document_status');
    }
};
