<?php

namespace App\DTOs\Payment;

final class PaymentRequestDTO
{
    /**
     * @param  int  $amountMinor  Amount in the currency's smallest unit (cents), as Stripe expects it.
     * @param  string  $idempotencyKey  Sent to the provider so a retried charge is never captured twice.
     * @param  string|null  $paymentMethodId  Provider token for the card (Stripe pm_...).
     * @param  string|null  $returnUrl  Where the provider sends the customer back after 3DS.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly int $amountMinor,
        public readonly string $currency,
        public readonly string $idempotencyKey,
        public readonly ?string $paymentMethodId = null,
        public readonly ?string $returnUrl = null,
    ) {
    }
}
