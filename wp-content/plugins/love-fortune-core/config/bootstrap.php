<?php
declare(strict_types=1);

use LoveFortune\Core\Engine\Saju\DayPillarCalculator;
use LoveFortune\Core\Engine\Saju\SajuDayPillarService;
use LoveFortune\Core\Engine\Saju\SolarTermReference;
use LoveFortune\Core\Engine\Saju\YearPillarCalculator;
use LoveFortune\Core\Engine\Saju\MonthPillarCalculator;
use LoveFortune\Core\Engine\Saju\HourPillarCalculator;
use LoveFortune\Core\Engine\Saju\NatalResolutionService;
use LoveFortune\Core\Engine\Saju\LocationReferenceRepository;
use LoveFortune\Core\Engine\Saju\TimezoneReferenceRepository;
use LoveFortune\Core\Engine\Zodiac\ZodiacCatalog;
use LoveFortune\Core\Engine\Zodiac\ZodiacDateResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacScorer;
use LoveFortune\Core\Domain\Score\LifetimeBlender;
use LoveFortune\Core\Domain\Score\DailyAggregator;
use LoveFortune\Core\Domain\Score\ExactRanking;

// Code-owned registrations only. No request input or secrets.
$dayPillar = new DayPillarCalculator();
$solarReference = new SolarTermReference();
$zodiac = ZodiacCatalog::configuration()['catalog'];
return [
    'routes' => [],
    'admin' => [],
    'migrations' => [],
    'engines' => [
        'saju.day_pillar' => $dayPillar,
        'saju.day_pillar_service' => new SajuDayPillarService($dayPillar),
        'saju.year_pillar' => new YearPillarCalculator($solarReference),
        'saju.month_pillar' => new MonthPillarCalculator($solarReference),
        'saju.hour_pillar' => new HourPillarCalculator(),
        'saju.natal_resolver' => new NatalResolutionService(solar: $solarReference),
        'zodiac.date' => new ZodiacDateResolver(),
        'zodiac.pair' => new ZodiacPairResolver(),
        'zodiac.score' => new ZodiacScorer(),
        'score.lifetime_blend' => new LifetimeBlender(),
        'score.daily_aggregate' => new DailyAggregator(),
        'score.ranking' => new ExactRanking(),
    ],
    'versions' => [
        'saju.day_pillar' => DayPillarCalculator::IDENTIFIER,
        'saju.year_pillar' => YearPillarCalculator::FORMULA_VERSION,
        'saju.month_pillar' => MonthPillarCalculator::FORMULA_VERSION,
        'saju.hour_pillar' => HourPillarCalculator::VERSION,
        'saju.natal_resolver' => NatalResolutionService::VERSION,
        'saju.location_reference' => LocationReferenceRepository::VERSION,
        'saju.timezone_reference' => TimezoneReferenceRepository::VERSION,
        'saju.solar_reference' => SolarTermReference::VERSION,
        'saju.time_scale_bridge' => SolarTermReference::BRIDGE,
        'zodiacEngineVersion' => $zodiac['modelVersion'],
        'zodiacCatalogVersion' => $zodiac['catalogVersion'],
        'zodiacDateRangeVersion' => $zodiac['dateRangeVersion'],
        'scoreVersion' => $zodiac['scoreVersion'],
    ],
];
