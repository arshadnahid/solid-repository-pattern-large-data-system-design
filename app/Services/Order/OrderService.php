<?php

namespace App\Services\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderDTO;
use App\DTOs\Order\OrderPaymentDTO;
use App\DTOs\Order\PlacedOrderDTO;
use App\DTOs\Order\PricedOrderDTO;
use App\DTOs\Payment\PaymentRequestDTO;
use App\DTOs\Payment\PaymentResultDTO;
use App\Enums\PaymentOutcome;
use App\Enums\PaymentStatus;
use App\Repositories\Coupon\CouponInterfaces\CouponRepositoryInterface;
use App\Repositories\Order\OrderInterfaces\OrderRepositoryInterface;
use App\Repositories\Payment\PaymentInterfaces\PaymentRepositoryInterface;
use App\Repositories\Product\ProductInterfaces\ProductStockRepositoryInterface;
use App\Services\Payment\PaymentGatewayFactory;
use App\Support\Money;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Places an order: price it on the server, reserve stock and coupons and
 * store it in one transaction, then charge outside that transaction so no
 * row stays locked while the payment provider answers.
 */
class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ProductStockRepositoryInterface $stocks,
        private readonly CouponRepositoryInterface $coupons,
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayFactory $gateways,
        private readonly OrderPricingService $pricing,
        private readonly ConnectionInterface $db,
    ) {}

    public function placeOrder(CreateOrderDTO $data): PlacedOrderDTO
    {
        // A retry that outlived the idempotency cache resumes the first order instead of creating another.
        if ($existing = $this->orders->findByIdempotencyKey($data->userId, $data->idempotencyKey)) {
            return $this->pay($existing, $data->payment);
        }

        $priced = $this->pricing->price($data);

        try {
            $order = $this->db->transaction(fn() => $this->reserveAndCreate($data, $priced));
        } catch (UniqueConstraintViolationException $e) {
            // Another request with this key won the race; everything here was rolled back.
            $order = $this->orders->findByIdempotencyKey($data->userId, $data->idempotencyKey) ?? throw $e;
        }

        return $this->pay($order, $data->payment);
    }

    /**
     * Charges an order at most once. The payment key is derived from the
     * order id: a result already recorded under it is reused, and the same
     * key is sent to the provider so it never captures the charge twice.
     */
    public function pay(OrderDTO $order, OrderPaymentDTO $payment): PlacedOrderDTO
    {
        $key = "order-{$order->id}-payment";
        $result = $this->payments->findByIdempotencyKey($key);

        if ($order->paymentStatus !== PaymentStatus::PENDING) {
            return new PlacedOrderDTO($order, $result);
        }

        if (! $result) {
            $result = $this->gateways
                ->make($order->paymentProvider)
                ->charge(new PaymentRequestDTO(
                    orderId: $order->id,
                    amountMinor: Money::toCents($order->totals->total),
                    currency: $order->currency,
                    idempotencyKey: $key,
                    paymentMethodId: $payment->paymentMethodId,
                    returnUrl: $payment->returnUrl,
                ));

            $this->payments->save($key, $result);
        }

        match ($result->outcome) {
            PaymentOutcome::SUCCEEDED => $this->orders->markPaid($order->id, $result->transactionId, $result->chargeId),
            // Stays pending until the client finishes 3DS and the webhook marks it paid.
            PaymentOutcome::REQUIRES_ACTION => $this->orders->attachPaymentIntent($order->id, $result->transactionId),
            PaymentOutcome::FAILED => $this->failPayment($order, $result),
        };

        return new PlacedOrderDTO($this->orders->findById($order->id), $result);
    }

    public function findForUser(string $orderId, int $userId): ?OrderDTO
    {
        $order = $this->orders->findById($orderId);

        return $order?->userId === $userId ? $order : null;
    }

    private function reserveAndCreate(CreateOrderDTO $data, PricedOrderDTO $priced): OrderDTO
    {
        foreach ($priced->itemsInLockOrder() as $item) {
            if (! $this->stocks->reserve($item->stock->id, $item->qty)) {
                throw ValidationException::withMessages([
                    "items.{$item->index}.qty" => ['This product just sold out.'],
                ]);
            }
        }

        foreach ($priced->couponIds() as $couponId) {
            if (! $this->coupons->reserveUsage($couponId)) {
                throw ValidationException::withMessages(['coupons' => ['A coupon just reached its usage limit.']]);
            }
        }

        return $this->orders->create($data, $priced);
    }

    /**
     * Gives the reserved stock and coupon uses back, once: only the request
     * that moves the order out of pending releases them.
     */
    private function failPayment(OrderDTO $order, PaymentResultDTO $result): void
    {
        $this->db->transaction(function () use ($order, $result) {
            if (! $this->orders->markPaymentFailed($order->id, $result->transactionId)) {
                return;
            }

            foreach ($order->items as $item) {
                $this->stocks->release($item->productStockId, $item->qty);
            }

            foreach ($order->couponIds as $couponId) {
                $this->coupons->releaseUsage($couponId);
            }
        });
    }
}
