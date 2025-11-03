<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: Expire past reservations every minute
Schedule::command('reservations:auto-expire')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule: Send reservation reminders every minute
Schedule::command('reservations:send-reminders')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();