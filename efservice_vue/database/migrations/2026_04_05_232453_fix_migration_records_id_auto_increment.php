<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `migration_records` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        //
    }
};
