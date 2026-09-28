<?php

namespace App\Http\Requests\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderItemDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(array_keys(config('payment.gateways')))],
            'currency' => ['sometimes', 'string', 'size:3'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function toDTO(): CreateOrderDTO
    {
        return new CreateOrderDTO(
            userId: auth()->id(),
            paymentMethod: $this->validated('payment_method'),
            currency: strtoupper($this->validated('currency', config('payment.default_currency'))),
            items: array_map(fn (array $item) => OrderItemDTO::fromArray($item), $this->validated('items')),
        );
    }
}
