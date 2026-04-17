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
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');
            $table->string('subject');
            $table->text('message');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->enum('status', ['draft', 'sent', 'delivered', 'failed'])->default('draft');
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['sender_type', 'sender_id'], 'admin_messages_sender_index');
            $table->index(['status']);
            $table->index(['priority']);
            $table->index(['sent_at']);
            $table->index(['created_at']);
            $table->index(['context_type', 'context_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_messages');
    }
};
