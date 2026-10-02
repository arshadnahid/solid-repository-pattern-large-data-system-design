<?php

namespace App\DTOs\Order;

/**
 * Order totals as decimal strings, so no amount goes through a float.
 * From the request these are the totals the client showed the customer;
 * the server compares them with its own and rejects the order on mismatch.
 */
final class OrderTotalsDTO
{
    public function __construct(
        public readonly string $subtotal,
        public readonly string $shipping,
        public readonly string $tax,
        public readonly string $discount,
        public readonly string $total,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            subtotal: (string) $data['subtotal'],
            shipping: (string) $data['shipping'],
            tax: (string) $data['tax'],
            discount: (string) $data['discount'],
            total: (string) $data['total'],
        );
    }

    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'shipping' => $this->shipping,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
        ];
    }
}
