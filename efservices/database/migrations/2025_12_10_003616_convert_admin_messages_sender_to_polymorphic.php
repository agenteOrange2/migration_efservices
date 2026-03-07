<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_messages', function (Blueprint $table) {
            // 1. Add sender_type column (nullable initially)
            $table->string('sender_type')->nullable()->after('id');
            
            // 2. Drop foreign key constraint on sender_id before modification
            $table->dropForeign(['sender_id']);
        });

        // 3. Migrate existing data: set sender_type to User model for all existing records
        DB::table('admin_messages')->update([
            'sender_type' => 'App\\Models\\User'
        ]);

        Schema::table('admin_messages', function (Blueprint $table) {
            // 4. Make sender_type NOT NULL now that data is migrated
            $table->string('sender_type')->nullable(false)->change();
            
            // 5. Add composite index for polymorphic relationship
            $table->index(['sender_type', 'sender_id'], 'admin_messages_sender_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_messages', function (Blueprint $table) {
            // 1. Drop the composite index
            $table->dropIndex('admin_messages_sender_index');
            
            // 2. Drop sender_type column
            $table->dropColumn('sender_type');
            
            // 3. Re-add foreign key constraint on sender_id
            $table->foreign('sender_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};
