<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('/terms', 'legal/Terms')->name('terms');
Route::inertia('/privacy', 'legal/Privacy')->name('privacy');

require __DIR__.'/settings.php';
