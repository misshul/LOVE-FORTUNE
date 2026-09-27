<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

/** Stateless interval algebra. No DateTimeZone or OS timezone source. */
final class NatalLocalTimeResolver
{
    public function __construct(private readonly TimezoneReferenceRepository $timezones = new TimezoneReferenceRepository()) {}

    public function resolve(string $zone, int $nominalUs, bool $known): array
    {
        return $this->mappings($this->timezones->intervals($zone), $nominalUs, $known);
    }

    /** Internal pure algebra also permits synthetic transition fixtures in tests. */
    public function mappings(array $intervals, int $nominalUs, bool $known): array
    {
        $result = [];
        foreach ($intervals as $interval) {
            $off = $interval['historicalOffsetSeconds'] * 1000000;
            $a = $nominalUs - $off;
            if ($known) {
                if ($interval['startUs'] <= $a && $a < $interval['endUs'] && $a + $off === $nominalUs) {
                    $result[] = ['startUs' => $a, 'endUs' => $a] + $interval;
                }
            } else {
                $start = max($interval['startUs'], $a);
                $end = min($interval['endUs'], $a + NatalCivilTime::DAY_US);
                if ($start < $end) {
                    $result[] = ['startUs' => $start, 'endUs' => $end] + $interval;
                }
            }
        }
        usort($result, static fn (array $a, array $b): int => [$a['startUs'], $a['endUs'], $a['historicalOffsetSeconds'], $a['transitionIdentity']]
            <=> [$b['startUs'], $b['endUs'], $b['historicalOffsetSeconds'], $b['transitionIdentity']]);
        return $result;
    }
}
