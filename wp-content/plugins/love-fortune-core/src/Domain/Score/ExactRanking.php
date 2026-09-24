<?php
declare(strict_types=1);

namespace LoveFortune\Core\Domain\Score;

use LoveFortune\Core\Engine\Saju\GregorianDayNumber;
use LoveFortune\Core\Support\Rational;

final class ExactRanking
{
    /** @param list<array{date: string, score: Rational, confidence: Rational}> $rows */
    public function rank(array $rows, bool $caution = false): array
    {
        foreach ($rows as $row) {
            (new GregorianDayNumber())->assertPublicDate($row['date']);
            if (!$row['score'] instanceof Rational || !$row['confidence'] instanceof Rational) {
                throw new \InvalidArgumentException('Ranking requires unrounded exact values.');
            }
        }
        usort($rows, static fn (array $a, array $b): int => ($caution ? 1 : -1) * $a['score']->compare($b['score'])
            ?: -$a['confidence']->compare($b['confidence']) ?: strcmp($a['date'], $b['date']));
        return $rows;
    }
}
