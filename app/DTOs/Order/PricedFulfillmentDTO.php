<?php

namespace App\DTOs\Order;

/**
 * The part of an order one store or one supplier ships. Amounts are cents.
 */
final class PricedFulfillmentDTO
{
    /**
     * @param  'internal'|'external'  $sourceType
     * @param  PricedItemDTO[]  $items
     */
    public function __construct(
        public readonly string $sourceType,
        public readonly ?string $storeId,
        public readonly ?string $supplierId,
        public readonly string $shippingRuleId,
        public readonly ?string $couponId,
        public readonly array $items,
        public readonly int $discountCents,
    ) {
    }

    public function subtotalCents(): int
    {
        return array_sum(array_map(fn (PricedItemDTO $item) => $item->lineTotalCents(), $this->items));
    }

    public function shippingCents(): int
    {
        return array_sum(array_map(fn (PricedItemDTO $item) => $item->shippingCents, $this->items));
    }

    public function taxCents(): int
    {
        return array_sum(array_map(fn (PricedItemDTO $item) => $item->taxCents, $this->items));
    }

    public function commissionCents(): int
    {
        return $this->transactionCents('COMMISSION');
    }

    public function marginCents(): int
    {
        return $this->transactionCents('MARGIN');
    }

    /**
     * What the customer pays for this fulfillment.
     */
    public function totalCents(): int
    {
        return $this->subtotalCents() + $this->shippingCents() + $this->taxCents() - $this->discountCents;
    }

    private function transactionCents(string $type): int
    {
        return array_sum(array_map(
            fn (PricedItemDTO $item) => $item->transactionType === $type ? $item->transactionTotalCents() : 0,
            $this->items,
        ));
    }
}
