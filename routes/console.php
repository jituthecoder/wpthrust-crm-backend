<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('campaigns:recover')->everyMinute()->withoutOverlapping(5);
Schedule::command('campaigns:process')->everyMinute()->withoutOverlapping(5);
Schedule::command('psi:process --concurrency=15 --limit=100')->everyMinute()->withoutOverlapping(15);
Schedule::command('emails:sync-bounces')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('inbox:sync')->everyFiveMinutes()->withoutOverlapping(10);
