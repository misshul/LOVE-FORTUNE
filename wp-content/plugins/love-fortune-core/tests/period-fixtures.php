<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__).'/autoload.php';
use LoveFortune\Core\Application\Score\{PeriodCalendar as C,PeriodOrchestrationService as P};
use LoveFortune\Core\Api\{PeriodProjection,PeriodResultValidation,InterpretationContext,CanonicalJson};
use LoveFortune\Core\Domain\Score\PeriodStatistics as S;
use LoveFortune\Core\Support\Rational as R;
$s=new InterpretationContext('period-fixture',['period-fixture'=>'SYNTHETIC_PERIOD_KEY_NOT_FOR_PRODUCTION_0001']);$out=[];
foreach(['WEEK','MONTH','YEAR'] as $type){$dates=C::membership($type,$type==='WEEK'?['weekStartDate'=>'2024-09-02']:['year'=>2024,'month'=>2]);
    foreach(['numeric','partial','empty','trend3','trend8'] as $mode){$rows=[];foreach($dates as $i=>$date){$score=$mode==='empty'||($mode==='partial'&&$i>0)?null:R::of($mode==='trend3'?'52.99996':($mode==='trend8'?'57.99996':'69'));$rows[$date]=['score'=>$score,'coverage'=>R::of('0.5'),'resultConfidence'=>R::of('0.8'),'dailyStatus'=>'STABLE'];}
        $r=PeriodProjection::project((new P())->aggregate($type,$dates,$rows,R::of(50),'Asia/Tokyo'),str_repeat('a',32));PeriodResultValidation::validate($r);$entry=['unsigned'=>$r];
        if(PeriodResultValidation::needsSignature($r)){$t=$s->issue($r,'ko-KR',1800000000);$entry['payload']=$s->verify($t,'ko-KR',1800000000);$r['signedInterpretationContext']=$t;}$entry['external']=$r;$out[$type.'_'.$mode]=$entry;
    }
}
$sqrt=[];foreach(['0','2','2/3','2500','1/400000000','1/400000001','152399025/100000000'] as $v){$sqrt[$v]=S::sqrtHalfUp4(R::of($v));}
echo CanonicalJson::encode(['periods'=>$out,'sqrt'=>$sqrt]);
