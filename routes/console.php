<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use App\Jobs\HandleLeaveExpiration;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('leave:expire', function () {
    // You can log or add additional information if needed
    dispatch(new HandleLeaveExpiration());
})->purpose('Handle leave expiration logic for expired leave accruals');

// Schedule the job to run daily
app(Schedule::class)->job(new HandleLeaveExpiration)->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
