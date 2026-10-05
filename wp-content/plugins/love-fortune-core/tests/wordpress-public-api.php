<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
// Synthetic process-local test keys; no config/DB secret changes.
putenv('LOVE_FORTUNE_SIGNING_KID=fixture');
putenv('LOVE_FORTUNE_SIGNING_KEYS={"fixture":"SYNTHETIC_PUBLIC_KEY_NEVER_FOR_DEPLOYMENT_0001"}');
putenv('LOVE_FORTUNE_RATE_SECRET=SYNTHETIC_RATE_SECRET_NEVER_FOR_DEPLOYMENT_0001');
$_SERVER['REMOTE_ADDR']='192.0.2.'.random_int(1,254);
require '/var/www/html/wp-load.php';
use LoveFortune\Core\Api\{InterpretationContext,CompatibilityEndpoint,CanonicalJson,CompatibilityProjection,PublicError};
use LoveFortune\Core\Application\Score\{CombinedLifetimeService,LifetimeSourceEnvelope as E};
use LoveFortune\Core\Domain\Score\CategoryResult as C;
function demand(bool $ok,string $label):void{if(!$ok){throw new RuntimeException($label);}echo 'PASS: '.$label.PHP_EOL;}
$tables=$wpdb->get_col('SHOW TABLES');$options=$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A);
$routes=rest_get_server()->get_routes();
demand(isset($routes[CompatibilityEndpoint::ROUTE]),'Actual route registration');
$body=['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001'],'relationshipType'=>'UNKNOWN','targetTimezone'=>'Asia/Tokyo'];
function callApi(string $body,string $type='application/json'):WP_REST_Response{$r=new WP_REST_Request('POST',CompatibilityEndpoint::ROUTE);$r->set_header('content-type',$type);$r->set_body($body);return rest_get_server()->dispatch($r);}
$response=callApi(json_encode($body,JSON_THROW_ON_ERROR));demand($response->get_status()===200,'Real Natal/Saju/Zodiac/Combined REST E2E');
demand($response->get_headers()['Cache-Control']==='no-store','Success no-store');$data=$response->get_data();
$p=InterpretationContext::environment()->verify($data['signedInterpretationContext'],'ko-KR',time());demand($p['scoreVersion']==='SCORE_COMBINED_LIFETIME_V1','Generated token accepted by interpretation validator');
$second=callApi(json_encode($body,JSON_THROW_ON_ERROR))->get_data();unset($data['signedInterpretationContext'],$second['signedInterpretationContext'],$data['meta']['requestId'],$second['meta']['requestId']);
demand(CanonicalJson::encode($data)===CanonicalJson::encode($second),'Deterministic actual route');
foreach([['{',400,'application/json'],[str_repeat('x',32769),413,'application/json'],['{}',415,'text/plain'],['{}',422,'application/json']] as [$b,$status,$type]){$r=callApi($b,$type);demand($r->get_status()===$status,'Actual dispatch '.$status);demand($r->get_headers()['Cache-Control']==='no-store','Error no-store '.$status);}
$bad=$body;$bad['personA']['birthLocationId']='unknown';$r=callApi(json_encode($bad));demand($r->get_status()===404 && $r->get_headers()['Cache-Control']==='no-store','Actual reference 404 no-store');
putenv('LOVE_FORTUNE_SIGNING_KEYS=');$r=callApi(json_encode($body));demand($r->get_status()===503 && $r->get_headers()['Cache-Control']==='no-store','Actual missing signing configuration 503 no-store');
// Fault injection uses the actual dispatcher and production handler. The numeric E2E above is uninjected.
$unavailable=static function(array $input,string $id):array{
    $combined=(new CombinedLifetimeService())->score([
        E::fromEligible('SAJU',E::IDENTITIES['SAJU'],array_fill_keys(E::ELIGIBLE['SAJU'],C::unavailable())),
        E::fromEligible('ZODIAC',E::IDENTITIES['ZODIAC'],array_fill_keys(E::ELIGIBLE['ZODIAC'],C::unavailable())),
    ]);
    return (new CompatibilityProjection())->project($combined,[],$id);
};
foreach([
    [new CompatibilityEndpoint($unavailable,static fn()=>null),200,null],
    [new CompatibilityEndpoint(static fn()=>throw new RuntimeException('PRIVATE INTERNAL PATH'),static fn()=>null),500,'CALCULATION_FAILED'],
    [new CompatibilityEndpoint(null,static fn()=>throw new PublicError(429,'RATE_LIMITED',['Retry-After'=>'7'])),429,'RATE_LIMITED'],
] as [$endpoint,$status,$code]){
    $filter=static fn($result,$server,$request)=>$endpoint->dispatch($result,$server,$request);
    add_filter('rest_pre_dispatch',$filter,99,3);
    try{
        $r=callApi(json_encode($body));demand($r->get_status()===$status && $r->get_headers()['Cache-Control']==='no-store','Dispatcher controlled branch '.$status.' no-store');
        if($code===null){demand($r->get_data()['overallScore']===null && !isset($r->get_data()['signedInterpretationContext']),'Insufficient omits token');}
        else{demand($r->get_data()['error']['code']===$code && !str_contains(CanonicalJson::encode($r->get_data()),'PRIVATE'),'Controlled error is private');}
        if($status===429){demand($r->get_headers()['Retry-After']==='7','Dispatcher Retry-After');}
    }finally{remove_filter('rest_pre_dispatch',$filter,99);}
}
// Verify the real dispatcher-generated token, including rotation and semantic cutover.
$key='SYNTHETIC_PUBLIC_KEY_NEVER_FOR_DEPLOYMENT_0001';
$signer=new InterpretationContext('fixture',['fixture'=>$key,'previous'=>$key.'old'],'previous');
$now=strtotime($p['issuedAt']);$token=$response->get_data()['signedInterpretationContext'];
$resign=static function(array $payload,string $kid,string $secret):string{
    $b=static fn($v)=>rtrim(strtr(base64_encode(CanonicalJson::encode($v)),'+/','-_'),'=');
    $m=$b(['alg'=>'HS256','typ'=>'LFIC','kid'=>$kid,'v'=>1]).'.'.$b($payload);
    return $m.'.'.rtrim(strtr(base64_encode(hash_hmac('sha256',$m,$secret,true)),'+/','-_'),'=');
};
demand($signer->verify($resign($p,'previous',$key.'old'),'ko-KR',$now)===$p,'Previous key current semantics accepted');
$invalid=[[substr($token,0,-4).'aaaa','ko-KR',$now,'INVALID_INTERPRETATION_CONTEXT'],[$token,'ko-KR',$now+300,'INTERPRETATION_CONTEXT_EXPIRED'],[$token,'ja-JP',$now,'INTERPRETATION_LOCALE_MISMATCH']];
foreach(['scoreVersion'=>'SCORE_ZODIAC_V1','configVersion'=>'synthetic-config-v1'] as $field=>$value){
    $old=$p;$old[$field]=$value;$old['result']['meta']['versions'][$field]=$value;
    foreach(['fixture'=>$key,'previous'=>$key.'old'] as $kid=>$secret){$invalid[]=[$resign($old,$kid,$secret),'ko-KR',$now,'INVALID_INTERPRETATION_CONTEXT'];}
}
$wrong=$p;$wrong['purpose']='OTHER';$invalid[]=[$resign($wrong,'fixture',$key),'ko-KR',$now,'INVALID_INTERPRETATION_PURPOSE'];
foreach($invalid as [$t,$locale,$at,$code]){
    try{$signer->verify($t,$locale,$at);throw new RuntimeException('Invalid context accepted');}
    catch(PublicError $e){demand($e->status===422 && $e->publicCode===$code,'Dispatcher token rejection '.$code);}
}
demand($tables===$wpdb->get_col('SHOW TABLES'),'No tables created');demand($options===$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A),'No calculation options persisted');
echo 'PUBLIC_COMBINED_WORDPRESS_E2E_PASS'.PHP_EOL;
