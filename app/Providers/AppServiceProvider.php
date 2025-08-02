<?php

namespace App\Providers;

use App\Models\StudentAttendance;
use App\Observers\StudentAttendanceObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StudentAttendance::observe(StudentAttendanceObserver::class);
    }
}
