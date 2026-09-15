<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Consumes a resolved local instant, preserving the caller's natal reference zone. */
final class CalculationDateResolver
{
    public function fromResolvedLocal(DateTimeImmutable $local, float $longitude): string
    {
        if (!is_finite($longitude) || $longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('INVALID_LONGITUDE');
        }
        // Neutral wall-clock arithmetic: the actual historical offset is applied
        // once, including sub-minute historical offsets. No second DST transition.
        $wall = new DateTimeImmutable($local->format('Y-m-d H:i:s.u'), new DateTimeZone('UTC'));
        $seconds = 32400 - $local->getOffset()
            + (int) round(($longitude - 127.5) * 4, 0, PHP_ROUND_HALF_UP) * 60;
        $adjusted = $wall->modify(sprintf('%+d seconds', $seconds));
        return $this->fromAdjusted($adjusted);
    }

    /** Reads already-adjusted civil fields; performs no timezone correction. */
    public function fromAdjusted(DateTimeImmutable $adjusted): string
    {
        $wall = new DateTimeImmutable($adjusted->format('Y-m-d H:i:s.u'), new DateTimeZone('UTC'));
        if ($wall->format('H:i') >= '23:30') {
            $wall = $wall->modify('+1 day');
        }
        return $wall->format('Y-m-d');
    }
}
