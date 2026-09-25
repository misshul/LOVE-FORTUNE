<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use DateTimeImmutable;
use InvalidArgumentException;

/** FP-04: reads already-adjusted civil fields; never resolves or corrects time. */
final class HourPillarCalculator
{
    public const VERSION = 'SAJU_HOUR_PILLAR_V1';
    public const TIME_BASIS = 'ADJUSTED_CIVIL_TIME';
    public const BRANCH_CONVENTION = 'ZI_2300_TWO_HOUR_HALF_OPEN_V1';

    public function __construct(
        private readonly CalculationDateResolver $dates = new CalculationDateResolver(),
        private readonly DayPillarCalculator $days = new DayPillarCalculator(),
    ) {}

    /** Required code-owned provenance; callers must supply the actual input convention. */
    public static function conventions(): array
    {
        return [
            'hourTimeBasis' => self::TIME_BASIS,
            'hourBranchConvention' => self::BRANCH_CONVENTION,
            'dayStemSource' => 'SAME_CANDIDATE_APPROVED_DAY_PILLAR',
            'dayBoundaryConvention' => 'ADJUSTED_CIVIL_2330',
            'correctionVersion' => 'KOREAN_LONGITUDE_2330',
            'dayPillarVersion' => DayPillarCalculator::IDENTIFIER,
            'hourPillarVersion' => self::VERSION,
        ];
    }

    /**
     * $dayResult is the existing service array {calculationDate, pillar}.
     * Same-date candidate lineage is not present in that DTO: upstream must bind it.
     * Only resolved candidates may call this entry point; gaps remain upstream errors.
     */
    public function calculate(DateTimeImmutable $adjusted, array $dayResult, array $provenance): array
    {
        if (PHP_INT_SIZE !== 8) {
            throw new InvalidArgumentException('HOUR_REQUIRES_64_BIT');
        }
        foreach (self::conventions() as $key => $expected) {
            if (($provenance[$key] ?? null) !== $expected) {
                throw new InvalidArgumentException('HOUR_PROVENANCE_MISMATCH');
            }
        }
        $date = $this->dates->fromAdjusted($adjusted);
        if (($dayResult['calculationDate'] ?? null) !== $date || !is_array($dayResult['pillar'] ?? null)) {
            throw new InvalidArgumentException('HOUR_DAY_RESULT_MISMATCH');
        }
        // Validate the supplied result against the approved date-only calculator.
        // This is not a civil-date alternative or a 23:00 day-stem switch.
        foreach ($this->days->calculate($date) as $key => $expected) {
            if (($dayResult['pillar'][$key] ?? null) !== $expected) {
                throw new InvalidArgumentException('HOUR_DAY_RESULT_MISMATCH');
            }
        }
        $u = ((int) $adjusted->format('H') * 3600 + (int) $adjusted->format('i') * 60
            + (int) $adjusted->format('s')) * 1000000 + (int) $adjusted->format('u');
        $hour = intdiv(($u + 3600000000) % 86400000000, 7200000000);
        $dayStem = $dayResult['pillar']['stemIndex'];
        $stemIndex = (2 * ($dayStem % 5) + $hour) % 10;
        $stem = DayPillarCalculator::STEMS[$stemIndex];
        $branch = DayPillarCalculator::BRANCHES[$hour];
        return [
            'known' => true, 'hourIndex' => $hour, 'stemIndex' => $stemIndex, 'branchIndex' => $hour,
            'stem' => $stem, 'branch' => $branch, 'ganzhi' => $stem . $branch,
            'dayStemIndexUsed' => $dayStem, 'calculationDateUsed' => $date,
        ] + $this->metadata();
    }

    public function unknown(): array
    {
        return [
            'known' => false, 'hourIndex' => null, 'stemIndex' => null, 'branchIndex' => null,
            'stem' => null, 'branch' => null, 'ganzhi' => null,
            'dayStemIndexUsed' => null, 'calculationDateUsed' => null,
        ] + $this->metadata();
    }

    private function metadata(): array
    {
        return [
            'timeBasis' => self::TIME_BASIS, 'boundaryConvention' => self::BRANCH_CONVENTION,
            'dayPillarVersion' => DayPillarCalculator::IDENTIFIER, 'hourPillarVersion' => self::VERSION,
        ];
    }
}
