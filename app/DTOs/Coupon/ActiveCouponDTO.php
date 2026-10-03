<?php

namespace App\DTOs\Coupon;

/**
 * A coupon that exists, is active, is inside its date window and has uses left.
 */
final class ActiveCouponDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $discountType,
        public readonly string $discount,
        public readonly ?string $minOrderAmount,
        public readonly ?string $maxDiscountAmount,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            id: $row->id,
            code: $row->code,
            discountType: $row->discount_type,
            discount: (string) $row->discount,
            minOrderAmount: $row->min_order_amount,
            maxDiscountAmount: $row->max_discount_amount,
        );
    }
}
