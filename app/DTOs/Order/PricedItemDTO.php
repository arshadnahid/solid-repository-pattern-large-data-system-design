<?php

namespace App\DTOs\Order;

use App\DTOs\Product\ProductStockDTO;

/**
 * One order line priced by the server. All amounts are integer cents.
 */
final class PricedItemDTO
{
    /**
     * @param  int  $index  Position in the request's items array, for error messages.
     */
    public function __construct(
        public readonly int $index,
        public readonly ProductStockDTO $stock,
        public readonly int $qty,
        public readonly int $unitPriceCents,
        public readonly int $taxCents,
        public readonly int $shippingCents,
        public readonly ?string $transactionType,
        public readonly string $transactionPercentage,
        public readonly int $transactionPerItemCents,
    ) {
    }

    public function lineTotalCents(): int
    {
        return $this->unitPriceCents * $this->qty;
    }

    public function transactionTotalCents(): int
    {
        return $this->transactionPerItemCents * $this->qty;
    }
}
