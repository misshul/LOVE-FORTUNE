<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

use LoveFortune\Core\Domain\Score\SajuCategoryScorer;

/** Internal source category results only: no overall, source blend or public transport. */
final class SajuLifetimeScoringService
{
    public const VERSION='SAJU_LIFETIME_SCORING_V1';
    public const DEPENDENCIES=[
        'featureExtractorVersion'=>SajuFeatureExtractionService::VERSION,'catalogVersion'=>SajuFeatureCatalog::VERSION,
        'guardrailVersion'=>SajuCategoryScorer::GUARDRAIL_VERSION,'guardrailContract'=>'GUARDRAIL_V2',
        'multiIdentityVersion'=>SajuFeatureAggregator::MULTI_VERSION,'coverageContract'=>'FE-COV-01',
    ];

    public function __construct(string $scoringVersion=self::VERSION,array $dependencies=self::DEPENDENCIES)
    {
        $expected=self::DEPENDENCIES;ksort($expected,SORT_STRING);ksort($dependencies,SORT_STRING);
        if($scoringVersion!==self::VERSION || $dependencies!==$expected){throw new \InvalidArgumentException('SCORING_CONTRACT_MISMATCH');}
    }

    public function score(array $extraction): array
    {
        $validated=(new SajuLifetimeScoringValidator())->validate($extraction);
        $scorer=new SajuCategoryScorer();$categories=[];$diagnostics=[];
        foreach($validated['featuresByCategory'] as $category=>$features){
            $r=$scorer->score($features,$validated['coverage'][$category]['coverage']);
            $categories[$category]=$r['result'];$diagnostics[$category]=$r['diagnostics'];
        }
        return ['scoringVersion'=>self::VERSION,'dependencies'=>self::DEPENDENCIES,
            'categories'=>$categories,'diagnostics'=>$diagnostics,'tenGods'=>$extraction['tenGods']];
    }
}
