<?php

use App\Console\Commands\MarkStudentsAsAbsent;
use App\Console\Commands\SendAbsentNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule commands to run every minute and let them handle their own timing logic
// This prevents database access during application bootstrap

// Check every minute if it's time to mark students as absent
Schedule::command(MarkStudentsAsAbsent::class)->everyMinute();

// Check every minute if it's time to send absent notifications  
Schedule::command(SendAbsentNotifications::class)->everyMinute();

// Check every minute if it's time to send class leave summary
Schedule::command(\App\Console\Commands\SendClassLeaveSummaryToHomeroomTeacher::class)->everyMinute();

// Check every minute if it's time to send monthly discipline summary to Student Affairs (self-gated by time)
Schedule::command(\App\Console\Commands\SendMonthlyStudentDisciplineSummary::class)->everyMinute();
