<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use InvalidArgumentException;
use OutOfRangeException;

/** Gregorian date identity only: never an instant or a timestamp difference. */
final class GregorianDayNumber
{
    public const INTERNAL_MIN = '1899-12-30';
    public const INTERNAL_MAX = '2100-01-02';
    public const PUBLIC_MIN = '1900-01-01';
    public const PUBLIC_MAX = '2099-12-31';

    /** @return array{int, int, int} */
    private function parts(string $date): array
    {
        if (!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date)) {
            throw new InvalidArgumentException('INVALID_DATE');
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year)) {
            throw new InvalidArgumentException('INVALID_DATE');
        }
        return [$year, $month, $day];
    }

    public function assertPublicDate(string $date): void
    {
        $this->parts($date);
        if ($date < self::PUBLIC_MIN || $date > self::PUBLIC_MAX) {
            throw new OutOfRangeException('UNSUPPORTED_DATE');
        }
    }

    public function calculate(string $date): int
    {
        [$year, $month, $day] = $this->parts($date);
        if ($date < self::INTERNAL_MIN || $date > self::INTERNAL_MAX) {
            throw new OutOfRangeException('UNSUPPORTED_DATE');
        }
        // NAOJ Gregorian -> JDN. Every division operand is positive here;
        // intdiv therefore equals floor. Largest numerator is below 11 million.
        $k = intdiv(14 - $month, 12);
        return intdiv(($year + 4800 - $k) * 1461, 4)
            + intdiv((12 * $k + $month - 2) * 367, 12)
            - intdiv(intdiv($year + 4900 - $k, 100) * 3, 4)
            + $day - 32075;
    }
}
