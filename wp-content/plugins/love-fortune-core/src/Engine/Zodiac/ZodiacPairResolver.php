<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Zodiac;

final class ZodiacPairResolver
{
    /** A/B are canonical sign positions, not retained person identities. */
    public function resolve(string $signA, string $signB): array
    {
        $catalog = ZodiacCatalog::configuration()['catalog'];
        $signs = $catalog['referenceTables']['signs'];
        $names = array_column($signs, 'sign');
        $a = array_search($signA, $names, true);
        $b = array_search($signB, $names, true);
        if ($a === false || $b === false) {
            throw new \InvalidArgumentException('Unknown Zodiac sign.');
        }
        if ($a > $b) { [$a, $b] = [$b, $a]; }
        $left = $signs[$a];
        $right = $signs[$b];
        $element = $left['element'] === $right['element'] ? 'SAME_ELEMENT'
            : ($left['polarity'] === $right['polarity'] ? 'COMPATIBLE_ELEMENT' : 'CHALLENGING_ELEMENT');
        $relation = $a === $b ? 'SAME_SIGN' : ($left['opposite'] === $right['sign'] ? 'OPPOSITE_SIGN' : $element);
        $context = [
            'source' => 'ZODIAC', 'contextRole' => 'STATIC',
            'signA' => $left['sign'], 'signB' => $right['sign'],
            'pairId' => 'ZODIAC_PAIR_' . $left['sign'] . '_' . $right['sign'],
            'primaryRelation' => $relation, 'elementRelation' => $element,
            'modalityRelation' => $left['modality'] === $right['modality'] ? 'SAME_MODALITY' : 'DIFFERENT_MODALITY',
            'polarityRelation' => $left['polarity'] === $right['polarity'] ? 'SAME_POLARITY' : 'DIFFERENT_POLARITY',
            'modelVersion' => $catalog['modelVersion'], 'dateRangeVersion' => $catalog['dateRangeVersion'],
        ];
        // Closed, string-only identity: sorted UTF-8 JSON, same in PHP and JS.
        $identity = $context;
        ksort($identity, SORT_STRING);
        $context['contextEvidenceId'] = 'ce_' . substr(hash('sha256', json_encode($identity, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)), 0, 48);
        return $context;
    }
}
