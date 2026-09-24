<?php
declare(strict_types=1);

namespace LoveFortune\Core\Domain\Score;

use LoveFortune\Core\Engine\Zodiac\ZodiacCatalog;
use LoveFortune\Core\Support\Rational as R;

final class LifetimeBlender
{
    /** Inputs are source category results AFTER Saju SC-07. No feature/source double weighting. */
    public function blend(array $saju, array $zodiac): array
    {
        $config = ZodiacCatalog::configuration();
        $weights = $config['categoryWeights'];
        $scope = $config['scope'];
        foreach (['SAJU' => $saju, 'ZODIAC' => $zodiac] as $source => $rows) {
            foreach ($rows as $category => $row) {
                if (!isset($weights[$category]) || !$row instanceof CategoryResult
                    || $category === 'COMMUNICATION'
                    || ($source === 'ZODIAC' && !in_array($category, $scope['zodiacScoringCategories'], true))) {
                    throw new \InvalidArgumentException('Ineligible source category.');
                }
            }
        }
        $out = [];
        foreach ($weights as $category => $_) {
            if ($category === 'COMMUNICATION') { $out[$category] = CategoryResult::unavailable(); continue; }
            $eligible = ['SAJU'];
            if (in_array($category, $scope['zodiacScoringCategories'], true)) { $eligible[] = 'ZODIAC'; }
            $den = $delta = $coverage = $confidence = R::of(0);
            $computable = false;
            foreach ($eligible as $source) {
                $weight = R::of($scope['sourceWeights'][$source]);
                $den = $den->add($weight); // Runtime missing still occupies this denominator.
                $row = ($source === 'SAJU' ? $saju : $zodiac)[$category] ?? CategoryResult::unavailable();
                $coverage = $coverage->add($weight->multiply($row->coverage));
                $confidence = $confidence->add($weight->multiply($row->confidence));
                if ($row->score !== null) {
                    $computable = true;
                    $delta = $delta->add($weight->multiply($row->score->subtract(R::of(50))));
                }
            }
            $out[$category] = new CategoryResult($computable ? R::of(50)->add($delta->divide($den)) : null, $coverage->divide($den), $confidence->divide($den));
        }
        $score = $pre = $coverage = $computableWeight = $eligibleWeight = R::of(0);
        foreach ($out as $category => $row) {
            if ($category === 'COMMUNICATION') { continue; }
            $weight = R::of($weights[$category]);
            $eligibleWeight = $eligibleWeight->add($weight);
            $coverage = $coverage->add($weight->multiply($row->coverage));
            if ($row->score !== null) {
                $computableWeight = $computableWeight->add($weight);
                $score = $score->add($weight->multiply($row->score));
                $pre = $pre->add($weight->multiply($row->preCoverageConfidence()));
            }
        }
        $coverage = $coverage->divide($eligibleWeight);
        $pre = $computableWeight->isZero() ? R::of(0) : $pre->divide($computableWeight);
        return ['categories' => $out, 'overall' => new CategoryResult($computableWeight->isZero() ? null : $score->divide($computableWeight), $coverage, $coverage->multiply($pre)), 'overallPreCoverageConfidence' => $pre];
    }
}
