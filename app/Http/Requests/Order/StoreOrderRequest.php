<?php

namespace App\Http\Requests\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\Http\Middleware\EnsureIdempotency;
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
        $money = ['required', 'decimal:0,2', 'min:0'];
        $billingRequired = 'required_unless:billing_address.same_as_shipping,true';

        return [
            'idempotency_key' => ['sometimes', 'uuid'],
            'currency' => ['required', 'string', 'size:3'],

            'shipping_address' => ['required', 'array'],
            ...$this->addressRules('shipping_address', 'required'),

            'billing_address' => ['required', 'array'],
            'billing_address.same_as_shipping' => ['required', 'boolean'],
            ...$this->addressRules('billing_address', $billingRequired),

            'payment' => ['required', 'array'],
            'payment.provider' => ['required', 'string', Rule::in(array_keys(config('payment.gateways')))],
            'payment.method' => ['required', 'string', Rule::in(['card'])],
            'payment.stripe_payment_method_id' => ['required_if:payment.provider,stripe', 'string', 'starts_with:pm_', 'max:255'],
            'payment.save_payment_method' => ['sometimes', 'boolean'],
            'payment.return_url' => ['nullable', 'url', 'max:2048'],

            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_stock_id' => ['required', 'uuid', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.expected_unit_price' => $money,

            'shipping_selections' => ['required', 'array', 'min:1'],
            'shipping_selections.*.store_id' => ['required_without:shipping_selections.*.supplier_id', 'prohibits:shipping_selections.*.supplier_id', 'nullable', 'uuid'],
            'shipping_selections.*.supplier_id' => ['required_without:shipping_selections.*.store_id', 'nullable', 'uuid'],
            'shipping_selections.*.shipping_rule_id' => ['required', 'uuid'],

            'coupons' => ['sometimes', 'array', 'max:20'],
            'coupons.*.code' => ['required', 'string', 'max:50'],
            'coupons.*.store_id' => ['required_without:coupons.*.supplier_id', 'prohibits:coupons.*.supplier_id', 'nullable', 'uuid'],
            'coupons.*.supplier_id' => ['required_without:coupons.*.store_id', 'nullable', 'uuid'],

            'expected_totals' => ['required', 'array'],
            'expected_totals.subtotal' => $money,
            'expected_totals.shipping' => $money,
            'expected_totals.tax' => $money,
            'expected_totals.discount' => $money,
            'expected_totals.total' => $money,

            'client_meta' => ['sometimes', 'array'],
            'client_meta.geo_location.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'client_meta.geo_location.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'client_meta.user_agent' => ['nullable', 'string', 'max:512'],
            'client_meta.platform' => ['nullable', 'string', Rule::in(['web', 'ios', 'android'])],

            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDTO(): CreateOrderDTO
    {
        return CreateOrderDTO::fromArray(
            $this->validated(),
            userId: auth()->id(),
            // The header is what EnsureIdempotency deduplicates on, so it is the one we persist.
            idempotencyKey: $this->header(EnsureIdempotency::HEADER),
            ipAddress: $this->ip(),
        );
    }

    private function addressRules(string $prefix, string $required): array
    {
        return [
            "{$prefix}.full_name" => [$required, 'string', 'max:255'],
            "{$prefix}.phone" => [$required, 'string', 'max:30'],
            "{$prefix}.email" => ['nullable', 'email', 'max:255'],
            "{$prefix}.address_line_1" => [$required, 'string', 'max:255'],
            "{$prefix}.address_line_2" => ['nullable', 'string', 'max:255'],
            "{$prefix}.city_id" => ['nullable', 'integer'],
            "{$prefix}.state_id" => ['nullable', 'integer'],
            "{$prefix}.country_id" => ['nullable', 'integer'],
            "{$prefix}.city" => [$required, 'string', 'max:100'],
            "{$prefix}.state" => ['nullable', 'string', 'max:100'],
            "{$prefix}.zip_code" => [$required, 'string', 'max:20'],
            "{$prefix}.country_code" => [$required, 'string', 'size:2'],
        ];
    }
}
