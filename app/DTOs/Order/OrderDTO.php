<?php

namespace App\DTOs\Order;

use App\Enums\PaymentStatus;

/**
 * Output data of the order repository. Every repository implementation
 * returns this same shape, so callers never depend on the storage.
 */
final class OrderDTO
{
    /**
     * @param  OrderItemDTO[]  $items
     * @param  string[]  $couponIds  Coupons whose usage this order holds.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $orderCode,
        public readonly int $userId,
        public readonly PaymentStatus $paymentStatus,
        public readonly string $paymentProvider,
        public readonly string $currency,
        public readonly OrderTotalsDTO $totals,
        public readonly array $items,
        public readonly array $couponIds,
        public readonly ?string $paymentIntentId,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param  OrderItemDTO[]  $items
     * @param  string[]  $couponIds
     */
    public static function fromRow(object $row, array $items, array $couponIds): self
    {
        return new self(
            id: $row->id,
            orderCode: $row->order_code,
            userId: (int) $row->user_id,
            paymentStatus: PaymentStatus::from($row->payment_status),
            paymentProvider: $row->payment_provider,
            currency: $row->currency,
            totals: new OrderTotalsDTO(
                subtotal: (string) $row->subtotal,
                shipping: (string) $row->total_shipping_cost,
                tax: (string) $row->total_tax,
                discount: (string) $row->total_discount,
                total: (string) $row->total_amount,
            ),
            items: $items,
            couponIds: $couponIds,
            paymentIntentId: $row->stripe_payment_intent_id,
            createdAt: (string) $row->created_at,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->orderCode,
            'payment_status' => $this->paymentStatus->value,
            'payment_provider' => $this->paymentProvider,
            'currency' => $this->currency,
            'totals' => $this->totals->toArray(),
            'items' => array_map(fn (OrderItemDTO $item) => $item->toArray(), $this->items),
            'created_at' => $this->createdAt,
        ];
    }
}
