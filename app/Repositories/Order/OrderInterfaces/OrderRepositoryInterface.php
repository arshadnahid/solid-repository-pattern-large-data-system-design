<?php

namespace App\Repositories\Order\OrderInterfaces;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderDTO;
use App\Enums\OrderStatus;

interface OrderRepositoryInterface
{
    public function create(CreateOrderDTO $data): OrderDTO;

    public function findById(string $id): ?OrderDTO;

    public function updatePaymentStatus(string $id, OrderStatus $status, ?string $transactionId = null): OrderDTO;
}
