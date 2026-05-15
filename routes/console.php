<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hsn:sync')->daily()->withoutOverlapping();
Schedule::command('gst:sync-tax-slabs')->weeklyOn(1, '03:00')->withoutOverlapping();
Schedule::command('gst:sync-compliance-updates')->weeklyOn(1, '03:30')->withoutOverlapping();
Schedule::command('gst:sync-tax-notifications')->dailyAt('04:00')->withoutOverlapping();
