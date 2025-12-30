<?php

namespace App\Features\Balance\Providers;

use Illuminate\Support\ServiceProvider;
use App\Features\Balance\Repositories\AssetRepository;
use App\Features\Balance\Repositories\AssetRepositoryInterface;
use App\Features\Balance\Services\AssetService;

class BalanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the repository interface to implementation
        $this->app->bind(
            AssetRepositoryInterface::class,
            AssetRepository::class
        );

        // Register the asset service as singleton
        $this->app->singleton(AssetService::class, function ($app) {
            return new AssetService($app->make(AssetRepositoryInterface::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
