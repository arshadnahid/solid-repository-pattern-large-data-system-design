<?php

namespace App\Support;

/**
 * Converts between decimal strings ("49.99") and integer cents (4999), so
 * order math never goes through a float. Amounts are never negative here.
 */
final class Money
{
    public static function toCents(string|int $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '0');

        return (int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    public static function format(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * $percent of $cents, rounded half up. "12.5" percent is 1250 basis points.
     */
    public static function percentOf(int $cents, string $percent): int
    {
        return intdiv($cents * self::toCents($percent) + 5000, 10000);
    }
}
