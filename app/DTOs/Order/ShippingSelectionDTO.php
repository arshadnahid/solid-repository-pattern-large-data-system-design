<?php

namespace App\DTOs\Order;

/**
 * The shipping rule chosen for one fulfillment. Exactly one of
 * storeId or supplierId is set.
 */
final class ShippingSelectionDTO
{
    public function __construct(
        public readonly ?string $storeId,
        public readonly ?string $supplierId,
        public readonly string $shippingRuleId,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            storeId: $data['store_id'] ?? null,
            supplierId: $data['supplier_id'] ?? null,
            shippingRuleId: $data['shipping_rule_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'store_id' => $this->storeId,
            'supplier_id' => $this->supplierId,
            'shipping_rule_id' => $this->shippingRuleId,
        ];
    }
}
