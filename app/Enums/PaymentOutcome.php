<?php

namespace App\Enums;

/**
 * What a gateway reports after one charge attempt. Stripe maps as:
 * succeeded => SUCCEEDED, requires_action => REQUIRES_ACTION (3DS),
 * anything declined or canceled => FAILED.
 */
enum PaymentOutcome: string
{
    case SUCCEEDED = 'succeeded';
    case REQUIRES_ACTION = 'requires_action';
    case FAILED = 'failed';
}
