<?php

namespace App\DTOs\Payment;

final class PaymentResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly string $gateway,
        public readonly ?string $transactionId = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) $data['success'],
            gateway: $data['gateway'],
            transactionId: $data['transaction_id'] ?? null,
            message: $data['message'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'gateway' => $this->gateway,
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
        ];
    }
}
