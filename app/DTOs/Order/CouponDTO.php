<?php

namespace App\DTOs\Order;

/**
 * A coupon code scoped to one store or one supplier. Exactly one of
 * storeId or supplierId is set.
 */
final class CouponDTO
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $storeId,
        public readonly ?string $supplierId,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: strtoupper(trim($data['code'])),
            storeId: $data['store_id'] ?? null,
            supplierId: $data['supplier_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'store_id' => $this->storeId,
            'supplier_id' => $this->supplierId,
        ];
    }
}
