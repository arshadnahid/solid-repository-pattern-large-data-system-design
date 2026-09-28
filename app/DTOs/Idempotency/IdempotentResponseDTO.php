<?php

namespace App\DTOs\Idempotency;

/**
 * A stored response for an idempotency key, plus the fingerprint of the
 * request that produced it so a reused key with a different body is caught.
 */
final class IdempotentResponseDTO
{
    public function __construct(
        public readonly string $fingerprint,
        public readonly int $status,
        public readonly string $body,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self($data['fingerprint'], (int) $data['status'], $data['body']);
    }

    public function toArray(): array
    {
        return [
            'fingerprint' => $this->fingerprint,
            'status' => $this->status,
            'body' => $this->body,
        ];
    }
}
