<?php

namespace App\DTOs\Order;

/**
 * Input data for creating an order. Carries validated data from the
 * HTTP layer into the service layer without leaking the Request object.
 */
final class CreateOrderDTO
{
    /**
     * @param  OrderItemDTO[]  $items
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $paymentMethod,
        public readonly string $currency,
        public readonly array $items,
    ) {
    }

    public function totalAmount(): float
    {
        return round(array_sum(array_map(fn (OrderItemDTO $item) => $item->subtotal(), $this->items)), 2);
    }
}
