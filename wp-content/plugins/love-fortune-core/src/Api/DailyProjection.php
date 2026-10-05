<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Support\Rational;
final class DailyProjection
{
    public static function project(array $daily,string $id): array
    {
        $wire=static fn(?Rational $r):?float=>$r===null?null:(float)$r->halfUp4();$o=$daily['overall'];$categories=[];
        foreach($daily['categories'] as $c=>$row){$categories[$c]=$wire($row['score']);}
        $r=['meta'=>['requestId'=>$id,'versions'=>DailyRelease::versions()],'date'=>$daily['date'],'targetTimezone'=>$daily['targetTimezone'],
            'lifetimeScore'=>$wire($o['baselineScore']),'dailyScore'=>$wire($o['score']),'dailyDelta'=>$wire($o['delta']),
            'dailyStatus'=>$o['dailyStatus'],'coverage'=>$wire($o['coverage']),'resultConfidence'=>$wire($o['resultConfidence']),
            'features'=>[],'warnings'=>[],'categoryScores'=>$categories];
        if(isset($daily['contexts']['ZODIAC']['context'])){$r['zodiacContext']=$daily['contexts']['ZODIAC']['context'];}
        return $r;
    }
}
