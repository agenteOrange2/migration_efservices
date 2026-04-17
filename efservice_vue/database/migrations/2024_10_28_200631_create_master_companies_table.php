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
        Schema::create('master_companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('contact')->nullable();
            $table->string('phone')->nullable();            
            $table->string('email')->nullable();
            $table->string('fax')->nullable();
            $table->timestamps();

            // Performance indexes for search optimization
            $table->index('company_name', 'idx_master_companies_name');
            $table->index('city', 'idx_master_companies_city');
            $table->index('state', 'idx_master_companies_state');
            $table->index(['company_name', 'city', 'state'], 'idx_master_companies_search');
            $table->index('created_at', 'idx_master_companies_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_companies');
    }
};
