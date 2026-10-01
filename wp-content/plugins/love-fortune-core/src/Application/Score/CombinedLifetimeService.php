<?php
declare(strict_types=1);

namespace LoveFortune\Core\Application\Score;

use LoveFortune\Core\Domain\Score\LifetimeBlender;
use LoveFortune\Core\Engine\Zodiac\ZodiacCatalog;

/** Versioned orchestration only. LifetimeBlender owns every numeric calculation. */
final class CombinedLifetimeService
{
    public const VERSION = 'LIFETIME_COMBINED_SCORING_V1';
    public const DEPENDENCIES = [
        'sajuScoringVersion'=>'SAJU_LIFETIME_SCORING_V1', 'sajuCatalogVersion'=>'1.0.0',
        'zodiacEngineVersion'=>'FIXED_DATE_SINGLE_SIGN_V1', 'zodiacCatalogVersion'=>'ZODIAC_CATALOG_V1',
        'zodiacDateRangeVersion'=>'ZODIAC_DATE_RANGE_V1', 'productScopeVersion'=>'ZODIAC_V1',
        'categoryWeightsVersion'=>'2.0.0',
    ];

    public function __construct(string $combinedVersion = self::VERSION, array $dependencies = self::DEPENDENCIES)
    {
        if ($combinedVersion !== self::VERSION
            || LifetimeSourceEnvelope::canonical($dependencies) !== LifetimeSourceEnvelope::canonical(self::DEPENDENCIES)) {
            throw new \InvalidArgumentException('COMBINED_CONTRACT_MISMATCH');
        }
        $config = ZodiacCatalog::configuration();
        if ($config['catalog']['modelVersion'] !== self::DEPENDENCIES['zodiacEngineVersion']
            || $config['catalog']['catalogVersion'] !== self::DEPENDENCIES['zodiacCatalogVersion']
            || $config['catalog']['dateRangeVersion'] !== self::DEPENDENCIES['zodiacDateRangeVersion']
            || $config['scope']['contractVersion'] !== self::DEPENDENCIES['productScopeVersion']
            || $config['scope']['sourceWeights'] !== ['SAJU'=>'4/5','ZODIAC'=>'1/5']
            || $config['scope']['lifetimeSources'] !== ['SAJU','ZODIAC']
            || $config['scope']['zodiacScoringCategories'] !== LifetimeSourceEnvelope::ELIGIBLE['ZODIAC']
            || array_keys($config['categoryWeights']) !== LifetimeSourceEnvelope::CATEGORIES) {
            throw new \InvalidArgumentException('COMBINED_CONTRACT_MISMATCH');
        }
    }

    /** Exactly two typed source envelopes. Source order is immaterial. */
    public function score(array $sources): array
    {
        if (!array_is_list($sources) || count($sources) !== 2) { throw new \InvalidArgumentException('INVALID_COMBINED_SOURCE'); }
        $bySource = [];
        foreach ($sources as $source) {
            if (!$source instanceof LifetimeSourceEnvelope || isset($bySource[$source->sourceId])) {
                throw new \InvalidArgumentException('INVALID_COMBINED_SOURCE');
            }
            $bySource[$source->sourceId] = $source;
        }
        if (!isset($bySource['SAJU'], $bySource['ZODIAC'])) { throw new \InvalidArgumentException('INVALID_COMBINED_SOURCE'); }
        $numeric = (new LifetimeBlender())->blend($bySource['SAJU']->blenderCategories(), $bySource['ZODIAC']->blenderCategories());
        $contexts = [];
        foreach (['SAJU','ZODIAC'] as $id) {
            $source = $bySource[$id];
            if ($source->sourceContext !== null) {
                $contexts[$id] = ['sourceId'=>$id, 'versions'=>$source->versions, 'context'=>$source->sourceContext];
            }
        }
        return ['combinedVersion'=>self::VERSION, 'dependencies'=>LifetimeSourceEnvelope::canonical(self::DEPENDENCIES),
            'categories'=>$numeric['categories'], 'overall'=>$numeric['overall'],
            'overallPreCoverageConfidence'=>$numeric['overallPreCoverageConfidence'], 'contexts'=>$contexts];
    }
}
