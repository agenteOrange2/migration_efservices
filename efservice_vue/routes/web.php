<?php

use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::post('/contact', [LandingPageController::class, 'submitContact'])->name('landing.contact.store');
Route::post('/plan-request', [LandingPageController::class, 'submitPlanRequest'])->name('landing.plan-request.store');

Route::inertia('/terms', 'legal/Terms')->name('terms');
Route::inertia('/privacy', 'legal/Privacy')->name('privacy');

require __DIR__.'/settings.php';
