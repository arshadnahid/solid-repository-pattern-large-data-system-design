<?php

namespace App\Services\Order;

use App\DTOs\Coupon\ActiveCouponDTO;
use App\DTOs\Order\CouponDTO;
use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\PricedFulfillmentDTO;
use App\DTOs\Order\PricedItemDTO;
use App\DTOs\Order\PricedOrderDTO;
use App\DTOs\Product\ProductStockDTO;
use App\Exceptions\Order\PriceChangedException;
use App\Repositories\Coupon\CouponInterfaces\CouponRepositoryInterface;
use App\Repositories\Product\ProductInterfaces\ProductStockRepositoryInterface;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

/**
 * Prices an order from the database. Client amounts are only compared
 * against the result, never used in it. Reads only; reserves nothing.
 */
class OrderPricingService
{
    /**
     * @param  string  $taxRate  Percent, as a decimal string (config/order.php).
     */
    public function __construct(
        private readonly ProductStockRepositoryInterface $stocks,
        private readonly CouponRepositoryInterface $coupons,
        private readonly string $taxRate,
    ) {
    }

    /**
     * @throws ValidationException when an item, shipping choice or coupon is not usable.
     * @throws PriceChangedException when the client's prices differ from the server's.
     */
    public function price(CreateOrderDTO $data): PricedOrderDTO
    {
        $groups = $this->groupItemsByFulfillment($data);
        $rules = $this->shippingRulesByGroup($data, array_keys($groups));
        $coupons = $this->couponsByGroup($data, $groups);

        $priced = new PricedOrderDTO(array_map(function (string $group) use ($groups, $rules, $coupons) {
            $items = $groups[$group];
            $stock = $items[0]->stock;
            $coupon = $coupons[$group] ?? null;

            return new PricedFulfillmentDTO(
                sourceType: $stock->storeId && $stock->storeType === 'INTERNAL' ? 'internal' : 'external',
                storeId: $stock->storeId,
                supplierId: $stock->storeId ? null : $stock->supplierId,
                shippingRuleId: $rules[$group],
                couponId: $coupon?->id,
                items: $items,
                discountCents: $coupon ? $this->couponDiscountCents($coupon, $items) : 0,
            );
        }, array_keys($groups)));

        $this->assertMatchesClient($data, $priced);

        return $priced;
    }

    /**
     * @return array<string, PricedItemDTO[]> keyed by "store:<id>" or "supplier:<id>"
     */
    private function groupItemsByFulfillment(CreateOrderDTO $data): array
    {
        $stocks = $this->stocks->findManyForOrder(array_map(fn ($item) => $item->productStockId, $data->items));
        $groups = [];
        $errors = [];

        foreach ($data->items as $index => $item) {
            $stock = $stocks[$item->productStockId] ?? null;

            if (! $stock || ! $stock->isAvailable) {
                $errors["items.{$index}.product_stock_id"] = ['This product is no longer available.'];
            } elseif ($stock->stockQty < $item->qty) {
                $errors["items.{$index}.qty"] = ["Only {$stock->stockQty} left in stock."];
            } elseif (! $group = $this->groupKey($stock->storeId, $stock->supplierId)) {
                $errors["items.{$index}.product_stock_id"] = ['This product has no seller.'];
            } else {
                $groups[$group][] = $this->priceItem($index, $stock, $item->qty);
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $groups;
    }

    private function priceItem(int $index, ProductStockDTO $stock, int $qty): PricedItemDTO
    {
        $unitPrice = $this->unitPriceCents($stock);

        // Stores' commission comes from seller profiles, which do not exist yet.
        $percentage = $stock->storeId ? '0' : ($stock->supplierTransactionPercentage ?? '0');

        return new PricedItemDTO(
            index: $index,
            stock: $stock,
            qty: $qty,
            unitPriceCents: $unitPrice,
            taxCents: Money::percentOf($unitPrice * $qty, $this->taxRate),
            shippingCents: $this->shippingCents($stock, $qty),
            transactionType: $stock->storeId ? null : $stock->supplierTransactionType,
            transactionPercentage: $percentage,
            transactionPerItemCents: Money::percentOf($unitPrice, $percentage),
        );
    }

    private function unitPriceCents(ProductStockDTO $stock): int
    {
        $price = Money::toCents($stock->salesPrice);
        $today = now()->toDateString();

        $discountActive = $stock->discount !== null
            && ($stock->discountStartDate === null || $stock->discountStartDate <= $today)
            && ($stock->discountEndDate === null || $stock->discountEndDate >= $today);

        if (! $discountActive) {
            return $price;
        }

        $discount = $stock->discountType === 'percent'
            ? Money::percentOf($price, $stock->discount)
            : Money::toCents($stock->discount);

        return max(0, $price - $discount);
    }

    private function shippingCents(ProductStockDTO $stock, int $qty): int
    {
        $cost = Money::toCents($stock->shippingCost ?? '0');

        return match ($stock->shippingCostType) {
            'fixed' => $cost,
            'quantityMultiply' => $cost * $qty,
            default => 0,
        };
    }

    /**
     * Every fulfillment needs exactly one shipping choice, and every choice a fulfillment.
     *
     * @param  string[]  $groups
     * @return array<string, string> group => shipping rule id
     */
    private function shippingRulesByGroup(CreateOrderDTO $data, array $groups): array
    {
        $rules = [];
        $errors = [];

        foreach ($data->shippingSelections as $index => $selection) {
            $group = $this->groupKey($selection->storeId, $selection->supplierId);

            if (! in_array($group, $groups, true)) {
                $errors["shipping_selections.{$index}"] = ['No item in this order ships from this seller.'];
            } elseif (isset($rules[$group])) {
                $errors["shipping_selections.{$index}"] = ['Only one shipping option per seller.'];
            } else {
                $rules[$group] = $selection->shippingRuleId;
            }
        }

        foreach (array_diff($groups, array_keys($rules)) as $group) {
            $errors['shipping_selections'][] = "Choose a shipping option for {$group}.";
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $rules;
    }

    /**
     * @param  array<string, PricedItemDTO[]>  $groups
     * @return array<string, ActiveCouponDTO> group => coupon
     */
    private function couponsByGroup(CreateOrderDTO $data, array $groups): array
    {
        $coupons = [];
        $errors = [];

        foreach ($data->coupons as $index => $requested) {
            /** @var CouponDTO $requested */
            $group = $this->groupKey($requested->storeId, $requested->supplierId);
            $coupon = isset($groups[$group])
                ? $this->coupons->findActive($requested->code, $requested->storeId, $requested->supplierId)
                : null;

            if (! isset($groups[$group])) {
                $errors["coupons.{$index}.code"] = ['No item in this order is from this seller.'];
            } elseif (isset($coupons[$group])) {
                $errors["coupons.{$index}.code"] = ['Only one coupon per seller.'];
            } elseif (! $coupon) {
                $errors["coupons.{$index}.code"] = ['This coupon is invalid or expired.'];
            } elseif ($coupon->minOrderAmount !== null && $this->subtotalCents($groups[$group]) < Money::toCents($coupon->minOrderAmount)) {
                $errors["coupons.{$index}.code"] = ["This coupon needs a minimum of {$coupon->minOrderAmount}."];
            } else {
                $coupons[$group] = $coupon;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $coupons;
    }

    /**
     * @param  PricedItemDTO[]  $items
     */
    private function couponDiscountCents(ActiveCouponDTO $coupon, array $items): int
    {
        $subtotal = $this->subtotalCents($items);

        $discount = $coupon->discountType === 'percent'
            ? Money::percentOf($subtotal, $coupon->discount)
            : Money::toCents($coupon->discount);

        if ($coupon->maxDiscountAmount !== null) {
            $discount = min($discount, Money::toCents($coupon->maxDiscountAmount));
        }

        return min($discount, $subtotal);
    }

    /**
     * @param  PricedItemDTO[]  $items
     */
    private function subtotalCents(array $items): int
    {
        return array_sum(array_map(fn (PricedItemDTO $item) => $item->lineTotalCents(), $items));
    }

    private function assertMatchesClient(CreateOrderDTO $data, PricedOrderDTO $priced): void
    {
        $errors = [];

        foreach ($priced->itemsInLockOrder() as $item) {
            $expected = $data->items[$item->index]->expectedUnitPrice;

            if (Money::toCents($expected) !== $item->unitPriceCents) {
                $errors["items.{$item->index}.expected_unit_price"] = ['Price is now '.Money::format($item->unitPriceCents).'.'];
            }
        }

        $server = $priced->totals()->toArray();

        foreach ($data->expectedTotals->toArray() as $field => $expected) {
            if (Money::toCents($expected) !== Money::toCents($server[$field])) {
                $errors["expected_totals.{$field}"] = ["The {$field} is now {$server[$field]}."];
            }
        }

        if ($errors) {
            ksort($errors);

            throw new PriceChangedException($errors, $priced->totals());
        }
    }

    private function groupKey(?string $storeId, ?string $supplierId): ?string
    {
        return match (true) {
            $storeId !== null => "store:{$storeId}",
            $supplierId !== null => "supplier:{$supplierId}",
            default => null,
        };
    }
}
