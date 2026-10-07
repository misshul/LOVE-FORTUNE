<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
putenv('LOVE_FORTUNE_SIGNING_KID=interpretation-fixture');putenv('LOVE_FORTUNE_SIGNING_KEYS={"interpretation-fixture":"SYNTHETIC_INTERPRETATION_KEY_NOT_FOR_DEPLOYMENT"}');
// Each execution has an isolated rate key. Never print it or request content.
putenv('LOVE_FORTUNE_RATE_SECRET='.bin2hex(random_bytes(32)));$_SERVER['REMOTE_ADDR']='198.51.100.42';putenv('LOVE_FORTUNE_AI_ENABLED=0');
require '/var/www/html/wp-load.php';require __DIR__.'/interpretation-support.php';
use LoveFortune\Core\Api\{InterpretationEndpoint,InterpretationContext,CanonicalJson};
use LoveFortune\Core\Application\Interpretation\{InterpretationService,InterpretationText as T};
function irCheck(bool $ok,string $label): void {if(!$ok){throw new RuntimeException($label);}}
function irCall(array|string $body,int $status=200,string $media='application/json',?string $encoding=null): WP_REST_Response
{
    $q=new WP_REST_Request('POST',InterpretationEndpoint::ROUTE);$q->set_body(is_array($body)?json_encode($body):$body);$q->set_header('content-type',$media);if($encoding!==null){$q->set_header('content-encoding',$encoding);}
    $r=rest_get_server()->dispatch($q);irCheck($r instanceof WP_REST_Response,'response');irCheck($r->get_status()===$status,'expected status '.$status.' got '.$r->get_status());irCheck(($r->get_headers()['Cache-Control']??null)==='no-store','no-store');return $r;
}
$tables=$wpdb->get_col('SHOW TABLES');$options=$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A);
irCheck(isset(rest_get_server()->get_routes()[InterpretationEndpoint::ROUTE]),'registered');
$registered=rest_get_server()->get_routes()[InterpretationEndpoint::ROUTE];
irCheck(count(array_filter($registered,static fn($entry)=>is_array($entry)&&isset($entry['callback'])))===1,'single route handler');
$signer=InterpretationContext::environment();$tokens=[];$responses=[];
foreach(interpretationResults() as $type=>$result){$tokens[$type]=$signer->issue($result,'ko-KR',time());$r=irCall(['signedContext'=>$tokens[$type]]);irCheck($r->get_data()['meta']['interpretationMode']==='FALLBACK','default fallback');irCheck($r->get_data()['meta']['provider']===null&&$r->get_data()['meta']['model']===null,'fallback identity');$responses[$type.'_fallback']=$r->get_data();}
// Exercise real configured adapter via WordPress HTTP interception: no live vendor calls.
putenv('LOVE_FORTUNE_AI_ENABLED=1');putenv('LOVE_FORTUNE_AI_ENDPOINT=https://interpretation-gateway.invalid/generate');putenv('LOVE_FORTUNE_AI_KEY=SYNTHETIC_GATEWAY_SECRET');putenv('LOVE_FORTUNE_AI_PROVIDER=SYNTHETIC_PROVIDER');putenv('LOVE_FORTUNE_AI_MODEL=SYNTHETIC_MODEL');
$calls=0;$mode='success';$captured=[];
$http=static function($pre,$args,$url)use(&$calls,&$mode,&$captured){
    if($url!=='https://interpretation-gateway.invalid/generate'){return $pre;}$calls++;$body=json_decode($args['body'],true);$captured[]=$body;
    irCheck($args['timeout']<=6&&$args['timeout']>0&&$args['redirection']===0&&$args['sslverify']===true,'adapter budgets');
    irCheck(array_keys($body)===['model','system','input','validationErrors'],'gateway envelope');
    foreach(['signedContext','requestId','issuedAt','expiresAt','kid','signature','birthDate','birthTime','birthLocationId','candidateId','sampleRef','dependencies','name','nickname'] as $key){irCheck(!str_contains(CanonicalJson::encode($body['input']),'"'.$key.'"'),'provider privacy '.$key);}
    irCheck(!str_contains($body['system'],'SYNTHETIC_GATEWAY_SECRET'),'system secret');
    $content=T::fallback($body['input']);if($mode==='numeric'){$content['advice']='compatibility score is 100';}
    return ['headers'=>[],'response'=>['code'=>$mode==='429'?429:($mode==='5xx'?503:200),'message'=>'fixture'],'body'=>$mode==='json'?'{':CanonicalJson::encode($content),'cookies'=>[]];
};add_filter('pre_http_request',$http,10,3);
foreach($tokens as $type=>$token){$r=irCall(['signedContext'=>$token]);irCheck($r->get_data()['meta']['interpretationMode']==='AI','configured AI '.$type);$responses[$type.'_ai']=$r->get_data();}
$r=irCall(['signedContext'=>$tokens['Daily']],429);irCheck(ctype_digit($r->get_headers()['Retry-After']),'rate retry');irCheck($calls===5,'ten allowed then rate');
// Remaining fault tests use the registered route with only rate dependency bypassed.
$endpoint=new InterpretationEndpoint(rate:static fn()=>null);$dispatch=static fn($result,$server,$request)=>$endpoint->dispatch($result,$server,$request);add_filter('rest_pre_dispatch',$dispatch,99,3);
foreach(['429','5xx','json','numeric'] as $failure){$mode=$failure;$before=$calls;$r=irCall(['signedContext'=>$tokens['Daily']]);irCheck($r->get_data()['meta']['interpretationMode']==='FALLBACK','provider fallback');irCheck($calls-$before===($failure==='429'?1:2),'bounded attempts');}
$mode='success';$before=$calls;
$parts=explode('.',$tokens['Daily']);$parts[2]=str_repeat('A',43);irCall(['signedContext'=>implode('.',$parts)],422);
$expired=$signer->issue(interpretationResults()['Daily'],'ko-KR',time()-300);irCall(['signedContext'=>$expired],422);
irCall(['signedContext'=>$tokens['Daily'],'locale'=>'ja-JP'],422);
foreach([
    ['purpose','OTHER','INVALID_INTERPRETATION_PURPOSE'],
    ['configVersion','CONFIG_PERIOD_V1','INVALID_INTERPRETATION_CONTEXT'],
    ['scoreVersion','OLD','INVALID_INTERPRETATION_CONTEXT'],
] as [$field,$value,$error]){
    $bad=interpretationResign($tokens['Daily'],static function($p)use($field,$value){$p[$field]=$value;return $p;},'SYNTHETIC_INTERPRETATION_KEY_NOT_FOR_DEPLOYMENT');
    $r=irCall(['signedContext'=>$bad],422);irCheck($r->get_data()['error']['code']===$error,'signed rejection '.$field);
}
irCheck($calls===$before,'no provider on invalid context');
$body=json_encode(['signedContext'=>$tokens['Daily']]);irCall(str_pad($body,65536,' '));irCall(str_pad($body,65537,' '),413);
$before=$calls;
foreach([65499,65500] as $length){
    // Unit tests separately prove the length gate; neither synthetic string has a valid MAC.
    $token=str_repeat('a',$length-46).'.b.'.str_repeat('c',43);
    $r=irCall(['signedContext'=>$token],422);irCheck($r->get_data()['error']['code']==='INVALID_INTERPRETATION_CONTEXT','token boundary rejection');
}
irCheck($calls===$before,'no provider on boundary token');
irCall('{',400);irCall($body,415,'text/plain');irCall($body,415,'application/json','gzip');irCall(['signedContext'=>$tokens['Daily'],'question'=>'bad'],400);
remove_filter('rest_pre_dispatch',$dispatch,99);
$unavailable=new InterpretationEndpoint(new InterpretationService(fallback:static fn()=>null),static fn()=>null);$dispatch=static fn($result,$server,$request)=>$unavailable->dispatch($result,$server,$request);add_filter('rest_pre_dispatch',$dispatch,99,3);irCall(['signedContext'=>$tokens['Daily']],503);remove_filter('rest_pre_dispatch',$dispatch,99);
remove_filter('pre_http_request',$http,10);
irCheck($tables===$wpdb->get_col('SHOW TABLES'),'no tables');irCheck($options===$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A),'no options');
echo CanonicalJson::encode(['result'=>'INTERPRETATION_WORDPRESS_E2E_PASS','signedTypes'=>5,'responses'=>$responses,'providerCalls'=>$calls,'liveProvider'=>'NOT_CONFIGURED_TEST_HTTP_INTERCEPTED','privacy'=>'PASS','noStore'=>'PASS','rate'=>'10/600 PASS','limits'=>['Period inputs use production aggregate/projection over synthetic rows; full period birth-input pipeline is covered by retained period E2E.','Configured gateway HTTP is intercepted before transport; no real vendor was contacted.']]);
