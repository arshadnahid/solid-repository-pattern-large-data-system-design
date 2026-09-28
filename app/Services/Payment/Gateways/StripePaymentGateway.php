<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\Payment\PaymentRequestDTO;
use App\DTOs\Payment\PaymentResultDTO;
use App\Repositories\Payment\PaymentInterfaces\PaymentGatewayInterface;
use Illuminate\Support\Str;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function charge(PaymentRequestDTO $payment): PaymentResultDTO
    {
        // Demo only. Real code:
        // $stripe->paymentIntents->create([...], ['idempotency_key' => $payment->idempotencyKey]);
        // The transaction id below is derived from the key to mimic that: same key, same charge.

        return new PaymentResultDTO(
            success: true,
            gateway: 'stripe',
            transactionId: 'pi_'.substr(hash('sha256', $payment->idempotencyKey), 0, 24),
            message: 'Payment captured by Stripe (demo).',
        );
    }
}
