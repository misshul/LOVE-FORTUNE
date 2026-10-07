<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Interpretation;

/** Accept only a context already verified by InterpretationContext. Never project meta. */
final class ProviderInput
{
    public static function project(array $context): array
    {
        $r=$context['result'];
        $type=isset($r['period'])?match($r['period']['type']){'WEEK'=>'Weekly','MONTH'=>'Monthly','YEAR'=>'Yearly'}:(array_key_exists('dailyScore',$r)?'Daily':'Compatibility');
        $fields=match($type){
            'Compatibility'=>['overallScore','status','coverage','resultConfidence','categories'],
            'Daily'=>['date','targetTimezone','lifetimeScore','dailyScore','dailyDelta','dailyStatus','coverage','resultConfidence','categoryScores'],
            'Weekly'=>['period','weeklyScore','periodDelta','status','trendStatus','trendDirection','coverage','resultConfidence','bestDays','cautionDays','volatility','slope'],
            'Monthly'=>['period','monthlyScore','periodDelta','status','trendStatus','trendDirection','coverage','resultConfidence','bestDays','cautionDays','volatility'],
            'Yearly'=>['period','yearlyScore','periodDelta','status','trendStatus','trendDirection','coverage','resultConfidence','bestMonths','cautionMonths','yearlyVolatility'],
        };
        $dto=['resultType'=>$type,'locale'=>$context['locale'],'aiPromptVersion'=>InterpretationText::VERSION];
        foreach($fields as $field){if(array_key_exists($field,$r)){$dto[$field]=$r[$field];}}
        $dto['evidence']=$type==='Compatibility'?$context['evidence']:[];
        if(isset($r['zodiacContext'])){$dto['zodiacContext']=$r['zodiacContext'];}
        return $dto;
    }
}
