<?php
declare(strict_types=1);

namespace LoveFortune\Core\Domain\Score;

use LoveFortune\Core\Support\Rational as R;

final readonly class CategoryResult
{
    public function __construct(public ?R $score, public R $coverage, public R $confidence)
    {
        $zero = R::of(0);
        $one = R::of(1);
        if ($coverage->compare($zero) < 0 || $coverage->compare($one) > 0 || $confidence->compare($zero) < 0 || $confidence->compare($coverage) > 0
            || ($score !== null && ($score->compare($zero) < 0 || $score->compare(R::of(100)) > 0 || $coverage->isZero()))
            || ($score === null && !$confidence->isZero())) {
            throw new \InvalidArgumentException('Invalid category result.');
        }
    }

    public static function unavailable(?R $coverage = null): self
    {
        return new self(null, $coverage ?? R::of(0), R::of(0));
    }

    public function preCoverageConfidence(): R
    {
        return $this->coverage->isZero() ? R::of(0) : $this->confidence->divide($this->coverage);
    }

    public function status(): string
    {
        if ($this->score === null) { return 'INSUFFICIENT_DATA'; }
        $rounded = R::of($this->score->halfUp4());
        foreach ([45 => 'CAUTION', 60 => 'BALANCED', 75 => 'GOOD', 85 => 'VERY_GOOD'] as $bound => $status) {
            if ($rounded->compare(R::of($bound)) < 0) { return $status; }
        }
        return 'EXCELLENT';
    }
}
