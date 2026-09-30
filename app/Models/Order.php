<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
}
use \Illuminate\Database\Eloquent\Concerns\HasUuids;

public $incrementing = false;

protected $keyType = 'string';

protected $fillable = [
    'order_code',
    'shipping_address',
    'total_amount',
    'total_shipping_cost',
    'total_tax',
    'total_discount',
    'total_commission',
    'total_margin',
    'user_type',
    'stripe_payment_method_id',
    'stripe_payment_charge_id',
    'geo_location',
    'ip_address',
    'shipping_rules'
];

protected $casts = [
    'order_date' => 'datetime',
    'shipping_address' => 'array',
    'is_3ds_verified' => 'boolean',
    'geo_location' => 'array',
    'shipping_rules' => 'array',
];
