<?php
declare(strict_types=1);
require __DIR__.'/rate-environment.php';
if(PHP_SAPI!=='cli'){exit;}
putenv('LOVE_FORTUNE_SIGNING_KID=period-fixture');putenv('LOVE_FORTUNE_SIGNING_KEYS={"period-fixture":"SYNTHETIC_PERIOD_KEY_NOT_FOR_PRODUCTION_0001"}');putenv('LOVE_FORTUNE_RATE_SECRET=SYNTHETIC_PERIOD_RATE_NOT_FOR_PRODUCTION_0001');$_SERVER['REMOTE_ADDR']='198.51.100.'.random_int(1,254);
require '/var/www/html/wp-load.php';
use LoveFortune\Core\Api\{DailyRangeEndpoint,WeeklyEndpoint,MonthlyEndpoint,YearlyEndpoint,PeriodProjection,InterpretationContext,CanonicalJson,PublicError};
use LoveFortune\Core\Application\Score\{PeriodCalendar as C,PeriodOrchestrationService as P};
use LoveFortune\Core\Support\Rational as R;
function periodCheck(bool $ok,string $label):void{if(!$ok){throw new RuntimeException($label);} }
function periodCall(string $route,array|string $body,int $status=200,string $media='application/json',?string $encoding=null):array{
    $q=new WP_REST_Request('POST','/love-fortune/v1/fortune/'.$route);$q->set_header('content-type',$media);if($encoding!==null){$q->set_header('content-encoding',$encoding);}$q->set_body(is_array($body)?json_encode($body):$body);$cpu=getrusage();$start=hrtime(true);memory_reset_peak_usage();$r=rest_get_server()->dispatch($q);$elapsed=(hrtime(true)-$start)/1e9;
    periodCheck($r->get_status()===$status,$route.' expected '.$status.' got '.CanonicalJson::encode($r->get_data()));periodCheck(($r->get_headers()['Cache-Control']??null)==='no-store','no-store');
    $data=$r->get_data();return [$data,['seconds'=>$elapsed,'cpuSeconds'=>(getrusage()['ru_utime.tv_sec']-$cpu['ru_utime.tv_sec'])+(getrusage()['ru_utime.tv_usec']-$cpu['ru_utime.tv_usec'])/1e6,'peakBytes'=>memory_get_peak_usage(true),'responseBytes'=>strlen(CanonicalJson::encode($data)),'tokenBytes'=>strlen($data['signedInterpretationContext']??'')],$r];
}
$tables=$wpdb->get_col('SHOW TABLES');$options=$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A);
$base=['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001'],'relationshipType'=>'UNKNOWN','targetTimezone'=>'Asia/Tokyo'];$metrics=[];$examples=[];
$requests=['range1'=>['daily-range',['startDate'=>'2024-09-02','endDate'=>'2024-09-02']], 'range31'=>['daily-range',['startDate'=>'2024-01-01','endDate'=>'2024-01-31']], 'dst'=>['daily-range',['startDate'=>'2024-03-09','endDate'=>'2024-03-11','targetTimezone'=>'America/New_York']], 'weekly'=>['weekly',['weekStartDate'=>'2024-12-30']], 'month28'=>['monthly',['year'=>2023,'month'=>2]], 'month29'=>['monthly',['year'=>2024,'month'=>2]], 'month30'=>['monthly',['year'=>2024,'month'=>4]], 'month31'=>['monthly',['year'=>2024,'month'=>1]], 'year365'=>['yearly',['year'=>2023]], 'year366'=>['yearly',['year'=>2024]]];
foreach($requests as $label=>[$route,$extra]){fwrite(STDERR,'Period E2E '.$label.PHP_EOL);[$data,$metric]=periodCall($route,array_replace($base,$extra));$metrics[$label]=$metric;$examples[$label]=$data;periodCheck($data['meta']['versions']['configVersion']==='CONFIG_PERIOD_V1','period version');
    if($route==='daily-range'){periodCheck(!isset($data['signedInterpretationContext']),'range unsigned');foreach($data['days'] as $d){periodCheck(!isset($d['signedInterpretationContext'])&&$d['features']===[],'nested unsigned');}
        foreach(array_unique([0,count($data['days'])-1]) as $i){$d=$data['days'][$i];[$single]=periodCall('daily',array_replace($base,['date'=>$d['date'],'targetTimezone'=>$data['targetTimezone']]));unset($single['signedInterpretationContext'],$single['meta']['requestId'],$d['meta']['requestId']);periodCheck(CanonicalJson::encode($single)===CanonicalJson::encode($d),'standalone Daily equality');}
    }else{$payload=InterpretationContext::environment()->verify($data['signedInterpretationContext'],'ko-KR',time());periodCheck($payload['configVersion']==='CONFIG_PERIOD_V1'&&$payload['evidence']===[],'period signature');foreach(['birthDate','birthTime','birthLocationId','candidateId','sampleRef','lineage'] as $field){periodCheck(!str_contains(CanonicalJson::encode($payload),'"'.$field.'"'),'private field');}}
}
foreach([['daily-range',['startDate'=>'2024-01-01','endDate'=>'2024-02-01'],'DATE_RANGE_TOO_LARGE'],['daily-range',['startDate'=>'2024-01-02','endDate'=>'2024-01-01'],'INVALID_DATE_RANGE'],['daily-range',['startDate'=>'2099-12-31','endDate'=>'2100-01-01'],'UNSUPPORTED_DATE'],['weekly',['weekStartDate'=>'2024-09-03'],'INVALID_REQUEST_SEMANTICS']] as [$route,$extra,$error]){[$data]=periodCall($route,$base+$extra,422);periodCheck(str_contains(CanonicalJson::encode($data),$error),$error);}
foreach(['daily-range','weekly','monthly','yearly'] as $route){periodCall($route,'{',400);periodCall($route,str_repeat('x',32769),413);periodCall($route,'{}',415,'text/plain');periodCall($route,'{}',415,'application/json','identity');}
// Synthetic partial/empty/fault branches pass through the real dispatcher; the numeric cases above use the complete production pipeline.
foreach([['WEEK','weekly',WeeklyEndpoint::class,['weekStartDate'=>'2024-09-02']],['MONTH','monthly',MonthlyEndpoint::class,['year'=>2024,'month'=>2]],['YEAR','yearly',YearlyEndpoint::class,['year'=>2024]]] as [$type,$route,$class,$extra]){
    $dates=C::membership($type,$extra);foreach(['partial','empty','error','rate'] as $mode){$rows=[];foreach($dates as $i=>$d){$rows[$d]=['score'=>$mode==='empty'||$i>0?null:R::of(69),'coverage'=>R::of('0.5'),'resultConfidence'=>R::of('0.8'),'dailyStatus'=>'STABLE'];}$internal=(new P())->aggregate($type,$dates,$rows,R::of(60),'Asia/Tokyo');
        $endpoint=new $class($mode==='error'?static fn()=>throw new RuntimeException('PRIVATE'):static fn($in,$id)=>PeriodProjection::project($internal,$id),$mode==='rate'?static fn()=>throw new PublicError(429,'RATE_LIMITED',['Retry-After'=>'7']):static fn()=>null);
        $filter=static fn($result,$server,$request)=>$endpoint->dispatch($result,$server,$request);add_filter('rest_pre_dispatch',$filter,99,3);
        try{[$r,,$http]=periodCall($route,$base+$extra,$mode==='error'?500:($mode==='rate'?429:200));if(in_array($mode,['partial','empty'],true)){periodCheck(isset($r['signedInterpretationContext'])===($mode==='partial'),'partial/null signing');}if($mode==='rate'){periodCheck($http->get_headers()['Retry-After']==='7','Retry-After');}periodCheck(!str_contains(CanonicalJson::encode($r),'PRIVATE'),'safe error');}finally{remove_filter('rest_pre_dispatch',$filter,99);}
    }
    // Signing unavailable tested against a computed numeric result to avoid duplicate annual work.
    $sample=$examples[$type==='WEEK'?'weekly':($type==='MONTH'?'month29':'year366')];unset($sample['signedInterpretationContext']);$endpoint=new $class(static function($in,$id)use($sample){$sample['meta']['requestId']=$id;return $sample;},static fn()=>null,new InterpretationContext('',[]));$filter=static fn($result,$server,$request)=>$endpoint->dispatch($result,$server,$request);add_filter('rest_pre_dispatch',$filter,99,3);try{periodCall($route,$base+$extra,503);}finally{remove_filter('rest_pre_dispatch',$filter,99);}
}
// Range preserves the existing Daily null/fallback meanings, without any token.
$normalized=$base;$normalized['personB']['birthTime']=null;
$combined=(new \LoveFortune\Core\Api\CompatibilityCalculation())->internal($normalized)['combined'];
$slots=array_fill(0,4,array_fill_keys(array_keys(\LoveFortune\Core\Application\Score\DailyOrchestrationService::WEIGHTS),null));
foreach(['fallback','null'] as $mode){if($mode==='null'){$combined['overall']=\LoveFortune\Core\Domain\Score\CategoryResult::unavailable();}
    $daily=(new \LoveFortune\Core\Application\Score\DailyOrchestrationService())->aggregate($combined,$slots,'2024-09-02','Asia/Tokyo');
    $internal=['days'=>[$daily],'startDate'=>'2024-09-02','endDate'=>'2024-09-02','targetTimezone'=>'Asia/Tokyo'];
    $endpoint=new DailyRangeEndpoint(static fn($in,$id)=>PeriodProjection::project($internal,$id),static fn()=>null,new InterpretationContext('',[]));
    $filter=static fn($result,$server,$request)=>$endpoint->dispatch($result,$server,$request);add_filter('rest_pre_dispatch',$filter,99,3);
    try{[$r]=periodCall('daily-range',$base+['startDate'=>'2024-09-02','endDate'=>'2024-09-02']);periodCheck(!isset($r['signedInterpretationContext'],$r['days'][0]['signedInterpretationContext']),'unsigned fallback range');periodCheck($r['days'][0]['dailyStatus']===($mode==='null'?'INSUFFICIENT_DATA':'INSUFFICIENT_PERIOD_DATA'),'range fallback status');}finally{remove_filter('rest_pre_dispatch',$filter,99);}
}
putenv('LOVE_FORTUNE_SIGNING_KEYS=');periodCall('daily-range',$base+['startDate'=>'2024-09-02','endDate'=>'2024-09-02']);
periodCheck($tables===$wpdb->get_col('SHOW TABLES'),'no tables');periodCheck($options===$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'love_fortune_%' ORDER BY option_name",ARRAY_A),'no options');
echo CanonicalJson::encode(['result'=>'PERIOD_WORDPRESS_E2E_PASS','metrics'=>$metrics,'examples'=>$examples,'limits'=>['Partial/empty/fault paths use explicit dispatcher injection; real numeric paths use production calculation.','CLI dispatcher timings are not remote HTTP latency.']]);
