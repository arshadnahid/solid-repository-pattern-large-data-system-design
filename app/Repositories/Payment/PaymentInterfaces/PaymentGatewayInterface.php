<?php

namespace App\Repositories\Payment\PaymentInterfaces;

use App\DTOs\Payment\PaymentRequestDTO;
use App\DTOs\Payment\PaymentResultDTO;

interface PaymentGatewayInterface
{
    public function charge(PaymentRequestDTO $payment): PaymentResultDTO;
}
