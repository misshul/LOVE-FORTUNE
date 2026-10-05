<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

use LoveFortune\Core\Application\Score\CombinedLifetimeService;
use LoveFortune\Core\Application\Score\LifetimeSourceEnvelope as E;
use LoveFortune\Core\Engine\Saju\NatalResolutionService;
use LoveFortune\Core\Engine\Saju\SajuFeatureExtractionService;
use LoveFortune\Core\Engine\Saju\SajuLifetimeScoringService;
use LoveFortune\Core\Engine\Zodiac\ZodiacDateResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacScorer;

final class CompatibilityCalculation
{
    public function calculate(array $input,string $requestId): array
    {
        $natal=new NatalResolutionService();$people=[];$signs=[];
        foreach(['personA','personB'] as $side){
            $p=$input[$side];$people[]=$natal->resolve($p['birthDate'],$p['birthLocationId'],$p['birthTime']);
            $signs[]=(new ZodiacDateResolver())->resolve($p['birthDate'])['sign'];
        }
        $extraction=(new SajuFeatureExtractionService())->extract(...$people);
        $saju=(new SajuLifetimeScoringService())->score($extraction);
        $zodiac=new ZodiacScorer();$context=(new ZodiacPairResolver())->resolve(...$signs);
        $combined=(new CombinedLifetimeService())->score([E::fromSaju($saju),E::fromEligible('ZODIAC',E::IDENTITIES['ZODIAC'],$zodiac->score(...$signs),$context)]);
        return (new CompatibilityProjection())->project($combined,[...$extraction['features'],...$zodiac->features(...$signs)],$requestId);
    }
}
