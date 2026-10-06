<?php
declare(strict_types=1);
namespace LoveFortune\Core\Domain\Score;
use LoveFortune\Core\Support\Rational as R;

final class PeriodStatistics
{
    public static function mean(array $values): R
    {
        if($values===[]){throw new \InvalidArgumentException('EMPTY_MEAN');}
        $sum=R::of(0);foreach($values as $v){$sum=$sum->add($v);}return $sum->divide(R::of(count($values)));
    }
    public static function variance(array $values): ?R
    {
        if($values===[]){return null;}$mean=self::mean($values);$squares=[];
        foreach($values as $v){$d=$v->subtract($mean);$squares[]=$d->multiply($d);}return self::mean($squares);
    }
    /** Scores are bounded0..100: population standard deviation cannot exceed50. */
    public static function sqrtHalfUp4(R $variance): string
    {
        if($variance->compare(R::of(0))<0||$variance->compare(R::of(2500))>0){throw new \InvalidArgumentException('INVALID_PERIOD_VARIANCE');}
        $scaled=$variance->multiply(R::of(100000000));$lo=0;$hi=500001;
        while($hi-$lo>1){$m=intdiv($lo+$hi,2);if(R::of($m*$m)->compare($scaled)<=0){$lo=$m;}else{$hi=$m;}}
        if($scaled->multiply(R::of(4))->compare(R::of((2*$lo+1)*(2*$lo+1)))>=0){$lo++;}
        return intdiv($lo,10000).'.'.str_pad((string)($lo%10000),4,'0',STR_PAD_LEFT);
    }
    public static function slope(array $rows): ?R
    {
        if(count($rows)<2){return null;}$mx=self::mean(array_column($rows,'x'));$my=self::mean(array_column($rows,'score'));$n=$d=R::of(0);
        foreach($rows as $r){$dx=$r['x']->subtract($mx);$n=$n->add($dx->multiply($r['score']->subtract($my)));$d=$d->add($dx->multiply($dx));}
        return $n->divide($d);
    }
    public static function trend(?R $delta): array
    {
        if($delta===null){return ['INSUFFICIENT_DATA','UNKNOWN'];}$abs=$delta->abs();
        if($abs->compare(R::of(3))<0){return ['STABLE','STABLE'];}
        return [$abs->compare(R::of(8))<0?'NOTICEABLE':'SIGNIFICANT',$delta->compare(R::of(0))>0?'UP':'DOWN'];
    }
    public static function status(?R $score): string
    {
        if($score===null){return 'INSUFFICIENT_DATA';}$s=R::of($score->halfUp4());
        foreach([45=>'CAUTION',60=>'BALANCED',75=>'GOOD',85=>'VERY_GOOD'] as $limit=>$label){if($s->compare(R::of($limit))<0){return $label;}}return 'EXCELLENT';
    }
}
