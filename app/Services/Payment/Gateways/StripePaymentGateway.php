<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\Payment\PaymentRequestDTO;
use App\DTOs\Payment\PaymentResultDTO;
use App\Enums\PaymentOutcome;
use App\Repositories\Payment\PaymentInterfaces\PaymentGatewayInterface;

class StripePaymentGateway implements PaymentGatewayInterface
{
    /**
     * Stripe test tokens that do not succeed, so the demo can show every outcome.
     */
    private const DECLINED = ['pm_card_chargeDeclined', 'pm_card_visa_chargeDeclined'];

    private const REQUIRES_3DS = ['pm_card_authenticationRequired', 'pm_card_threeDSecure2Required'];

    public function charge(PaymentRequestDTO $payment): PaymentResultDTO
    {
        // Demo only. Real code (composer require stripe/stripe-php):
        //
        // $intent = $stripe->paymentIntents->create([
        //     'amount' => $payment->amountMinor,
        //     'currency' => strtolower($payment->currency),
        //     'payment_method' => $payment->paymentMethodId,
        //     'confirm' => true,
        //     'return_url' => $payment->returnUrl,
        //     'metadata' => ['order_id' => $payment->orderId],
        // ], ['idempotency_key' => $payment->idempotencyKey]);
        //
        // A CardException means declined => FAILED. Otherwise map $intent->status:
        // 'succeeded' => SUCCEEDED (charge id: $intent->latest_charge),
        // 'requires_action' => REQUIRES_ACTION (send $intent->client_secret to the client).
        // The payment_intent.succeeded webhook is what finally confirms a 3DS payment.
        //
        // The ids below are derived from the key to mimic that: same key, same intent.

        $intentId = 'pi_'.substr(hash('sha256', $payment->idempotencyKey), 0, 24);

        if (in_array($payment->paymentMethodId, self::DECLINED, true)) {
            return new PaymentResultDTO(
                outcome: PaymentOutcome::FAILED,
                gateway: 'stripe',
                transactionId: $intentId,
                message: 'Your card was declined.',
            );
        }

        if (in_array($payment->paymentMethodId, self::REQUIRES_3DS, true)) {
            return new PaymentResultDTO(
                outcome: PaymentOutcome::REQUIRES_ACTION,
                gateway: 'stripe',
                transactionId: $intentId,
                clientSecret: $intentId.'_secret_demo',
                message: 'The card requires 3D Secure authentication.',
            );
        }

        return new PaymentResultDTO(
            outcome: PaymentOutcome::SUCCEEDED,
            gateway: 'stripe',
            transactionId: $intentId,
            chargeId: 'ch_'.substr(hash('sha256', $payment->idempotencyKey.'charge'), 0, 24),
            message: 'Payment captured by Stripe (demo).',
        );
    }
}
