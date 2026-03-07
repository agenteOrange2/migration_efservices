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
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'accepted_at')) {
                $table->datetime('accepted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('trips', 'started_at')) {
                $table->datetime('started_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('trips', 'completed_at')) {
                $table->datetime('completed_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('trips', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('completed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $columns = ['accepted_at', 'started_at', 'completed_at', 'rejection_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('trips', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
