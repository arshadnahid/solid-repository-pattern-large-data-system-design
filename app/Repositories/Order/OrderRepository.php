<?php

namespace App\Repositories\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderDTO;
use App\DTOs\Order\OrderItemDTO;
use App\DTOs\Order\PricedOrderDTO;
use App\Enums\PaymentStatus;
use App\Repositories\Order\OrderInterfaces\OrderRepositoryInterface;
use App\Support\Money;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

/**
 * Order storage on the database. Uses the query builder with bulk inserts:
 * an order with many lines costs three INSERTs, not one per row.
 */
class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Rows per INSERT for order lines, well below MySQL's placeholder limit.
     */
    private const INSERT_CHUNK = 500;

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function create(CreateOrderDTO $data, PricedOrderDTO $priced): OrderDTO
    {
        // Ordered UUIDs keep primary key inserts sequential, which matters on large tables.
        $orderId = (string) Str::orderedUuid();
        $now = now();
        $totals = $priced->totals();

        $this->db->table('orders')->insert([
            'id' => $orderId,
            'order_code' => 'ORD-'.$now->format('ymd').'-'.strtoupper(Str::random(8)),
            'order_date' => $now,
            'currency' => $data->currency,
            'shipping_address' => json_encode($data->shippingAddress->toArray()),
            'billing_address' => json_encode($data->billingAddress->toArray()),
            'total_amount' => $totals->total,
            'total_shipping_cost' => $totals->shipping,
            'total_tax' => $totals->tax,
            'total_discount' => $totals->discount,
            'total_commission' => Money::format($priced->commissionCents()),
            'total_margin' => Money::format($priced->marginCents()),
            'user_id' => $data->userId,
            'user_type' => 'CUSTOMER',
            'payment_status' => PaymentStatus::PENDING->value,
            'payment_provider' => $data->payment->provider,
            'stripe_payment_method_id' => $data->payment->paymentMethodId,
            'geo_location' => json_encode($data->clientMeta->geoLocation()),
            'ip_address' => $data->clientMeta->ipAddress,
            'user_agent' => $data->clientMeta->userAgent,
            'platform' => $data->clientMeta->platform,
            'shipping_rules' => json_encode(array_map(fn ($fulfillment) => [
                'store_id' => $fulfillment->storeId,
                'supplier_id' => $fulfillment->supplierId,
                'shipping_rule_id' => $fulfillment->shippingRuleId,
                'shipping_cost' => Money::format($fulfillment->shippingCents()),
            ], $priced->fulfillments)),
            'customer_note' => $data->customerNote,
            'idempotency_key' => $data->idempotencyKey,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $fulfillments = [];
        $items = [];

        foreach ($priced->fulfillments as $fulfillment) {
            $fulfillmentId = (string) Str::orderedUuid();

            $fulfillments[] = [
                'id' => $fulfillmentId,
                'order_id' => $orderId,
                'source_type' => $fulfillment->sourceType,
                'store_id' => $fulfillment->storeId,
                'supplier_id' => $fulfillment->supplierId,
                'total_order_amount' => Money::format($fulfillment->totalCents()),
                'total_order_shipping_cost' => Money::format($fulfillment->shippingCents()),
                'total_discount' => Money::format($fulfillment->discountCents),
                'fulfillment_tax_amount' => Money::format($fulfillment->taxCents()),
                'total_fulfillment_commission' => Money::format($fulfillment->commissionCents()),
                'total_fulfillment_margin' => Money::format($fulfillment->marginCents()),
                'coupon_id' => $fulfillment->couponId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($fulfillment->items as $item) {
                $stock = $item->stock;

                $items[] = [
                    'id' => (string) Str::orderedUuid(),
                    'fulfillment_id' => $fulfillmentId,
                    'product_stock_id' => $stock->id,
                    'qty' => $item->qty,
                    'unit_price' => Money::format($item->unitPriceCents),
                    'fulfillment_item_tax_amount' => Money::format($item->taxCents),
                    'transaction_type' => $item->transactionType,
                    'transaction_percentage' => $item->transactionPercentage,
                    'transaction_amount_per_item' => Money::format($item->transactionPerItemCents),
                    'transaction_amount_in_total' => Money::format($item->transactionTotalCents()),
                    'product_name' => $stock->productName,
                    'sku' => $stock->sku,
                    'attribute_value' => $stock->attributeValue,
                    'thumbnail_image' => $stock->thumbnailImage,
                    'purchase_price' => $stock->purchasePrice,
                    'margin' => $stock->margin,
                    'sales_price' => $stock->salesPrice,
                    'discount' => $stock->discount,
                    'discount_type' => $stock->discountType,
                    'dropshipper_price' => $stock->dropshipperPrice,
                    'dropshipper_discount' => $stock->dropshipperDiscount,
                    'dropshipper_discount_type' => $stock->dropshipperDiscountType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->db->table('fulfillments')->insert($fulfillments);

        foreach (array_chunk($items, self::INSERT_CHUNK) as $chunk) {
            $this->db->table('fulfillment_items')->insert($chunk);
        }

        return $this->findById($orderId);
    }

    public function findById(string $id): ?OrderDTO
    {
        $order = $this->db->table('orders')
            ->select('orders.*')
            // Orders store no subtotal column; it follows from the other totals.
            ->selectRaw('total_amount - total_shipping_cost - total_tax + total_discount as subtotal')
            ->where('id', $id)
            ->first();

        if (! $order) {
            return null;
        }

        $items = $this->db->table('fulfillment_items')
            ->join('fulfillments', 'fulfillments.id', '=', 'fulfillment_items.fulfillment_id')
            ->where('fulfillments.order_id', $id)
            ->orderBy('fulfillment_items.id')
            ->get(['fulfillment_items.*'])
            ->map(fn (object $row) => OrderItemDTO::fromRow($row))
            ->all();

        $couponIds = $this->db->table('fulfillments')
            ->where('order_id', $id)
            ->whereNotNull('coupon_id')
            ->pluck('coupon_id')
            ->all();

        return OrderDTO::fromRow($order, $items, $couponIds);
    }

    public function findByIdempotencyKey(int $userId, string $key): ?OrderDTO
    {
        // Served by the unique (user_id, idempotency_key) index.
        $id = $this->db->table('orders')
            ->where('user_id', $userId)
            ->where('idempotency_key', $key)
            ->value('id');

        return $id ? $this->findById($id) : null;
    }

    public function attachPaymentIntent(string $id, string $paymentIntentId): void
    {
        $this->db->table('orders')
            ->where('id', $id)
            ->update(['stripe_payment_intent_id' => $paymentIntentId, 'updated_at' => now()]);
    }

    public function markPaid(string $id, ?string $paymentIntentId, ?string $chargeId): bool
    {
        return $this->transition($id, PaymentStatus::PAID, [
            'stripe_payment_intent_id' => $paymentIntentId,
            'stripe_payment_charge_id' => $chargeId,
        ]);
    }

    public function markPaymentFailed(string $id, ?string $paymentIntentId): bool
    {
        return $this->transition($id, PaymentStatus::UNSUCCESSFUL, [
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);
    }

    private function transition(string $id, PaymentStatus $to, array $attributes): bool
    {
        return $this->db->table('orders')
            ->where('id', $id)
            ->where('payment_status', PaymentStatus::PENDING->value)
            ->update([...$attributes, 'payment_status' => $to->value, 'updated_at' => now()]) === 1;
    }
}
