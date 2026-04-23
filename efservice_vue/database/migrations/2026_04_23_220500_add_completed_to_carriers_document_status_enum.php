<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `carriers`
            MODIFY `document_status` ENUM('pending', 'in_progress', 'completed', 'skipped')
            NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE `carriers`
            MODIFY `document_status` ENUM('pending', 'in_progress', 'skipped')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
