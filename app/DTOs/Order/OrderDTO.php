<?php

namespace App\DTOs\Order;

use App\Enums\OrderStatus;

/**
 * Output data of the order repository. Every repository implementation
 * returns this same shape, so callers never depend on the storage.
 */
final class OrderDTO
{
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public readonly OrderStatus $status,
        public readonly string $paymentMethod,
        public readonly string $currency,
        public readonly float $totalAmount,
        public readonly array $items,
        public readonly ?string $transactionId,
        public readonly string $createdAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: (int) $data['user_id'],
            status: OrderStatus::from($data['status']),
            paymentMethod: $data['payment_method'],
            currency: $data['currency'],
            totalAmount: (float) $data['total_amount'],
            items: $data['items'],
            transactionId: $data['transaction_id'] ?? null,
            createdAt: (string) $data['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'status' => $this->status->value,
            'payment_method' => $this->paymentMethod,
            'currency' => $this->currency,
            'total_amount' => $this->totalAmount,
            'items' => $this->items,
            'transaction_id' => $this->transactionId,
            'created_at' => $this->createdAt,
        ];
    }
}
