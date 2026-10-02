<?php

namespace App\DTOs\Order;

/**
 * Input data for creating an order. Carries validated data from the
 * HTTP layer into the service layer without leaking the Request object.
 *
 * Who is ordering (userId) and from where (IP) come from the request
 * itself, never from the body.
 */
final class CreateOrderDTO
{
    /**
     * @param  CreateOrderItemDTO[]  $items
     * @param  ShippingSelectionDTO[]  $shippingSelections
     * @param  CouponDTO[]  $coupons
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $idempotencyKey,
        public readonly string $currency,
        public readonly AddressDTO $shippingAddress,
        public readonly AddressDTO $billingAddress,
        public readonly OrderPaymentDTO $payment,
        public readonly array $items,
        public readonly array $shippingSelections,
        public readonly array $coupons,
        public readonly OrderTotalsDTO $expectedTotals,
        public readonly ClientMetaDTO $clientMeta,
        public readonly ?string $customerNote,
    ) {
    }

    public static function fromArray(array $data, int $userId, string $idempotencyKey, ?string $ipAddress): self
    {
        $shippingAddress = AddressDTO::fromArray($data['shipping_address']);

        return new self(
            userId: $userId,
            idempotencyKey: $idempotencyKey,
            currency: strtoupper($data['currency']),
            shippingAddress: $shippingAddress,
            billingAddress: ($data['billing_address']['same_as_shipping'] ?? false)
                ? $shippingAddress
                : AddressDTO::fromArray($data['billing_address']),
            payment: OrderPaymentDTO::fromArray($data['payment']),
            items: array_map(fn (array $item) => CreateOrderItemDTO::fromArray($item), $data['items']),
            shippingSelections: array_map(
                fn (array $selection) => ShippingSelectionDTO::fromArray($selection),
                $data['shipping_selections'] ?? [],
            ),
            coupons: array_map(fn (array $coupon) => CouponDTO::fromArray($coupon), $data['coupons'] ?? []),
            expectedTotals: OrderTotalsDTO::fromArray($data['expected_totals']),
            clientMeta: ClientMetaDTO::fromArray($data['client_meta'] ?? [], $ipAddress),
            customerNote: $data['customer_note'] ?? null,
        );
    }
}
