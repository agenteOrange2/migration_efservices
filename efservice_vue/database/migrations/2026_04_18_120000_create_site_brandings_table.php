<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_brandings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('EF Services');
            $table->string('login_title')->default('Welcome back');
            $table->string('login_heading')->default('Transportation compliance in one place');
            $table->text('login_subtitle')->nullable();
            $table->text('login_description')->nullable();
            $table->timestamps();
        });

        DB::table('site_brandings')->insert([
            'id' => 1,
            'app_name' => 'EF Services',
            'login_title' => 'Welcome back',
            'login_subtitle' => 'Sign in to access your transportation compliance workspace.',
            'login_heading' => 'Transportation compliance in one place',
            'login_description' => 'Manage drivers, vehicles, documents, and trips with a single operational view.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_brandings');
    }
};
