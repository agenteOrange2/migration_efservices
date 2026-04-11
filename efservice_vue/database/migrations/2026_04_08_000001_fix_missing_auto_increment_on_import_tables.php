<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users'                => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
            'user_carrier_details' => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
            'user_driver_details'  => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
            'vehicles'             => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
        ];

        foreach ($tables as $table => $definition) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `id` {$definition}");
        }
    }

    public function down(): void
    {
        //
    }
};
