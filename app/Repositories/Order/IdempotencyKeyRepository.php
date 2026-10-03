<?php

namespace App\Repositories\Order;

use App\DTOs\Idempotency\IdempotentResponseDTO;
use App\Repositories\Order\OrderInterfaces\IdempotencyKeyRepositoryInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Stored responses and in-flight locks for idempotency keys. Lives in the
 * cache because it needs atomic locks and expires by itself.
 */
class IdempotencyKeyRepository implements IdempotencyKeyRepositoryInterface
{
    /**
     * How long a response is replayed. After this the orders table's unique
     * (user_id, idempotency_key) still stops a duplicate order.
     */
    private const TTL_SECONDS = 60 * 60 * 24;

    /**
     * Upper bound for one request; the lock frees itself if the process dies.
     */
    private const LOCK_SECONDS = 30;

    public function __construct(private readonly Cache $cache)
    {
    }

    public function find(string $key): ?IdempotentResponseDTO
    {
        $data = $this->cache->get("idempotency:{$key}");

        return $data ? IdempotentResponseDTO::fromArray($data) : null;
    }

    public function save(string $key, IdempotentResponseDTO $response): void
    {
        $this->cache->put("idempotency:{$key}", $response->toArray(), self::TTL_SECONDS);
    }

    public function acquireLock(string $key): ?Lock
    {
        $lock = $this->cache->getStore()->lock("idempotency:lock:{$key}", self::LOCK_SECONDS);

        return $lock->get() ? $lock : null;
    }
}
