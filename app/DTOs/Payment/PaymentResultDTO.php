<?php

namespace App\DTOs\Payment;

use App\Enums\PaymentOutcome;

final class PaymentResultDTO
{
    /**
     * @param  string|null  $transactionId  Provider's payment id (Stripe PaymentIntent pi_...).
     * @param  string|null  $chargeId  Provider's capture id (Stripe ch_...), set once money moved.
     * @param  string|null  $clientSecret  Lets the client finish 3DS; only set when REQUIRES_ACTION.
     */
    public function __construct(
        public readonly PaymentOutcome $outcome,
        public readonly string $gateway,
        public readonly ?string $transactionId = null,
        public readonly ?string $chargeId = null,
        public readonly ?string $clientSecret = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            outcome: PaymentOutcome::from($data['outcome']),
            gateway: $data['gateway'],
            transactionId: $data['transaction_id'] ?? null,
            chargeId: $data['charge_id'] ?? null,
            clientSecret: $data['client_secret'] ?? null,
            message: $data['message'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'outcome' => $this->outcome->value,
            'gateway' => $this->gateway,
            'transaction_id' => $this->transactionId,
            'charge_id' => $this->chargeId,
            'client_secret' => $this->clientSecret,
            'message' => $this->message,
        ];
    }
}
