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
        // Add indexes to master_companies table for search optimization
        Schema::table('master_companies', function (Blueprint $table) {
            $table->index('company_name', 'idx_master_companies_name');
            $table->index('city', 'idx_master_companies_city');
            $table->index('state', 'idx_master_companies_state');
            $table->index(['company_name', 'city', 'state'], 'idx_master_companies_search');
            $table->index('created_at', 'idx_master_companies_created_at');
        });

        // Add indexes to driver_employment_companies table for filtering and sorting
        Schema::table('driver_employment_companies', function (Blueprint $table) {
            $table->index('user_driver_detail_id', 'idx_employment_driver_id');
            $table->index('master_company_id', 'idx_employment_company_id');
            $table->index('email_sent', 'idx_employment_email_sent');
            $table->index('employed_to', 'idx_employment_employed_to');
            $table->index(['user_driver_detail_id', 'employed_to'], 'idx_employment_driver_date');
            $table->index(['user_driver_detail_id', 'email_sent'], 'idx_employment_driver_email');
        });

        // Add indexes to driver_unemployment_periods table for sorting
        Schema::table('driver_unemployment_periods', function (Blueprint $table) {
            $table->index('user_driver_detail_id', 'idx_unemployment_driver_id');
            $table->index('end_date', 'idx_unemployment_end_date');
            $table->index(['user_driver_detail_id', 'end_date'], 'idx_unemployment_driver_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_companies', function (Blueprint $table) {
            $table->dropIndex('idx_master_companies_name');
            $table->dropIndex('idx_master_companies_city');
            $table->dropIndex('idx_master_companies_state');
            $table->dropIndex('idx_master_companies_search');
            $table->dropIndex('idx_master_companies_created_at');
        });

        Schema::table('driver_employment_companies', function (Blueprint $table) {
            $table->dropIndex('idx_employment_driver_id');
            $table->dropIndex('idx_employment_company_id');
            $table->dropIndex('idx_employment_email_sent');
            $table->dropIndex('idx_employment_employed_to');
            $table->dropIndex('idx_employment_driver_date');
            $table->dropIndex('idx_employment_driver_email');
        });

        Schema::table('driver_unemployment_periods', function (Blueprint $table) {
            $table->dropIndex('idx_unemployment_driver_id');
            $table->dropIndex('idx_unemployment_end_date');
            $table->dropIndex('idx_unemployment_driver_date');
        });
    }
};
