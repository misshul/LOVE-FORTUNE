<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

final class SajuBranchPolarity
{
    public const VERSION = 'SAJU_BRANCH_POLARITY_V1';
    public static function of(int $index, string $symbol): string
    {
        if ($index < 0 || $index > 11 || DayPillarCalculator::BRANCHES[$index] !== $symbol) {
            throw new \InvalidArgumentException('UNSUPPORTED_PILLAR_VALUE');
        }
        return $index % 2 === 0 ? 'YANG' : 'YIN';
    }
}
