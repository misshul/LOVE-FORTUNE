<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

final class NatalCandidatePartitioner
{
    public function __construct(private readonly SolarTermReference $solar = new SolarTermReference()) {}

    /** Input is already split by timezone provenance; no Hour or Zhongqi boundaries. */
    public function partition(array $range, int $longitudeMicrodegrees): array
    {
        $start = $range['startUs'];
        $end = $range['endUs'];
        $cuts = [$start, $end];
        $cursor = $start;
        while ($cursor < $end) {
            [, $next] = $this->solar->interval((string) $cursor, true);
            $cursor = $next['boundaryUs'];
            if ($cursor < $end) {
                $cuts[] = $cursor;
            }
        }
        $offset = $range['historicalOffsetSeconds'];
        $shift = ($offset + NatalCivilTime::adjustment($longitudeMicrodegrees, $offset)) * 1000000;
        $day = NatalCivilTime::DAY_US;
        $boundary = NatalCivilTime::floorDiv($start + $shift, $day) * $day + 84600000000;
        if ($boundary <= $start + $shift) {
            $boundary += $day;
        }
        for (; $boundary < $end + $shift; $boundary += $day) {
            $cuts[] = $boundary - $shift;
        }
        $cuts = array_values(array_unique($cuts));
        sort($cuts, SORT_NUMERIC);
        $result = [];
        for ($i = 0; $i + 1 < count($cuts); ++$i) {
            $result[] = ['startUs' => $cuts[$i], 'endUs' => $cuts[$i + 1]] + $range;
        }
        return $result;
    }

    /** Maximal intervals, but never across an original timezone transition/lineage. */
    public function normalize(array $candidates): array
    {
        $result = [];
        foreach ($candidates as $c) {
            $i = count($result) - 1;
            $previous = $result[$i] ?? null;
            if ($previous !== null && $previous['serviceRange']['endUs'] === $c['serviceRange']['startUs']
                && $previous['provenance'] === $c['provenance']
                && $previous['historicalOffsetSeconds'] === $c['historicalOffsetSeconds']
                && $previous['yearPillar'] === $c['yearPillar'] && $previous['monthPillar'] === $c['monthPillar']
                && $previous['dayPillar'] === $c['dayPillar']) {
                $result[$i]['serviceRange']['endUs'] = $c['serviceRange']['endUs'];
                $result[$i]['adjustedCivilRange']['end'] = $c['adjustedCivilRange']['end'];
            } else {
                $result[] = $c;
            }
        }
        return $result;
    }
}
