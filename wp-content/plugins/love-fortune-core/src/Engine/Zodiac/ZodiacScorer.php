<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Zodiac;

use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Support\Rational as R;

final class ZodiacScorer
{
    /** Registered numeric evidence only; static context is a separate object. */
    public function features(string $signA, string $signB): array
    {
        $context = (new ZodiacPairResolver())->resolve($signA, $signB);
        $features = [];
        foreach (ZodiacCatalog::configuration()['catalog']['rules'] as $rule) {
            if (!$rule['enabled'] || $rule['productScope'] !== 'V1' || !ZodiacCatalog::allows($rule['source'], $rule['period']) || $rule['relation'] !== $context['primaryRelation']) { continue; }
            $metadata = [
                'signA' => $context['signA'], 'signB' => $context['signB'],
                'pairId' => $context['pairId'], 'relation' => $context['primaryRelation'],
                'modelVersion' => $context['modelVersion'], 'dateRangeVersion' => $context['dateRangeVersion'],
            ];
            $identity = ['ruleId' => $rule['ruleId'], 'subject' => 'PAIR', 'category' => $rule['category'], 'period' => ['type' => 'LIFETIME'], 'source' => 'ZODIAC', 'metadata' => $metadata];
            ksort($identity['metadata'], SORT_STRING);
            ksort($identity, SORT_STRING);
            $direction = R::of($rule['signedValue'])->compare(R::of(0));
            $features[] = [
                'featureId' => 'ft_' . substr(hash('sha256', json_encode($identity, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)), 0, 48),
                'ruleId' => $rule['ruleId'], 'source' => 'ZODIAC', 'subject' => 'PAIR', 'category' => $rule['category'],
                'direction' => $direction > 0 ? 'POSITIVE' : ($direction < 0 ? 'NEGATIVE' : 'NEUTRAL'),
                // Numeric wire projection only. Arithmetic and ranking never consume floats.
                'signedValue' => (float) $rule['signedValue'], 'rawValue' => (float) $rule['signedValue'],
                'baseWeight' => 1, 'confidence' => 0.75, 'period' => ['type' => 'LIFETIME'], 'metadata' => $metadata,
            ];
        }
        return $features;
    }

    /** Only primary relation contributes; modality/polarity never add a bonus. */
    public function score(string $signA, string $signB): array
    {
        $context = (new ZodiacPairResolver())->resolve($signA, $signB);
        $out = [];
        foreach (ZodiacCatalog::configuration()['catalog']['rules'] as $rule) {
            if ($rule['enabled'] && $rule['productScope'] === 'V1' && ZodiacCatalog::allows($rule['source'], $rule['period']) && $rule['relation'] === $context['primaryRelation']) {
                $out[$rule['category']] = new CategoryResult(R::of(50)->add(R::of(50)->multiply(R::of($rule['signedValue']))), R::of(1), R::of($rule['ruleConfidence']));
            }
        }
        return $out;
    }
}
