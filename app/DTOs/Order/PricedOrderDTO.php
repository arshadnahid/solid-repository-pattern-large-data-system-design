<?php

namespace App\DTOs\Order;

use App\Support\Money;

/**
 * An order priced from the database, ready to be stored. This is the only
 * source of amounts that get persisted and charged.
 */
final class PricedOrderDTO
{
    /**
     * @param  PricedFulfillmentDTO[]  $fulfillments
     */
    public function __construct(public readonly array $fulfillments)
    {
    }

    /**
     * @return PricedItemDTO[] sorted by stock id, so concurrent orders lock rows in the same order.
     */
    public function itemsInLockOrder(): array
    {
        $items = array_merge(...array_map(fn (PricedFulfillmentDTO $f) => $f->items, $this->fulfillments));

        usort($items, fn (PricedItemDTO $a, PricedItemDTO $b) => strcmp($a->stock->id, $b->stock->id));

        return $items;
    }

    /**
     * @return string[]
     */
    public function couponIds(): array
    {
        return array_values(array_filter(array_map(fn (PricedFulfillmentDTO $f) => $f->couponId, $this->fulfillments)));
    }

    public function commissionCents(): int
    {
        return $this->sum(fn (PricedFulfillmentDTO $f) => $f->commissionCents());
    }

    public function marginCents(): int
    {
        return $this->sum(fn (PricedFulfillmentDTO $f) => $f->marginCents());
    }

    public function totals(): OrderTotalsDTO
    {
        return new OrderTotalsDTO(
            subtotal: Money::format($this->sum(fn (PricedFulfillmentDTO $f) => $f->subtotalCents())),
            shipping: Money::format($this->sum(fn (PricedFulfillmentDTO $f) => $f->shippingCents())),
            tax: Money::format($this->sum(fn (PricedFulfillmentDTO $f) => $f->taxCents())),
            discount: Money::format($this->sum(fn (PricedFulfillmentDTO $f) => $f->discountCents)),
            total: Money::format($this->sum(fn (PricedFulfillmentDTO $f) => $f->totalCents())),
        );
    }

    private function sum(callable $amount): int
    {
        return array_sum(array_map($amount, $this->fulfillments));
    }
}
