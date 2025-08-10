<?php

use App\Console\Commands\MarkStudentsAsAbsent;
use App\Console\Commands\SendAbsentNotifications;
use App\Helpers\SettingsHelper;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Get the configured time for sending absent notifications.
$absentNotificationTime = SettingsHelper::get('notifications.absent.notification_time', '09:00');

// Schedule the command to mark students as absent 5 minutes before sending notifications.
Schedule::command(MarkStudentsAsAbsent::class)->dailyAt(
    \Carbon\Carbon::parse($absentNotificationTime)->subMinutes(5)->format('H:i')
);

// Schedule the command to send absent notifications.
Schedule::command(SendAbsentNotifications::class)->dailyAt($absentNotificationTime);

// Get the configured default end time for check-in.
$timeInEnd = SettingsHelper::get('attendance.defaults.time_in_end', '08:00');

// Schedule the command to send a summary of sick/permit students to homeroom teachers.
// Runs daily at the configured end of check-in time.
Schedule::command(\App\Console\Commands\SendClassLeaveSummaryToHomeroomTeacher::class)->dailyAt($timeInEnd);
