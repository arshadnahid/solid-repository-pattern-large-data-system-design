<?php

namespace App\DTOs\Order;

use App\DTOs\Payment\PaymentResultDTO;

/**
 * Result of placing an order: the stored order plus the payment attempt,
 * which carries what the client needs to finish 3DS.
 */
final class PlacedOrderDTO
{
    public function __construct(
        public readonly OrderDTO $order,
        public readonly ?PaymentResultDTO $payment,
    ) {
    }
}
