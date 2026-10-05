<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
// Synthetic secrets are process-local, never injected into the production bootstrap or DB.
putenv('LOVE_FORTUNE_SIGNING_KID=daily-fixture');
putenv('LOVE_FORTUNE_SIGNING_KEYS={"daily-fixture":"SYNTHETIC_DAILY_KEY_NOT_FOR_PRODUCTION_0001"}');
putenv('LOVE_FORTUNE_RATE_SECRET=SYNTHETIC_DAILY_RATE_NOT_FOR_PRODUCTION_0001');
$_SERVER['REMOTE_ADDR']='192.0.2.'.random_int(1,254);
require '/var/www/html/wp-load.php';
use LoveFortune\Core\Api\{DailyEndpoint,DailyProjection,CompatibilityCalculation,InterpretationContext,CanonicalJson,PublicError};
use LoveFortune\Core\Application\Score\DailyOrchestrationService;
use LoveFortune\Core\Domain\Score\CategoryResult;
function checkDaily(bool $ok,string $label):void{if(!$ok){throw new RuntimeException($label);}echo 'PASS: '.$label.PHP_EOL;}
$tables=$wpdb->get_col('SHOW TABLES');$options=$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A);
$routes=rest_get_server()->get_routes();checkDaily(isset($routes[DailyEndpoint::ROUTE]),'Daily registered');
foreach(['daily-range','weekly','monthly','yearly'] as $p){checkDaily(!isset($routes['/love-fortune/v1/fortune/'.$p]),$p.' remains unimplemented');}
$body=['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001'],'relationshipType'=>'UNKNOWN','date'=>'2024-09-02','targetTimezone'=>'Japan'];
function callDaily(string $b,string $type='application/json',?string $encoding=null):WP_REST_Response{$q=new WP_REST_Request('POST',DailyEndpoint::ROUTE);$q->set_header('content-type',$type);if($encoding!==null){$q->set_header('content-encoding',$encoding);}$q->set_body($b);return rest_get_server()->dispatch($q);}
function expectDaily(WP_REST_Response $r,int $status):void{checkDaily($r->get_status()===$status,'Dispatcher '.$status);checkDaily($r->get_headers()['Cache-Control']==='no-store','No-store '.$status);}
$r=callDaily(json_encode($body));expectDaily($r,200);$data=$r->get_data();checkDaily($data['targetTimezone']==='Asia/Tokyo','Frozen alias canonicalization');
$token=$data['signedInterpretationContext'];$signer=InterpretationContext::environment();$p=$signer->verify($token,'ko-KR',time());checkDaily($p['configVersion']==='CONFIG_DAILY_V1'&&$p['evidence']===[]&&$data['features']===[],'Real Daily pipeline token and empty evidence');
$second=callDaily(json_encode($body))->get_data();unset($data['signedInterpretationContext'],$second['signedInterpretationContext'],$data['meta']['requestId'],$second['meta']['requestId']);checkDaily(CanonicalJson::encode($data)===CanonicalJson::encode($second),'Deterministic real Daily dispatcher');
foreach(['birthDate','birthTime','birthLocationId','sampleRef','candidateId','lineage','tenGods'] as $key){checkDaily(!str_contains(CanonicalJson::encode($p),'"'.$key.'"'),'No private field '.$key);}
$cases=[['{',400,'application/json'],[str_repeat('x',32769),413,'application/json'],['{}',415,'text/plain'],['{}',422,'application/json']];
foreach([['date','2100-01-01',422],['date','2024-02-30',422],['targetTimezone','Europe/Paris',422]] as [$k,$v,$status]){$bad=$body;$bad[$k]=$v;$cases[]=[json_encode($bad),$status,'application/json'];}
$bad=$body;$bad['personA']['birthLocationId']='unknown';$cases[]=[json_encode($bad),404,'application/json'];
foreach($cases as [$b,$status,$type]){expectDaily(callDaily($b,$type),$status);}expectDaily(callDaily(json_encode($body),'application/json','identity'),415);
// Controlled fallback/invariant/rate branches through actual dispatcher; numeric path above is real.
$input=$body;$input['personB']['birthTime']=null;$base=(new CompatibilityCalculation())->internal($input)['combined'];
$slots=array_fill(0,4,array_fill_keys(array_keys(DailyOrchestrationService::WEIGHTS),null));
$fallback=(new DailyOrchestrationService())->aggregate($base,$slots,$body['date'],'Asia/Tokyo');$base['overall']=CategoryResult::unavailable();$null=(new DailyOrchestrationService())->aggregate($base,$slots,$body['date'],'Asia/Tokyo');
foreach([[new DailyEndpoint(static fn($i,$id)=>DailyProjection::project($fallback,$id),static fn()=>null),200],
    [new DailyEndpoint(static fn($i,$id)=>DailyProjection::project($null,$id),static fn()=>null),200],
    [new DailyEndpoint(static fn()=>throw new RuntimeException('PRIVATE'),static fn()=>null),500],
    [new DailyEndpoint(null,static fn()=>throw new PublicError(429,'RATE_LIMITED',['Retry-After'=>'7'])),429]] as [$endpoint,$status]){
    $filter=static fn($r,$server,$req)=>$endpoint->dispatch($r,$server,$req);add_filter('rest_pre_dispatch',$filter,99,3);
    try{$r=callDaily(json_encode($body));expectDaily($r,$status);if($status===200){checkDaily(!isset($r->get_data()['signedInterpretationContext']),'Fallback/null token omitted');}if($status===429){checkDaily($r->get_headers()['Retry-After']==='7','Retry-After');}checkDaily(!str_contains(CanonicalJson::encode($r->get_data()),'PRIVATE'),'No internal exception detail');}finally{remove_filter('rest_pre_dispatch',$filter,99);}
}
$encode=static fn($v)=>rtrim(strtr(base64_encode(CanonicalJson::encode($v)),'+/','-_'),'=');$bad=$p;$bad['configVersion']='CONFIG_COMBINED_LIFETIME_V1';$bad['result']['meta']['versions']['configVersion']='CONFIG_COMBINED_LIFETIME_V1';
$message=$encode(['alg'=>'HS256','typ'=>'LFIC','kid'=>'daily-fixture','v'=>1]).'.'.$encode($bad);$cross=$message.'.'.rtrim(strtr(base64_encode(hash_hmac('sha256',$message,'SYNTHETIC_DAILY_KEY_NOT_FOR_PRODUCTION_0001',true)),'+/','-_'),'=');
foreach([[$token,strtotime($p['expiresAt']),'INTERPRETATION_CONTEXT_EXPIRED'],[substr($token,0,-4).'aaaa',time(),'INVALID_INTERPRETATION_CONTEXT'],[$cross,time(),'INVALID_INTERPRETATION_CONTEXT']] as [$t,$now,$code]){try{$signer->verify($t,'ko-KR',$now);throw new RuntimeException('Invalid Daily token accepted');}catch(PublicError $e){checkDaily($e->publicCode===$code,'Token rejects '.$code);}}
putenv('LOVE_FORTUNE_SIGNING_KEYS=');expectDaily(callDaily(json_encode($body)),503);
checkDaily($tables===$wpdb->get_col('SHOW TABLES'),'No table changes');checkDaily($options===$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A),'No option changes');
echo 'DAILY_WORDPRESS_E2E_PASS'.PHP_EOL;
