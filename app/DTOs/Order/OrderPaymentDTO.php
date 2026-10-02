<?php

namespace App\DTOs\Order;

/**
 * What the client sends to pay for the order. The amount is never taken
 * from here: the server prices the order and charges that.
 */
final class OrderPaymentDTO
{
    /**
     * @param  string  $paymentMethodId  Token created by Stripe.js (pm_...), never raw card data.
     * @param  string|null  $returnUrl  Where Stripe sends the customer back after a 3DS challenge.
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $method,
        public readonly string $paymentMethodId,
        public readonly bool $savePaymentMethod,
        public readonly ?string $returnUrl,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'],
            method: $data['method'],
            paymentMethodId: $data['stripe_payment_method_id'],
            savePaymentMethod: (bool) ($data['save_payment_method'] ?? false),
            returnUrl: $data['return_url'] ?? null,
        );
    }
}
