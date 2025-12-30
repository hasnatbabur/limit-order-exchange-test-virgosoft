<?php

namespace App\Features\Orders\Providers;

use Illuminate\Support\ServiceProvider;
use App\Features\Orders\Repositories\OrderRepository;
use App\Features\Orders\Repositories\OrderRepositoryInterface;
use App\Features\Orders\Services\OrderService;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );

        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService(
                $app->make(OrderRepositoryInterface::class),
                $app->make(\App\Features\Balance\Services\AssetService::class)
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
