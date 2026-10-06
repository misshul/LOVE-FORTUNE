<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Application\Score\PeriodCalendar;
use LoveFortune\Core\Domain\Score\PeriodStatistics;
use LoveFortune\Core\Engine\Saju\FrozenDailySampleResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;
use LoveFortune\Core\Support\Rational as R;

final class PeriodResultValidation
{
    private static function keys(array $a,array $required,array $optional=[]): void
    {
        if(array_diff($required,array_keys($a))||array_diff(array_keys($a),[...$required,...$optional])){throw new \InvalidArgumentException('INVALID_PERIOD_RESULT');}
    }
    private static function number(mixed $n,int $min,int $max): void
    {
        if((!is_int($n)&&!is_float($n))||!is_finite((float)$n)||$n<$min||$n>$max){throw new \InvalidArgumentException('INVALID_PERIOD_NUMBER');}
        $r=R::of(CanonicalJson::encode($n));if($r->compare(R::of($r->halfUp4()))!==0){throw new \InvalidArgumentException('INVALID_PERIOD_PRECISION');}
    }
    public static function needsSignature(array $r): bool
    {
        return !isset($r['days'])&&($r['weeklyScore']??$r['monthlyScore']??$r['yearlyScore']??null)!==null;
    }
    public static function validate(array $r,?string $expected=null): void
    {
        self::keys($r['meta'],['requestId','versions']);
        if(!is_string($r['meta']['requestId'])||!preg_match('/^[0-9a-f]{32}$/D',$r['meta']['requestId'])||CanonicalJson::encode($r['meta']['versions'])!==CanonicalJson::encode(PeriodRelease::versions())){throw new \InvalidArgumentException('INVALID_PERIOD_VERSION');}
        if((new FrozenDailySampleResolver())->canonicalize($r['targetTimezone'])!==$r['targetTimezone']){throw new \InvalidArgumentException('INVALID_PERIOD_TIMEZONE');}
        if(array_key_exists('days',$r)){
            if($expected!==null&&$expected!=='RANGE'){throw new \InvalidArgumentException('INVALID_PERIOD_TYPE');}
            self::keys($r,['meta','startDate','endDate','targetTimezone','days']);$dates=PeriodCalendar::dates($r['startDate'],$r['endDate'],31);
            if(!array_is_list($r['days'])||count($r['days'])!==count($dates)){throw new \InvalidArgumentException('INVALID_PERIOD_MEMBERSHIP');}
            foreach($r['days'] as $i=>$d){DailyResultValidation::validate($d);if($d['date']!==$dates[$i]||$d['targetTimezone']!==$r['targetTimezone']){throw new \InvalidArgumentException('INVALID_PERIOD_MEMBERSHIP');}}return;
        }
        $kind=$r['period']['type']??null;
        if(!in_array($kind,['WEEK','MONTH','YEAR'],true)||($expected!==null&&$kind!==$expected)){throw new \InvalidArgumentException('INVALID_PERIOD_TYPE');}
        $prefix=match($kind){'WEEK'=>'weekly','MONTH'=>'monthly','YEAR'=>'yearly'};$scoreKey=$prefix.'Score';$vKey=$kind==='YEAR'?'yearlyVolatility':'volatility';$best=$kind==='YEAR'?'bestMonths':'bestDays';$caution=$kind==='YEAR'?'cautionMonths':'cautionDays';
        self::keys($r,['meta','period','targetTimezone',$scoreKey,'periodDelta','coverage','resultConfidence','status','trendStatus','trendDirection',$vKey,$best,$caution,'features','warnings'],$kind==='WEEK'?['slope','zodiacContext']:['zodiacContext']);
        self::keys($r['period'],$kind==='WEEK'?['type','startDate','endDate','timezone']:($kind==='MONTH'?['type','year','month','timezone']:['type','year','timezone']));
        $input=$kind==='WEEK'?['weekStartDate'=>$r['period']['startDate']]:$r['period'];$dates=PeriodCalendar::membership($kind,$input);
        if($r['period']['timezone']!==$r['targetTimezone']||($kind==='WEEK'&&$r['period']['endDate']!==$dates[6])){throw new \InvalidArgumentException('INVALID_PERIOD_MEMBERSHIP');}
        self::number($r['coverage'],0,1);self::number($r['resultConfidence'],0,1);
        if($r['features']!==[]||$r['warnings']!==[]){throw new \InvalidArgumentException('INVALID_PERIOD_EVIDENCE');}
        if($r[$scoreKey]===null){
            if($r['periodDelta']!==null||$r['resultConfidence']!=0||$r['trendStatus']!=='INSUFFICIENT_DATA'||$r['trendDirection']!=='UNKNOWN'||$r[$vKey]!==null||$r[$best]!==[]||$r[$caution]!==[]||($r['slope']??null)!==null){throw new \InvalidArgumentException('INVALID_PERIOD_EMPTY');}
        }else{
            self::number($r[$scoreKey],0,100);self::number($r['periodDelta'],-100,100);self::number($r[$vKey],0,50);
            if(!in_array($r['trendStatus'],['STABLE','NOTICEABLE','SIGNIFICANT'],true)||($r['trendStatus']==='STABLE'?$r['trendDirection']!=='STABLE':!in_array($r['trendDirection'],['UP','DOWN'],true))){throw new \InvalidArgumentException('INVALID_PERIOD_TREND');}
        }
        if($r['status']!==PeriodStatistics::status($r[$scoreKey]===null?null:R::of(CanonicalJson::encode($r[$scoreKey])))){throw new \InvalidArgumentException('INVALID_PERIOD_STATUS');}
        if(isset($r['slope'])){self::number($r['slope'],-100,100);}
        $seen=[];$max=match($kind){'WEEK'=>2,'MONTH'=>5,'YEAR'=>3};
        foreach([$best,$caution] as $key){if(!array_is_list($r[$key])||count($r[$key])>$max){throw new \InvalidArgumentException('INVALID_PERIOD_RANKING');}
            foreach($r[$key] as $item){self::keys($item,['date','score','resultConfidence']);if(!in_array($item['date'],$dates,true)||isset($seen[$item['date']])){throw new \InvalidArgumentException('INVALID_PERIOD_RANKING');}$seen[$item['date']]=true;self::number($item['score'],0,100);self::number($item['resultConfidence'],0,1);}}
        if(isset($r['zodiacContext'])){$c=$r['zodiacContext'];if(CanonicalJson::encode($c)!==CanonicalJson::encode((new ZodiacPairResolver())->resolve($c['signA'],$c['signB']))){throw new \InvalidArgumentException('INVALID_PERIOD_CONTEXT');}}
        // Exact delta is not recoverable from the rounded wire: never reclassify trend here.
    }
}
