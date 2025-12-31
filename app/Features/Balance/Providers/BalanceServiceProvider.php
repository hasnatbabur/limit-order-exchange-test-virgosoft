<?php

namespace App\Features\Balance\Providers;

use Illuminate\Support\ServiceProvider;
use App\Features\Balance\Repositories\AssetRepository;
use App\Features\Balance\Repositories\AssetRepositoryInterface;
use App\Features\Balance\Services\AssetService;
use App\Features\Balance\Services\AssetRegistryService;

class BalanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            AssetRepositoryInterface::class,
            AssetRepository::class
        );

        // Register asset registry service as singleton
        $this->app->singleton(AssetRegistryService::class, function ($app) {
            return new AssetRegistryService();
        });

        // Register asset service as singleton with both dependencies
        $this->app->singleton(AssetService::class, function ($app) {
            return new AssetService(
                $app->make(AssetRepositoryInterface::class),
                $app->make(AssetRegistryService::class)
            );
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
