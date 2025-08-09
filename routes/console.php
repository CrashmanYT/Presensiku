<?php

use App\Console\Commands\SendAbsentNotifications;
use App\Helpers\SettingsHelper;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the command to send absent notifications
Schedule::command(SendAbsentNotifications::class)->dailyAt(
    SettingsHelper::get('notifications.absent.notification_time', '09:00')
);
