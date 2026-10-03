<?php

namespace App\Repositories\Order\OrderInterfaces;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderDTO;
use App\DTOs\Order\PricedOrderDTO;

interface OrderRepositoryInterface
{
    /**
     * Stores the order, its fulfillments and items. Amounts come from
     * $priced only; $data supplies addresses, payment token and metadata.
     */
    public function create(CreateOrderDTO $data, PricedOrderDTO $priced): OrderDTO;

    public function findById(string $id): ?OrderDTO;

    public function findByIdempotencyKey(int $userId, string $key): ?OrderDTO;

    public function attachPaymentIntent(string $id, string $paymentIntentId): void;

    /**
     * Each transition only applies to a pending order and returns whether it
     * did, so a retry or a late webhook can never apply it twice.
     */
    public function markPaid(string $id, ?string $paymentIntentId, ?string $chargeId): bool;

    public function markPaymentFailed(string $id, ?string $paymentIntentId): bool;
}
