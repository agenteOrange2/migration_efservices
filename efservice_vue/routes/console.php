<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-suspend vehicles with expired registration or annual inspection - daily at 6:00 AM
Schedule::command('vehicles:auto-suspend-expired')
    ->dailyAt('06:00')
    ->withoutOverlapping();

Schedule::command('notifications:dispatch-operational-alerts')
    ->dailyAt('07:00')
    ->withoutOverlapping();
