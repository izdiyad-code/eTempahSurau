<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily reminder one day before each approved event (FR12)
\Illuminate\Support\Facades\Schedule::command('bookings:send-reminders')->dailyAt('09:00');
