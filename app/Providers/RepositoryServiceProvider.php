<?php

namespace App\Providers;

use App\Contracts\DeviceRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\SettingsRepositoryInterface;
use App\Repositories\DeviceRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingsRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(DeviceRepositoryInterface::class, DeviceRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
