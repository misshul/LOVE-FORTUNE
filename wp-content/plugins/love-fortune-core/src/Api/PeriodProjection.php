<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Support\Rational as R;
use LoveFortune\Core\Domain\Score\PeriodStatistics;
final class PeriodProjection
{
    public static function project(array $p,string $id): array
    {
        $meta=['requestId'=>$id,'versions'=>PeriodRelease::versions()];
        if(isset($p['days'])){return ['meta'=>$meta,'startDate'=>$p['startDate'],'endDate'=>$p['endDate'],'targetTimezone'=>$p['targetTimezone'],'days'=>array_map(static fn($d)=>DailyProjection::project($d,$id),$p['days'])];}
        $wire=static fn(?R $v)=>$v===null?null:(float)$v->halfUp4();$kind=$p['periodType'];$date=$p['membership'][0];
        $period=['type'=>$kind,'timezone'=>$p['targetTimezone']];
        if($kind==='WEEK'){$period+=['startDate'=>$date,'endDate'=>$p['membership'][6]];}else{$period['year']=(int)substr($date,0,4);if($kind==='MONTH'){$period['month']=(int)substr($date,5,2);}}
        $prefix=match($kind){'WEEK'=>'weekly','MONTH'=>'monthly','YEAR'=>'yearly'};
        $rank=static fn($rows)=>array_map(static fn($r)=>['date'=>$r['date'],'score'=>$wire($r['score']),'resultConfidence'=>$wire($r['confidence'])],$rows);
        $r=['meta'=>$meta,'period'=>$period,'targetTimezone'=>$p['targetTimezone'],$prefix.'Score'=>$wire($p['score']),
            'periodDelta'=>$wire($p['periodDelta']),'coverage'=>$wire($p['coverage']),'resultConfidence'=>$wire($p['resultConfidence']),
            'status'=>$p['status'],'trendStatus'=>$p['trendStatus'],'trendDirection'=>$p['trendDirection'],
            $kind==='YEAR'?'yearlyVolatility':'volatility'=>$p['variance']===null?null:(float)PeriodStatistics::sqrtHalfUp4($p['variance']),
            $kind==='YEAR'?'bestMonths':'bestDays'=>$rank($p['rankings']['best']),$kind==='YEAR'?'cautionMonths':'cautionDays'=>$rank($p['rankings']['caution']),
            'features'=>[],'warnings'=>[]];
        if($kind==='WEEK'){$r['slope']=$wire($p['slope']);}
        if(isset($p['contexts']['ZODIAC']['context'])){$r['zodiacContext']=$p['contexts']['ZODIAC']['context'];}return $r;
    }
}
