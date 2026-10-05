<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__).'/autoload.php';
use LoveFortune\Core\Api\{DailyCalculation,DailyProjection,InterpretationContext,CanonicalJson,CompatibilityCalculation};
use LoveFortune\Core\Application\Score\{DailyOrchestrationService,CombinedLifetimeService,LifetimeSourceEnvelope};
use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Support\Rational as R;
$input=['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],
    'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001','birthTime'=>null],
    'relationshipType'=>'UNKNOWN','date'=>'2024-09-02','targetTimezone'=>'Asia/Tokyo','locale'=>'ko-KR'];
$r=(new DailyCalculation())->calculate($input,str_repeat('a',32));
$s=new InterpretationContext('daily-fixture',['daily-fixture'=>'SYNTHETIC_DAILY_KEY_NOT_FOR_PRODUCTION_0001']);
$token=$s->issue($r,'ko-KR',1800000000);$payload=$s->verify($token,'ko-KR',1800000000);$external=$r+['signedInterpretationContext'=>$token];
$base=(new CompatibilityCalculation())->internal($input)['combined'];
$slots=array_fill(0,4,array_fill_keys(array_keys(DailyOrchestrationService::WEIGHTS),null));
$fallback=DailyProjection::project((new DailyOrchestrationService())->aggregate($base,$slots,$input['date'],$input['targetTimezone']),str_repeat('b',32));
$base['overall']=CategoryResult::unavailable();$null=DailyProjection::project((new DailyOrchestrationService())->aggregate($base,$slots,$input['date'],$input['targetTimezone']),str_repeat('c',32));
echo CanonicalJson::encode(['request'=>$input,'response'=>$external,'unsigned'=>$r,'payload'=>$payload,'fallback'=>$fallback,'null'=>$null]);
