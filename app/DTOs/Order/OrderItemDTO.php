<?php

namespace App\DTOs\Order;

/**
 * One stored order line, as it was priced when the order was placed.
 */
final class OrderItemDTO
{
    public function __construct(
        public readonly string $productStockId,
        public readonly ?string $productName,
        public readonly ?string $sku,
        public readonly int $qty,
        public readonly string $unitPrice,
        public readonly string $taxAmount,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            productStockId: $row->product_stock_id,
            productName: $row->product_name,
            sku: $row->sku,
            qty: (int) $row->qty,
            unitPrice: (string) $row->unit_price,
            taxAmount: (string) $row->fulfillment_item_tax_amount,
        );
    }

    public function toArray(): array
    {
        return [
            'product_stock_id' => $this->productStockId,
            'product_name' => $this->productName,
            'sku' => $this->sku,
            'qty' => $this->qty,
            'unit_price' => $this->unitPrice,
            'tax_amount' => $this->taxAmount,
        ];
    }
}
