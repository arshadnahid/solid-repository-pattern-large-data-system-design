<?php

namespace App\Repositories\Payment\PaymentInterfaces;

use App\DTOs\Payment\PaymentResultDTO;

interface PaymentRepositoryInterface
{
    public function findByIdempotencyKey(string $key): ?PaymentResultDTO;

    public function save(string $key, PaymentResultDTO $result): void;
}
