<?php

namespace App\Exceptions\Order;

use App\DTOs\Order\OrderTotalsDTO;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The server's prices differ from what the client showed the customer.
 * Rendered as 422, which EnsureIdempotency does not store, so the client
 * can show the new totals and resend with the same Idempotency-Key.
 */
class PriceChangedException extends RuntimeException
{
    /**
     * @param  array<string, string[]>  $errors
     */
    public function __construct(
        public readonly array $errors,
        public readonly OrderTotalsDTO $serverTotals,
    ) {
        parent::__construct('Prices changed since checkout. Review the new totals and confirm again.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'data' => ['totals' => $this->serverTotals->toArray()],
        ], 422);
    }
}
