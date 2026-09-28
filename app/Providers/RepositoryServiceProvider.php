<?php

namespace App\Providers;

use App\Repositories\Order\OrderInterfaces\IdempotencyKeyRepositoryInterface;
use App\Repositories\Order\OrderInterfaces\OrderRepositoryInterface;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Payment\PaymentInterfaces\PaymentRepositoryInterface;
use App\Repositories\Payment\PaymentRepository;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Interface => implementation. To change storage (e.g. to Eloquent),
     * write a new class for the interface and swap it here.
     */
    public array $bindings = [
        OrderRepositoryInterface::class => OrderRepository::class,
        IdempotencyKeyRepositoryInterface::class => OrderRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(PaymentGatewayFactory::class, fn ($app) => new PaymentGatewayFactory(
            $app,
            $app['config']->get('payment.gateways', []),
        ));
    }
}
