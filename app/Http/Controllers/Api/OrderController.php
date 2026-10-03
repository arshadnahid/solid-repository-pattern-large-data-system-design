<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    /**
     * 201 paid, 202 needs 3DS (finish it with payment.client_secret), 402 declined.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $placed = $this->orderService->placeOrder($request->toDTO());
        $order = $placed->order;

        [$status, $message] = match ($order->paymentStatus) {
            PaymentStatus::PAID => [201, 'Order placed successfully.'],
            PaymentStatus::PENDING => [202, 'Confirm the payment to complete the order.'],
            default => [402, $placed->payment?->message ?? 'Payment failed.'],
        };

        return response()->json([
            'message' => $message,
            'data' => [
                ...$order->toArray(),
                'payment' => [
                    'status' => $placed->payment?->outcome->value,
                    'payment_intent_id' => $placed->payment?->transactionId,
                    'client_secret' => $order->paymentStatus === PaymentStatus::PENDING ? $placed->payment?->clientSecret : null,
                ],
            ],
        ], $status);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orderService->findForUser($id, auth()->id());

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json(['data' => $order->toArray()]);
    }
}
