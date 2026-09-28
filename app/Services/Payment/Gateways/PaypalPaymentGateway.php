<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\Payment\PaymentRequestDTO;
use App\DTOs\Payment\PaymentResultDTO;
use App\Repositories\Payment\PaymentInterfaces\PaymentGatewayInterface;
use Illuminate\Support\Str;

class PaypalPaymentGateway implements PaymentGatewayInterface
{
    public function charge(PaymentRequestDTO $payment): PaymentResultDTO
    {
        // Demo only. Real code: create and capture an order via the PayPal Orders v2 API,
        // sending the header 'PayPal-Request-Id: '.$payment->idempotencyKey.
        // The transaction id below is derived from the key: same key, same charge.

        return new PaymentResultDTO(
            success: true,
            gateway: 'paypal',
            transactionId: 'PAYID-'.strtoupper(substr(hash('sha256', $payment->idempotencyKey), 0, 20)),
            message: 'Payment captured by PayPal (demo).',
        );
    }
}
