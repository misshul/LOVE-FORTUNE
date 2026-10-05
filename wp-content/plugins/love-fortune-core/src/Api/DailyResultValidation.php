<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Application\Score\{DailyOrchestrationService,LifetimeSourceEnvelope};
use LoveFortune\Core\Engine\Saju\{FrozenDailySampleResolver,NatalCivilTime};
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;
use LoveFortune\Core\Support\Rational as R;

final class DailyResultValidation
{
    public static function needsSignature(array $r): bool { return $r['dailyScore']!==null && $r['dailyStatus']!=='INSUFFICIENT_PERIOD_DATA'; }
    private static function keys(array $a,array $required,array $optional=[]): void
    {
        if(array_diff($required,array_keys($a))!==[]||array_diff(array_keys($a),[...$required,...$optional])!==[]){throw new \InvalidArgumentException('INVALID_DAILY_RESULT');}
    }
    private static function number(mixed $n,int $min,int $max): void
    {
        if((!is_int($n)&&!is_float($n))||!is_finite((float)$n)||$n<$min||$n>$max){throw new \InvalidArgumentException('INVALID_DAILY_RESULT');}
        $r=R::of(CanonicalJson::encode($n));if($r->compare(R::of($r->halfUp4()))!==0){throw new \InvalidArgumentException('INVALID_DAILY_RESULT');}
    }
    public static function validate(array $r): void
    {
        self::keys($r,['meta','date','targetTimezone','lifetimeScore','dailyScore','dailyDelta','dailyStatus','coverage','resultConfidence','features','warnings'],['categoryScores','zodiacContext']);
        self::keys($r['meta'],['requestId','versions']);
        if(!is_string($r['meta']['requestId'])||!preg_match('/^[0-9a-f]{32}$/D',$r['meta']['requestId'])||CanonicalJson::encode($r['meta']['versions'])!==CanonicalJson::encode(DailyRelease::versions())){throw new \InvalidArgumentException('INVALID_DAILY_VERSION');}
        NatalCivilTime::input($r['date'],'00:00');
        if((new FrozenDailySampleResolver())->canonicalize($r['targetTimezone'])!==$r['targetTimezone']){throw new \InvalidArgumentException('INVALID_DAILY_TIMEZONE');}
        foreach(['lifetimeScore','dailyScore'] as $key){if($r[$key]!==null){self::number($r[$key],0,100);}}
        self::number($r['dailyDelta'],-18,18);self::number($r['coverage'],0,1);self::number($r['resultConfidence'],0,1);
        if($r['features']!==[]||$r['warnings']!==[]){throw new \InvalidArgumentException('INVALID_DAILY_EVIDENCE');}
        if($r['lifetimeScore']===null){
            if($r['dailyScore']!==null||$r['dailyDelta']!=0||$r['dailyStatus']!=='INSUFFICIENT_DATA'||$r['resultConfidence']!=0){throw new \InvalidArgumentException('INVALID_DAILY_NULL');}
        }elseif($r['dailyStatus']==='INSUFFICIENT_PERIOD_DATA'){
            if($r['dailyScore']!==$r['lifetimeScore']||$r['dailyDelta']!=0||$r['resultConfidence']!=0){throw new \InvalidArgumentException('INVALID_DAILY_FALLBACK');}
        }elseif($r['dailyScore']===null||$r['dailyStatus']!==DailyOrchestrationService::status(R::of(CanonicalJson::encode($r['dailyDelta'])))){
            throw new \InvalidArgumentException('INVALID_DAILY_STATUS');
        }
        // Confidence may exceed coverage; do not apply Lifetime's invariant here.
        if(isset($r['categoryScores'])){self::keys($r['categoryScores'],LifetimeSourceEnvelope::CATEGORIES);foreach($r['categoryScores'] as $v){if($v!==null){self::number($v,0,100);}}if($r['categoryScores']['COMMUNICATION']!==null){throw new \InvalidArgumentException('INVALID_DAILY_COMMUNICATION');}}
        if(isset($r['zodiacContext'])){$c=$r['zodiacContext'];if(CanonicalJson::encode($c)!==CanonicalJson::encode((new ZodiacPairResolver())->resolve($c['signA'],$c['signB']))){throw new \InvalidArgumentException('INVALID_DAILY_CONTEXT');}}
    }
}
