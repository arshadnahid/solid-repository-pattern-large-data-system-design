<?php

namespace App\Http\Middleware;

use App\DTOs\Idempotency\IdempotentResponseDTO;
use App\Repositories\Order\OrderInterfaces\IdempotencyKeyRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes a POST endpoint safe to retry. The first request with a given
 * Idempotency-Key runs normally and its response is stored; any retry
 * with the same key and body gets that stored response back unchanged.
 */
class EnsureIdempotency
{
    public const HEADER = 'Idempotency-Key';

    public function __construct(private readonly IdempotencyKeyRepositoryInterface $keys)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $clientKey = $request->header(self::HEADER);

        if (! $clientKey || strlen($clientKey) > 255) {
            return response()->json(['message' => 'A valid '.self::HEADER.' header (max 255 chars) is required.'], 400);
        }

        // Scope keys per user and endpoint so two clients can never collide.
        $key = hash('sha256', $request->user()?->getAuthIdentifier().'|'.$request->method().'|'.$request->path().'|'.$clientKey);
        $fingerprint = hash('sha256', $request->getContent());

        if ($stored = $this->keys->find($key)) {
            return $this->replay($stored, $fingerprint);
        }

        $lock = $this->keys->acquireLock($key);

        if (! $lock) {
            return response()->json(['message' => 'A request with this '.self::HEADER.' is already being processed.'], 409);
        }

        try {
            // Re-check: the request holding the lock may have finished just before we got it.
            if ($stored = $this->keys->find($key)) {
                return $this->replay($stored, $fingerprint);
            }

            $response = $next($request);

            if ($this->shouldStore($response)) {
                $this->keys->save($key, new IdempotentResponseDTO($fingerprint, $response->getStatusCode(), $response->getContent()));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function replay(IdempotentResponseDTO $stored, string $fingerprint): Response
    {
        if (! hash_equals($stored->fingerprint, $fingerprint)) {
            return response()->json(['message' => 'This '.self::HEADER.' was already used with a different request body.'], 422);
        }

        return response($stored->body, $stored->status, [
            'Content-Type' => 'application/json',
            'Idempotent-Replayed' => 'true',
        ]);
    }

    /**
     * Validation errors and server errors are not stored, so the client can
     * fix the request or retry with the same key.
     */
    private function shouldStore(Response $response): bool
    {
        $status = $response->getStatusCode();

        return $status < 500 && $status !== 422;
    }
}
