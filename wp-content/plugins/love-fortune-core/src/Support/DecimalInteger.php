<?php
declare(strict_types=1);

namespace LoveFortune\Core\Support;

/** Unsigned decimal-string arithmetic. No native integer can hold a whole operand. @internal */
final class DecimalInteger
{
    public static function normalize(string $value): string
    {
        return ltrim($value, '0') ?: '0';
    }

    public static function compare(string $a, string $b): int
    {
        return (strlen($a) <=> strlen($b)) ?: (strcmp($a, $b) <=> 0);
    }

    public static function add(string $a, string $b): string
    {
        $result = '';
        $carry = 0;
        for ($i = strlen($a) - 1, $j = strlen($b) - 1; $i >= 0 || $j >= 0 || $carry; --$i, --$j) {
            $digit = ($i >= 0 ? (int) $a[$i] : 0) + ($j >= 0 ? (int) $b[$j] : 0) + $carry;
            $result = ($digit % 10) . $result;
            $carry = intdiv($digit, 10);
        }
        return self::normalize($result);
    }

    /** Requires a >= b. */
    public static function subtract(string $a, string $b): string
    {
        $result = '';
        $borrow = 0;
        for ($i = strlen($a) - 1, $j = strlen($b) - 1; $i >= 0; --$i, --$j) {
            $digit = (int) $a[$i] - ($j >= 0 ? (int) $b[$j] : 0) - $borrow;
            $borrow = $digit < 0 ? 1 : 0;
            $result = ($digit + 10 * $borrow) . $result;
        }
        return self::normalize($result);
    }

    public static function multiply(string $a, string $b): string
    {
        $a = strrev($a);
        $b = strrev($b);
        $digits = array_fill(0, strlen($a) + strlen($b) + 1, 0);
        for ($i = 0; $i < strlen($a); ++$i) {
            for ($j = 0; $j < strlen($b); ++$j) {
                $digits[$i + $j] += (int) $a[$i] * (int) $b[$j];
            }
        }
        for ($i = 0; $i < count($digits) - 1; ++$i) {
            $digits[$i + 1] += intdiv($digits[$i], 10);
            $digits[$i] %= 10;
        }
        return self::normalize(implode('', array_reverse($digits)));
    }

    /** @return array{string, string} Quotient and remainder. */
    public static function divide(string $a, string $b): array
    {
        if ($b === '0') {
            throw new \DivisionByZeroError('Zero denominator.');
        }
        $quotient = '';
        $remainder = '0';
        for ($i = 0; $i < strlen($a); ++$i) {
            $remainder = self::normalize($remainder . $a[$i]);
            $digit = 0;
            while (self::compare($remainder, $b) >= 0) {
                $remainder = self::subtract($remainder, $b);
                ++$digit;
            }
            $quotient .= $digit;
        }
        return [self::normalize($quotient), $remainder];
    }

    public static function gcd(string $a, string $b): string
    {
        while ($b !== '0') {
            [, $remainder] = self::divide($a, $b);
            $a = $b;
            $b = $remainder;
        }
        return $a;
    }
}
