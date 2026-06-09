<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hubspot:sync-candidates')
    ->dailyAt('11:00')
    ->description('Retrieve and synchronize candidates/students from HubSpot');
