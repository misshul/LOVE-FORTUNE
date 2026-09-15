<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

/** Daily only: folds choose earlier UTC, gaps choose the first valid instant. */
final class DailySampleResolver
{
    public function resolve(string $date, string $time, DateTimeZone $zone): DateTimeImmutable
    {
        (new GregorianDayNumber())->assertPublicDate($date);
        if (!preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/', $time)) {
            throw new InvalidArgumentException('INVALID_TIME');
        }
        $wall = new DateTimeImmutable($date . ' ' . $time . ':00', new DateTimeZone('UTC'));
        // Seconds are used only to resolve timezone transitions, never for JDN.
        $nominal = $wall->getTimestamp();
        $transitions = $zone->getTransitions($nominal - 259200, $nominal + 259200);
        if ($transitions === false) {
            throw new InvalidArgumentException('IANA_TIMEZONE_REQUIRED');
        }
        $candidates = [];
        foreach (array_unique(array_column($transitions, 'offset')) as $offset) {
            $candidate = $wall->setTimestamp($nominal - $offset)->setTimezone($zone);
            if ($candidate->format('Y-m-d H:i:s') === $date . ' ' . $time . ':00') {
                $candidates[$candidate->getTimestamp()] = $candidate;
            }
        }
        if ($candidates !== []) {
            ksort($candidates, SORT_NUMERIC);
            return reset($candidates);
        }
        for ($i = 1; $i < count($transitions); ++$i) {
            $transition = $transitions[$i];
            $before = $transition['ts'] + $transitions[$i - 1]['offset'];
            $after = $transition['ts'] + $transition['offset'];
            if ($after > $before && $nominal >= $before && $nominal < $after) {
                return $wall->setTimestamp($transition['ts'])->setTimezone($zone);
            }
        }
        throw new RuntimeException('LOCAL_TIME_UNRESOLVED');
    }
}
