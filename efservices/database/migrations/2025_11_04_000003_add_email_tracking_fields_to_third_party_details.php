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
        Schema::table('third_party_details', function (Blueprint $table) {
            // Add timestamp for when email was sent
            $table->timestamp('email_sent_at')->nullable()->after('email_sent');
            
            // Add token for email verification
            $table->string('email_token', 64)->nullable()->after('email_sent_at');
            
            // Add timestamp for when email was verified
            $table->timestamp('email_verified_at')->nullable()->after('email_token');
            
            // Add index on email_token for fast lookups during verification
            $table->index('email_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('third_party_details', function (Blueprint $table) {
            $table->dropIndex(['email_token']);
            $table->dropColumn(['email_sent_at', 'email_token', 'email_verified_at']);
        });
    }
};
