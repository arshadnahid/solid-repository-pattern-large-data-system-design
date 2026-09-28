<?php

namespace App\Exceptions;

use InvalidArgumentException;

class UnsupportedPaymentMethodException extends InvalidArgumentException
{
    public static function for(string $method): self
    {
        return new self("Payment method [{$method}] is not supported.");
    }
}
