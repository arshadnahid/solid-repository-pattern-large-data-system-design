<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\Payment\PaymentRequestDTO;
use App\DTOs\Payment\PaymentResultDTO;
use App\Repositories\Payment\PaymentInterfaces\PaymentGatewayInterface;
use Illuminate\Support\Str;

class RazorpayPaymentGateway implements PaymentGatewayInterface
{
    public function charge(PaymentRequestDTO $payment): PaymentResultDTO
    {
        // Demo only. Real code: create an order with the razorpay/razorpay SDK using
        // 'receipt' => $payment->idempotencyKey, look up an existing order by that
        // receipt before creating a new one, then verify the checkout signature.
        // The transaction id below is derived from the key: same key, same charge.

        return new PaymentResultDTO(
            success: true,
            gateway: 'razorpay',
            transactionId: 'pay_'.substr(hash('sha256', $payment->idempotencyKey), 0, 14),
            message: 'Payment captured by Razorpay (demo).',
        );
    }
}
