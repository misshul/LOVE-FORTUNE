<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__).'/autoload.php';
use LoveFortune\Core\Api\{CompatibilityCalculation,InterpretationContext,CanonicalJson};
$input=['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],
    'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001','birthTime'=>null],
    'relationshipType'=>'UNKNOWN','targetTimezone'=>'Asia/Tokyo','locale'=>'ko-KR'];
$r=(new CompatibilityCalculation())->calculate($input,str_repeat('a',32));
$signer=new InterpretationContext('fixture',['fixture'=>'SYNTHETIC_PUBLIC_KEY_NEVER_FOR_DEPLOYMENT_0001']);
$token=$signer->issue($r,'ko-KR',1800000000);$payload=$signer->verify($token,'ko-KR',1800000001);
$r['signedInterpretationContext']=$token;
$known=$input;$known['personB']['birthTime']='12:00';
$single=(new CompatibilityCalculation())->calculate($known,str_repeat('b',32));
echo CanonicalJson::encode(['request'=>$input,'response'=>$r,'payload'=>$payload,'singleResult'=>$single]);
