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
        Schema::table('hos_violations', function (Blueprint $table) {
            // Forgiveness fields
            $table->boolean('is_forgiven')->default(false)->after('penalty_notes');
            $table->foreignId('forgiven_by')->nullable()->after('is_forgiven')
                ->constrained('users')->onDelete('set null');
            $table->datetime('forgiven_at')->nullable()->after('forgiven_by');
            $table->text('forgiveness_reason')->nullable()->after('forgiven_at');

            // Original trip end time (before adjustment)
            $table->datetime('original_trip_end_time')->nullable()->after('forgiveness_reason');
            // Adjusted trip end time (when the trip actually ended)
            $table->datetime('adjusted_trip_end_time')->nullable()->after('original_trip_end_time');

            // Index for filtering forgiven violations
            $table->index('is_forgiven');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hos_violations', function (Blueprint $table) {
            $table->dropIndex(['is_forgiven']);
            $table->dropForeign(['forgiven_by']);
            $table->dropColumn([
                'is_forgiven',
                'forgiven_by',
                'forgiven_at',
                'forgiveness_reason',
                'original_trip_end_time',
                'adjusted_trip_end_time',
            ]);
        });
    }
};
