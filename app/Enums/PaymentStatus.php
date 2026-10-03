<?php

namespace App\Enums;

/**
 * Mirrors orders.payment_status.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case UNSUCCESSFUL = 'unsuccessful';
    case PAID = 'paid';
    case REFUND_INITIATED = 'refund_initiated';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
}
