<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

/** Pure, request-local Day Pillar calculation; no time correction belongs here. */
final class DayPillarCalculator
{
    public const IDENTIFIER = 'SAJU_DAY_PILLAR_EPOCH_V1';
    public const FORMULA_VERSION = 'NAOJ_JDN_GANZHI_V1';
    public const STEMS = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    public const BRANCHES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    public function __construct(private readonly GregorianDayNumber $days = new GregorianDayNumber()) {}

    public static function floorMod60(int $value): int
    {
        return (($value % 60) + 60) % 60;
    }

    /** @return array{cycleIndex:int, stemIndex:int, branchIndex:int, stem:string, branch:string, ganzhi:string} */
    public function calculate(string $calculationDate): array
    {
        $index = self::floorMod60($this->days->calculate($calculationDate) + 49);
        $stemIndex = $index % 10;
        $branchIndex = $index % 12;
        $stem = self::STEMS[$stemIndex];
        $branch = self::BRANCHES[$branchIndex];
        return [
            'cycleIndex' => $index, 'stemIndex' => $stemIndex, 'branchIndex' => $branchIndex,
            'stem' => $stem, 'branch' => $branch, 'ganzhi' => $stem . $branch,
        ];
    }

    /** Internal version metadata; not a new public API field. */
    public function metadata(): array
    {
        return [
            'identifier' => self::IDENTIFIER,
            'calendarConvention' => 'PROLEPTIC_GREGORIAN_CALCULATION_DATE',
            'dayNumberConvention' => 'INTEGER_GREGORIAN_JDN',
            'cycleConvention' => 'ZERO_BASED_JIA_ZI_0_GUI_HAI_59',
            'formula' => 'floorMod(JDN + 49, 60)',
            'formulaVersion' => self::FORMULA_VERSION,
            'goldenAnchorDate' => '2019-01-27', 'goldenAnchorJDN' => 2458511,
            'goldenAnchorIndex' => 0, 'goldenAnchorGanzhi' => '甲子',
            'evidenceVersion' => 'PHASE3_2026-09-15_APPROVED',
        ];
    }
}
