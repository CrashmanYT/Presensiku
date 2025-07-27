<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Contracts\DeviceRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\DeviceRepository;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
