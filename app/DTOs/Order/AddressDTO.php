<?php

namespace App\DTOs\Order;

final class AddressDTO
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $phone,
        public readonly ?string $email,
        public readonly string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?int $cityId,
        public readonly ?int $stateId,
        public readonly ?int $countryId,
        public readonly string $city,
        public readonly ?string $state,
        public readonly string $zipCode,
        public readonly string $countryCode,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fullName: $data['full_name'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
            addressLine1: $data['address_line_1'],
            addressLine2: $data['address_line_2'] ?? null,
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            stateId: isset($data['state_id']) ? (int) $data['state_id'] : null,
            countryId: isset($data['country_id']) ? (int) $data['country_id'] : null,
            city: $data['city'],
            state: $data['state'] ?? null,
            zipCode: $data['zip_code'],
            countryCode: strtoupper($data['country_code']),
        );
    }

    public function toArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'email' => $this->email,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city_id' => $this->cityId,
            'state_id' => $this->stateId,
            'country_id' => $this->countryId,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
            'country_code' => $this->countryCode,
        ];
    }
}
