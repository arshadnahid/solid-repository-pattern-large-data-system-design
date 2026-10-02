<?php

namespace App\DTOs\Order;

/**
 * One requested line. The real price is read from the product stock;
 * expectedUnitPrice only detects that the price changed since checkout.
 */
final class CreateOrderItemDTO
{
    public function __construct(
        public readonly string $productStockId,
        public readonly int $qty,
        public readonly string $expectedUnitPrice,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productStockId: $data['product_stock_id'],
            qty: (int) $data['qty'],
            expectedUnitPrice: (string) $data['expected_unit_price'],
        );
    }

    public function toArray(): array
    {
        return [
            'product_stock_id' => $this->productStockId,
            'qty' => $this->qty,
            'expected_unit_price' => $this->expectedUnitPrice,
        ];
    }
}
