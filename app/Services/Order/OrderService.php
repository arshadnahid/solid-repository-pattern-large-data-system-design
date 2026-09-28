<?php

namespace App\Services\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderDTO;
use App\DTOs\Payment\PaymentRequestDTO;
use App\Enums\OrderStatus;
use App\Repositories\Order\OrderInterfaces\OrderRepositoryInterface;
use App\Repositories\Payment\PaymentInterfaces\PaymentRepositoryInterface;
use App\Services\Payment\PaymentGatewayFactory;

/**
 * Business logic for placing an order. Knows nothing about HTTP,
 * storage or a specific payment provider, only their abstractions.
 */
class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayFactory $gateways,
    ) {
    }

    public function placeOrder(CreateOrderDTO $data): OrderDTO
    {
        return $this->pay($this->orders->create($data));
    }

    /**
     * Charges an order at most once. The payment key is derived from the
     * order id: a result already recorded under it is reused, and the same
     * key is sent to the provider so it never captures the charge twice.
     */
    public function pay(OrderDTO $order): OrderDTO
    {
        if ($order->status === OrderStatus::PAID) {
            return $order;
        }

        $key = "order-{$order->id}-payment";

        $result = $this->payments->findByIdempotencyKey($key);

        if (! $result) {
            $result = $this->gateways
                ->make($order->paymentMethod)
                ->charge(new PaymentRequestDTO(
                    orderId: $order->id,
                    amount: $order->totalAmount,
                    currency: $order->currency,
                    idempotencyKey: $key,
                ));

            $this->payments->save($key, $result);
        }

        return $this->orders->updatePaymentStatus(
            $order->id,
            $result->success ? OrderStatus::PAID : OrderStatus::FAILED,
            $result->transactionId,
        );
    }

    public function findForUser(string $orderId, int $userId): ?OrderDTO
    {
        $order = $this->orders->findById($orderId);

        return $order?->userId === $userId ? $order : null;
    }
}
