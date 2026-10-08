<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
require __DIR__.'/rate-environment.php';
putenv('LOVE_FORTUNE_SIGNING_KID=local-fixture');putenv('LOVE_FORTUNE_SIGNING_KEYS='.json_encode(['local-fixture'=>str_repeat('synthetic',8)]));
putenv('LOVE_FORTUNE_RATE_SECRET='.str_repeat('synthetic',8));putenv('LOVE_FORTUNE_AI_ENABLED=0');$_SERVER['REMOTE_ADDR']='192.0.2.90';
require '/var/www/html/wp-load.php';
use LoveFortune\Core\Api\CanonicalJson;
function localCall(string $path,array $input): array
{
    $q=new WP_REST_Request('POST','/love-fortune/v1/'.$path);$q->set_header('content-type','application/json');$q->set_body(json_encode($input,JSON_THROW_ON_ERROR));
    memory_reset_peak_usage();$t=hrtime(true);$r=rest_get_server()->dispatch($q);$elapsed=(hrtime(true)-$t)/1e9;
    if($r->get_status()!==200||($r->get_headers()['Cache-Control']??null)!=='no-store'){throw new RuntimeException('LOCAL_DISPATCH_FAILED');}
    $data=$r->get_data();$bytes=strlen(CanonicalJson::encode($data));unset($data['meta']['requestId'],$data['signedInterpretationContext']);
    return ['hash'=>hash('sha256',CanonicalJson::encode($data)),'seconds'=>$elapsed,'peakBytes'=>memory_get_peak_usage(true),'responseBytes'=>$bytes];
}
$base=['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001'],'relationshipType'=>'UNKNOWN','targetTimezone'=>'Asia/Tokyo'];
$mode=$argv[1]??'cache';
if($mode==='cache'){
    $a=localCall('compatibility/calculate',$base);$other=$base;$other['personA']['birthDate']='1990-07-15';$b=localCall('compatibility/calculate',$other);$a2=localCall('compatibility/calculate',$base);
    if($a['hash']!==$a2['hash']||$a['hash']===$b['hash']){throw new RuntimeException('CACHE_REGRESSION');}
    echo json_encode(['result'=>'LOCAL_CACHE_PASS','differentInputsDistinct'=>true,'repeatExact'=>true,'noStore'=>true],JSON_THROW_ON_ERROR);
}else{echo json_encode(['result'=>'YEARLY_PASS','metrics'=>localCall('fortune/yearly',$base+['year'=>$mode==='365'?2023:2024])],JSON_THROW_ON_ERROR);}
