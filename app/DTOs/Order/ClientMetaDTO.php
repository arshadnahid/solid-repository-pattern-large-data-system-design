<?php

namespace App\DTOs\Order;

/**
 * Request context kept for fraud checks. The IP comes from the request,
 * not the body, so the client cannot set it.
 */
final class ClientMetaDTO
{
    public function __construct(
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?string $userAgent,
        public readonly ?string $platform,
        public readonly ?string $ipAddress,
    ) {
    }

    public static function fromArray(array $data, ?string $ipAddress): self
    {
        return new self(
            latitude: isset($data['geo_location']['lat']) ? (float) $data['geo_location']['lat'] : null,
            longitude: isset($data['geo_location']['lng']) ? (float) $data['geo_location']['lng'] : null,
            userAgent: $data['user_agent'] ?? null,
            platform: $data['platform'] ?? null,
            ipAddress: $ipAddress,
        );
    }

    public function geoLocation(): ?array
    {
        return $this->latitude === null || $this->longitude === null
            ? null
            : ['lat' => $this->latitude, 'lng' => $this->longitude];
    }
}
