<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Score;
use LoveFortune\Core\Domain\Score\{PeriodStatistics as S,ExactRanking};
use LoveFortune\Core\Support\Rational as R;
use LoveFortune\Core\Engine\Saju\FrozenDailySampleResolver;

final class PeriodOrchestrationService
{
    private ?DailyOrchestrationService $daily=null;
    private ?FrozenDailySampleResolver $resolver=null;
    public const VERSION='SAJU_PERIOD_ORCHESTRATION_V1';
    public static function dependencies(): array
    {
        return ['dailyVersion'=>DailyOrchestrationService::VERSION,'dailyDependencies'=>DailyOrchestrationService::dependencies(),
            'dailyConfig'=>'CONFIG_DAILY_V1','range'=>'INCLUSIVE_1_31','week'=>'ISO_MONDAY_SUNDAY','month'=>'GREGORIAN','year'=>'GREGORIAN_12_MONTHS',
            'score'=>'VALID_CHILD_EXACT_MEAN','coverage'=>'ALL_CALENDAR_CHILD_MEAN','confidence'=>'VALID_NUMERIC_CHILD_MEAN',
            'missing'=>'EXCLUDE_NULL_AND_DAILY_FALLBACK_ERROR_FATAL','status'=>'CANONICAL_LIFETIME_BANDS','trend'=>['exact','3','8'],
            'volatility'=>'EXACT_POPULATION_VARIANCE_INTEGER_SQRT_HALF_UP4','ranking'=>'BEST_FIRST_NONOVERLAP_EXACT_SCORE_CONFIDENCE_DATE',
            'rankingMax'=>['WEEK'=>2,'MONTH'=>5,'YEAR'=>3],'wire'=>'HALF_UP4','evidence'=>'EMPTY','signing'=>'NUMERIC_WMY_REQUIRED_RANGE_NONE'];
    }
    /** Rows are exact internal Daily overall results, or exact Monthly results for YEAR. */
    public function aggregate(string $type,array $membership,array $children,?R $baseline,string $timezone,array $contexts=[]): array
    {
        if(!in_array($type,['WEEK','MONTH','YEAR'],true)||!array_is_list($membership)||count($membership)!==count($children)||$membership===[]||array_keys($children)!==$membership){throw new \InvalidArgumentException('INVALID_PERIOD_MEMBERSHIP');}
        $expected=PeriodCalendar::membership($type,$type==='WEEK'?['weekStartDate'=>$membership[0]]:['year'=>(int)substr($membership[0],0,4),'month'=>(int)substr($membership[0],5,2)]);
        if($membership!==$expected){throw new \InvalidArgumentException('INVALID_PERIOD_MEMBERSHIP');}
        $timezone=($this->resolver??=new FrozenDailySampleResolver())->canonicalize($timezone);
        $valid=[];$coverages=[];
        foreach($membership as $i=>$date){$r=$children[$date];
            foreach(['coverage','resultConfidence'] as $k){if(!($r[$k]??null) instanceof R||$r[$k]->compare(R::of(0))<0||$r[$k]->compare(R::of(1))>0){throw new \InvalidArgumentException('INVALID_PERIOD_CHILD');}}
            if(!array_key_exists('score',$r)||($r['score']!==null&&!$r['score'] instanceof R)){throw new \InvalidArgumentException('INVALID_PERIOD_CHILD');}
            if($r['score']!==null&&($r['score']->compare(R::of(0))<0||$r['score']->compare(R::of(100))>0)){throw new \InvalidArgumentException('INVALID_PERIOD_CHILD');}
            if($type!=='YEAR'&&!in_array($r['dailyStatus']??null,['VERY_LOW','LOW','STABLE','GOOD','VERY_GOOD','INSUFFICIENT_DATA','INSUFFICIENT_PERIOD_DATA'],true)){throw new \InvalidArgumentException('INVALID_PERIOD_CHILD');}
            $coverages[]=$r['coverage'];
            if($baseline!==null&&$r['score']!==null&&($type==='YEAR'||$r['dailyStatus']!=='INSUFFICIENT_PERIOD_DATA')){$valid[]=['date'=>$date,'score'=>$r['score'],'confidence'=>$r['resultConfidence'],'x'=>R::of($i)];}
        }
        $score=$valid===[]?null:S::mean(array_column($valid,'score'));$delta=$score?->subtract($baseline);
        [$trend,$direction]=S::trend($delta);$rank=new ExactRanking();$max=self::dependencies()['rankingMax'][$type];
        $best=array_slice($rank->rank($valid),0,$max);$ids=array_column($best,'date');
        $caution=array_slice($rank->rank(array_values(array_filter($valid,static fn($r)=>!in_array($r['date'],$ids,true))),true),0,$max);
        return ['periodVersion'=>self::VERSION,'dependencies'=>self::dependencies(),'periodType'=>$type,'membership'=>$membership,'targetTimezone'=>$timezone,
            'lifetimeScore'=>$baseline,'score'=>$score,'periodDelta'=>$delta,'coverage'=>S::mean($coverages),
            'resultConfidence'=>$valid===[]?R::of(0):S::mean(array_column($valid,'confidence')),'status'=>S::status($score),
            'trendStatus'=>$trend,'trendDirection'=>$direction,'variance'=>S::variance(array_column($valid,'score')),
            'slope'=>$type==='WEEK'?S::slope($valid):null,'rankings'=>['best'=>$best,'caution'=>$caution],'warnings'=>[],'contexts'=>$contexts];
    }
    public function calculate(string $type,array $input,array $base): array
    {
        $dates=PeriodCalendar::membership($type,$input);$zone=($this->resolver??=new FrozenDailySampleResolver())->canonicalize($input['targetTimezone']);
        $baseline=$base['combined']['overall']->score;$contexts=$base['combined']['contexts'];$children=[];
        if($type==='YEAR'){
            foreach($dates as $date){$month=$input;$month['month']=(int)substr($date,5,2);$children[$date]=$this->calculate('MONTH',$month,$base);}
        }else{
            $daily=$this->daily??=new DailyOrchestrationService();
            foreach($dates as $date){$d=$daily->calculate($base['combined'],$base['natal'][0],$base['natal'][1],$date,$zone);$children[$date]=$type==='RANGE'?$d:$d['overall'];}
        }
        if($type==='RANGE'){return ['periodVersion'=>self::VERSION,'dependencies'=>self::dependencies(),'startDate'=>$dates[0],'endDate'=>$dates[count($dates)-1],'targetTimezone'=>$zone,'days'=>array_values($children),'warnings'=>[]];}
        return $this->aggregate($type,$dates,$children,$baseline,$zone,$contexts);
    }
}
