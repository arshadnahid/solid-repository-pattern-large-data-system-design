<?php

namespace App\Providers;

use App\Repositories\Coupon\CouponInterfaces\CouponRepositoryInterface;
use App\Repositories\Coupon\CouponRepository;
use App\Repositories\Order\IdempotencyKeyRepository;
use App\Repositories\Order\OrderInterfaces\IdempotencyKeyRepositoryInterface;
use App\Repositories\Order\OrderInterfaces\OrderRepositoryInterface;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Payment\PaymentInterfaces\PaymentRepositoryInterface;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Product\ProductInterfaces\ProductStockRepositoryInterface;
use App\Repositories\Product\ProductStockRepository;
use App\Services\Order\OrderPricingService;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Interface => implementation. To change storage, write a new class
     * for the interface and swap it here.
     */
    public array $bindings = [
        OrderRepositoryInterface::class => OrderRepository::class,
        IdempotencyKeyRepositoryInterface::class => IdempotencyKeyRepository::class,
        ProductStockRepositoryInterface::class => ProductStockRepository::class,
        CouponRepositoryInterface::class => CouponRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(PaymentGatewayFactory::class, fn ($app) => new PaymentGatewayFactory(
            $app,
            $app['config']->get('payment.gateways', []),
        ));

        $this->app->when(OrderPricingService::class)
            ->needs('$taxRate')
            ->giveConfig('order.tax_rate');
    }
}
