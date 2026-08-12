<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('campaigns:recover')->everyMinute()->withoutOverlapping();
Schedule::command('campaigns:process')->everyMinute()->withoutOverlapping();
Schedule::command('psi:fetch --limit=20')->everyFiveMinutes()->withoutOverlapping();
