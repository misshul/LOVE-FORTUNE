<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

use LoveFortune\Core\Application\Score\CombinedLifetimeService;
use LoveFortune\Core\Application\Score\LifetimeSourceEnvelope;
use LoveFortune\Core\Engine\Saju\LocationReferenceRepository;
use LoveFortune\Core\Engine\Saju\TimezoneReferenceRepository;

final class PublicRelease
{
    public const SCORE = 'SCORE_COMBINED_LIFETIME_V1';
    public const CONFIG = 'CONFIG_COMBINED_LIFETIME_V1';
    public static function manifest(): array
    {
        return CombinedLifetimeService::DEPENDENCIES + [
            'combinedVersion'=>CombinedLifetimeService::VERSION,
            'sourceWeights'=>['SAJU'=>'4/5','ZODIAC'=>'1/5'],
            'eligibility'=>LifetimeSourceEnvelope::ELIGIBLE,
            'm2'=>'FIXED_STRUCTURALLY_ELIGIBLE_SOURCE_DENOMINATOR',
            'statusThresholds'=>[45,60,75,85],
        ];
    }
    public static function versions(): array
    {
        return ['serviceVersion'=>'0.1.0','apiVersion'=>'v1','sajuEngineVersion'=>'SAJU_LIFETIME_SCORING_V1',
            'scoreVersion'=>self::SCORE,'configVersion'=>self::CONFIG,
            'locationReferenceVersion'=>LocationReferenceRepository::VERSION,
            'timezoneDataVersion'=>TimezoneReferenceRepository::VERSION,
            'zodiacEngineVersion'=>'FIXED_DATE_SINGLE_SIGN_V1','zodiacCatalogVersion'=>'ZODIAC_CATALOG_V1',
            'zodiacDateRangeVersion'=>'ZODIAC_DATE_RANGE_V1'];
    }
}
