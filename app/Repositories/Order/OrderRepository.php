<?php

namespace App\Repositories\Order;

use App\DTOs\Idempotency\IdempotentResponseDTO;
use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderDTO;
use App\DTOs\Order\OrderItemDTO;
use App\Enums\OrderStatus;
use App\Repositories\Order\OrderInterfaces\IdempotencyKeyRepositoryInterface;
use App\Repositories\Order\OrderInterfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Order storage backed by the cache, so the demo needs no database table.
 * Implements two small interfaces: callers depend only on the one they use.
 */
class OrderRepository implements OrderRepositoryInterface, IdempotencyKeyRepositoryInterface
{
    /**
     * How long an idempotency key is remembered. Retries after this create a new order.
     */
    private const IDEMPOTENCY_TTL_SECONDS = 60 * 60 * 24;

    /**
     * Upper bound for one request; the lock frees itself if the process dies.
     */
    private const LOCK_SECONDS = 30;

    public function __construct(private readonly Cache $cache)
    {
    }

    public function create(CreateOrderDTO $data): OrderDTO
    {
        $order = [
            'id' => (string) Str::uuid(),
            'user_id' => $data->userId,
            'status' => OrderStatus::PENDING->value,
            'payment_method' => $data->paymentMethod,
            'currency' => $data->currency,
            'total_amount' => $data->totalAmount(),
            'items' => array_map(fn (OrderItemDTO $item) => $item->toArray(), $data->items),
            'transaction_id' => null,
            'created_at' => now()->toIso8601String(),
        ];

        $this->cache->forever($this->orderKey($order['id']), $order);

        return OrderDTO::fromArray($order);
    }

    public function findById(string $id): ?OrderDTO
    {
        $order = $this->cache->get($this->orderKey($id));

        return $order ? OrderDTO::fromArray($order) : null;
    }

    public function updatePaymentStatus(string $id, OrderStatus $status, ?string $transactionId = null): OrderDTO
    {
        $order = $this->cache->get($this->orderKey($id)) ?? throw new RuntimeException("Order [{$id}] not found.");

        $order['status'] = $status->value;
        $order['transaction_id'] = $transactionId;

        $this->cache->forever($this->orderKey($id), $order);

        return OrderDTO::fromArray($order);
    }

    public function find(string $key): ?IdempotentResponseDTO
    {
        $data = $this->cache->get("idempotency:{$key}");

        return $data ? IdempotentResponseDTO::fromArray($data) : null;
    }

    public function save(string $key, IdempotentResponseDTO $response): void
    {
        $this->cache->put("idempotency:{$key}", $response->toArray(), self::IDEMPOTENCY_TTL_SECONDS);
    }

    public function acquireLock(string $key): ?Lock
    {
        $lock = $this->cache->getStore()->lock("idempotency:lock:{$key}", self::LOCK_SECONDS);

        return $lock->get() ? $lock : null;
    }

    private function orderKey(string $id): string
    {
        return "orders:{$id}";
    }
}
