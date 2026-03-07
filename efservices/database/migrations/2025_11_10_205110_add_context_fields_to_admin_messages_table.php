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
        Schema::table('admin_messages', function (Blueprint $table) {
            $table->string('context_type')->nullable()->after('status');
            $table->unsignedBigInteger('context_id')->nullable()->after('context_type');
            
            $table->index(['context_type', 'context_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_messages', function (Blueprint $table) {
            $table->dropIndex(['context_type', 'context_id']);
            $table->dropColumn(['context_type', 'context_id']);
        });
    }
};
