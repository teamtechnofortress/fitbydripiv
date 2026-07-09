<?php

namespace App\Support\Money;

class DecimalMoney
{
    public static function normalize(mixed $value): string
    {
        return self::formatCents(self::toCents($value));
    }

    public static function add(mixed $left, mixed $right): string
    {
        return self::formatCents(self::toCents($left) + self::toCents($right));
    }

    public static function subtract(mixed $left, mixed $right): string
    {
        return self::formatCents(self::toCents($left) - self::toCents($right));
    }

    private static function toCents(mixed $value): int
    {
        $value = trim((string) ($value ?? '0'));
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $whole = preg_replace('/\D/', '', $whole) ?: '0';
        $fraction = str_pad(substr(preg_replace('/\D/', '', $fraction) ?: '0', 0, 2), 2, '0');

        $cents = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$cents : $cents;
    }

    private static function formatCents(int $cents): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);

        return ($negative ? '-' : '').intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
