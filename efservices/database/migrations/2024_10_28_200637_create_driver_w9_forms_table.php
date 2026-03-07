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
        Schema::create('driver_w9_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_driver_detail_id')->constrained()->onDelete('cascade');

            // Line 1
            $table->string('name');

            // Line 2
            $table->string('business_name')->nullable();

            // Line 3a - Federal tax classification
            $table->enum('tax_classification', [
                'individual', 'c_corporation', 's_corporation',
                'partnership', 'trust_estate', 'llc', 'other',
            ]);
            $table->char('llc_classification', 1)->nullable(); // C, S, or P
            $table->string('other_classification')->nullable();

            // Line 3b
            $table->boolean('has_foreign_partners')->default(false);

            // Line 4
            $table->string('exempt_payee_code')->nullable();
            $table->string('fatca_exemption_code')->nullable();

            // Line 5
            $table->string('address');

            // Line 6
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);

            // Line 7
            $table->string('account_numbers')->nullable();

            // Part I - TIN (encrypted at rest)
            $table->enum('tin_type', ['ssn', 'ein']);
            $table->text('tin_encrypted');
            $table->longText('signature')->nullable();

            // Metadata
            $table->date('signed_date')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_w9_forms');
    }
};
