<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tax Rate
    |--------------------------------------------------------------------------
    |
    | Percentage applied to every order line, as a decimal string ("8.25").
    | Kept as a string so it never passes through a float.
    |
    */

    'tax_rate' => env('ORDER_TAX_RATE', '0'),

];
