<?php

namespace App\DTOs\Payment;

final class PaymentRequestDTO
{
    /**
     * @param  string  $idempotencyKey  Sent to the provider so a retried charge is never captured twice.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $idempotencyKey,
    ) {
    }
}
