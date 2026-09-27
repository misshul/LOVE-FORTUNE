<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** UTC here is a neutral Gregorian arithmetic carrier, never a birth timezone lookup. */
final class NatalCivilTime
{
    public const DAY_US = 86400000000;

    public static function input(string $date, ?string $time): int
    {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $date, $m)
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            throw new InvalidArgumentException('INVALID_DATE');
        }
        if ($date < '1900-01-01' || $date > '2099-12-31') {
            throw new InvalidArgumentException('UNSUPPORTED_DATE');
        }
        if ($time !== null && !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $time)) {
            throw new InvalidArgumentException('INVALID_BIRTH_TIME');
        }
        $civil = new DateTimeImmutable($date . ' ' . ($time ?? '00:00') . ':00', new DateTimeZone('UTC'));
        return $civil->getTimestamp() * 1000000;
    }

    public static function floorDiv(int $n, int $d): int
    {
        return intdiv($n, $d) - ($n < 0 && $n % $d !== 0 ? 1 : 0);
    }

    public static function datetime(int $us): DateTimeImmutable
    {
        $s = self::floorDiv($us, 1000000);
        $micro = $us - $s * 1000000;
        return (new DateTimeImmutable('@' . $s))->modify('+' . $micro . ' microseconds');
    }

    public static function adjustment(int $longitudeMicrodegrees, int $offsetSeconds): int
    {
        if ($longitudeMicrodegrees < -180000000 || $longitudeMicrodegrees > 180000000) {
            throw new InvalidArgumentException('REFERENCE_INCOMPATIBLE');
        }
        $n = 4 * ($longitudeMicrodegrees - 127500000);
        $minutes = ($n < 0 ? -1 : 1) * intdiv(abs($n) + 500000, 1000000);
        return 32400 - $offsetSeconds + 60 * $minutes;
    }
}
