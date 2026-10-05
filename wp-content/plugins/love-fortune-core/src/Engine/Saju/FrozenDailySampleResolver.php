<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

/** Frozen target and Seoul intervals; UTC DateTime is only a civil arithmetic carrier. */
final class FrozenDailySampleResolver
{
    public const TIMES = ['06:00','12:00','18:00','23:00'];
    public function __construct(private readonly TimezoneReferenceRepository $zones = new TimezoneReferenceRepository()) {}
    public function canonicalize(string $zone): string { return $this->zones->canonicalize($zone); }

    public function resolve(string $date, string $time, string $zone): int
    {
        $wall = NatalCivilTime::input($date, $time);
        $rows = $this->zones->intervals($this->canonicalize($zone));
        $hits = []; $gap = null;
        foreach ($rows as $i => $row) {
            $instant = $wall - $row['historicalOffsetSeconds'] * 1000000;
            if ($instant >= $row['startUs'] && $instant < $row['endUs']) { $hits[] = $instant; }
            if ($i > 0) {
                $before = $row['startUs'] + $rows[$i-1]['historicalOffsetSeconds'] * 1000000;
                $after = $row['startUs'] + $row['historicalOffsetSeconds'] * 1000000;
                if ($after > $before && $wall >= $before && $wall < $after) { $gap = $row['startUs']; }
            }
        }
        if ($hits !== []) { return min($hits); }
        if ($gap !== null) { return $gap; }
        throw new \OutOfRangeException('UNSUPPORTED_DATE');
    }

    public function day(int $instant): array
    {
        foreach ($this->zones->intervals('Asia/Seoul') as $row) {
            if ($instant < $row['startUs'] || $instant >= $row['endUs']) { continue; }
            $offset = $row['historicalOffsetSeconds'];
            $seoulCivil = $instant + $offset * 1000000;
            $adjusted = NatalCivilTime::datetime($seoulCivil + NatalCivilTime::adjustment(127500000, $offset) * 1000000);
            $date = (new CalculationDateResolver())->fromAdjusted($adjusted);
            return ['calculationDate'=>$date, 'pillar'=>(new DayPillarCalculator())->calculate($date)];
        }
        throw new \OutOfRangeException('UNSUPPORTED_DATE');
    }

    public function samples(string $date, string $zone): array
    {
        $out=[]; $canonical=$this->canonicalize($zone);
        foreach (self::TIMES as $time) {
            $instant=$this->resolve($date,$time,$canonical);
            $out[]=['sampleRef'=>['date'=>$date,'time'=>$time,'timezone'=>$canonical], 'instantUs'=>$instant] + $this->day($instant);
        }
        return $out;
    }
}
