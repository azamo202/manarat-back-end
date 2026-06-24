<?php

use App\Console\Commands\ExpireTimedOutAttempts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire timed-out quiz attempts every minute and trigger async grading
Schedule::command(ExpireTimedOutAttempts::class)->everyMinute();
