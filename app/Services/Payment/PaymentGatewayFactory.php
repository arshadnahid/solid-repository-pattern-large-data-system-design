<?php

namespace App\Services\Payment;

use App\Exceptions\UnsupportedPaymentMethodException;
use App\Repositories\Payment\PaymentInterfaces\PaymentGatewayInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves a gateway by name from config/payment.php. Adding a new
 * gateway means a new class plus one config line; this class never changes.
 */
class PaymentGatewayFactory
{
    public function __construct(
        private readonly Container $container,
        private readonly array $gateways,
    ) {
    }

    public function make(string $method): PaymentGatewayInterface
    {
        $class = $this->gateways[$method] ?? throw UnsupportedPaymentMethodException::for($method);

        return $this->container->make($class);
    }
}
