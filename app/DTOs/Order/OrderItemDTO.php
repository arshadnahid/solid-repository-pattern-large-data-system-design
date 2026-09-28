<?php

namespace App\DTOs\Order;

final class OrderItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $name,
        public readonly int $quantity,
        public readonly float $unitPrice,
    ) {
    }

    public static function fromArray(array $item): self
    {
        return new self(
            productId: (int) $item['product_id'],
            name: $item['name'],
            quantity: (int) $item['quantity'],
            unitPrice: (float) $item['unit_price'],
        );
    }

    public function subtotal(): float
    {
        return round($this->quantity * $this->unitPrice, 2);
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'subtotal' => $this->subtotal(),
        ];
    }
}
