<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

/** FP-02: consumes an already resolved service coordinate, not adjusted civil time. */
final class YearPillarCalculator
{
    public const FORMULA_VERSION = 'SAJU_YEAR_PILLAR_V1';

    public function __construct(private readonly SolarTermReference $reference = new SolarTermReference()) {}

    public function calculate(string $resolvedServiceCoordinateUs): array
    {
        [$start, $end] = $this->reference->interval($resolvedServiceCoordinateUs);
        $index = (($start['year'] - 1984) % 60 + 60) % 60;
        $stem = DayPillarCalculator::STEMS[$index % 10];
        $branch = DayPillarCalculator::BRANCHES[$index % 12];
        return [
            'sajuYear' => $start['year'], 'cycleIndex' => $index,
            'stemIndex' => $index % 10, 'branchIndex' => $index % 12,
            'stem' => $stem, 'branch' => $branch, 'ganzhi' => $stem . $branch,
            'lichunEventId' => $start['eventId'], 'nextLichunEventId' => $end['eventId'],
            'formulaVersion' => self::FORMULA_VERSION,
        ] + $this->reference->provenance();
    }
}
