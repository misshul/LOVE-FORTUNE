<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

/** FP-03: Year and Month always use the identical coordinate and artifact. */
final class MonthPillarCalculator
{
    public const FORMULA_VERSION = 'SAJU_MONTH_PILLAR_V1';
    private readonly YearPillarCalculator $year;

    public function __construct(private readonly SolarTermReference $reference = new SolarTermReference())
    {
        $this->year = new YearPillarCalculator($reference);
    }

    public function calculate(string $resolvedServiceCoordinateUs): array
    {
        $year = $this->year->calculate($resolvedServiceCoordinateUs);
        [$start, $end] = $this->reference->interval($resolvedServiceCoordinateUs, true);
        $month = array_search($start['termId'], SolarTermReference::JIE, true);
        $tiger = (2 * ($year['stemIndex'] % 5) + 2) % 10;
        $stemIndex = ($tiger + $month) % 10;
        $branchIndex = ($month + 2) % 12;
        $stem = DayPillarCalculator::STEMS[$stemIndex];
        $branch = DayPillarCalculator::BRANCHES[$branchIndex];
        return [
            'sajuYear' => $year['sajuYear'], 'yearStemIndex' => $year['stemIndex'],
            'monthIndex' => $month, 'stemIndex' => $stemIndex, 'branchIndex' => $branchIndex,
            'stem' => $stem, 'branch' => $branch, 'ganzhi' => $stem . $branch,
            'startJieEventId' => $start['eventId'], 'endJieEventId' => $end['eventId'],
            'formulaVersion' => self::FORMULA_VERSION,
        ] + $this->reference->provenance();
    }
}
