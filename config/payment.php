<?php

use App\Services\Payment\Gateways\PaypalPaymentGateway;
use App\Services\Payment\Gateways\RazorpayPaymentGateway;
use App\Services\Payment\Gateways\StripePaymentGateway;

return [

    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | The key is the `payment_method` value the API accepts; the value is a
    | PaymentGatewayInterface implementation. To add a gateway, create the
    | class and register it here. No other code has to change.
    |
    */

    'gateways' => [
        'stripe' => StripePaymentGateway::class,
        'razorpay' => RazorpayPaymentGateway::class,
        'paypal' => PaypalPaymentGateway::class,
    ],

];
