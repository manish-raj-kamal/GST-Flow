<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gst:sync')->daily()->withoutOverlapping(); // Covers HSN and Tax Notifications
Schedule::command('gst:sync --source=https://api.example.com/gst/slabs')->weekly()->withoutOverlapping();
Schedule::command('gst:sync --source=https://api.example.com/gst/compliance')->weekly()->withoutOverlapping();
