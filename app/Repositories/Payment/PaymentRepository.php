<?php

namespace App\Repositories\Payment;

use App\DTOs\Payment\PaymentResultDTO;
use App\Repositories\Payment\PaymentInterfaces\PaymentRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Records every payment result by its idempotency key, so a payment that
 * already happened is read back from here instead of hitting the gateway again.
 */
class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private readonly Cache $cache)
    {
    }

    public function findByIdempotencyKey(string $key): ?PaymentResultDTO
    {
        $data = $this->cache->get("payments:{$key}");

        return $data ? PaymentResultDTO::fromArray($data) : null;
    }

    public function save(string $key, PaymentResultDTO $result): void
    {
        $this->cache->forever("payments:{$key}", $result->toArray());
    }
}
