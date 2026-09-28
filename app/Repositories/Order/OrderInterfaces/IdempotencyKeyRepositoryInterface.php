<?php

namespace App\Repositories\Order\OrderInterfaces;

use App\DTOs\Idempotency\IdempotentResponseDTO;
use Illuminate\Contracts\Cache\Lock;

interface IdempotencyKeyRepositoryInterface
{
    public function find(string $key): ?IdempotentResponseDTO;

    public function save(string $key, IdempotentResponseDTO $response): void;

    /**
     * Returns the acquired lock, or null when another request holds the key.
     */
    public function acquireLock(string $key): ?Lock;
}
