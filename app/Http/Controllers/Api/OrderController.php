<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeOrder($request->toDTO());

        $paid = $order->status === OrderStatus::PAID;

        return response()->json([
            'message' => $paid ? 'Order placed successfully.' : 'Order created but payment failed.',
            'data' => $order->toArray(),
        ], $paid ? 201 : 402);
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
