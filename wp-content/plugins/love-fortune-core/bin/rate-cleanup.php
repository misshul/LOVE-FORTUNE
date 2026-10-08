<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/autoload.php';
use LoveFortune\Core\Infrastructure\Rate\{PrivateRateStorage,RateCleanup};
try{
    $totals=['deleted'=>0,'active'=>0];
    foreach(['core','range','week','month','year','interpretation'] as $bucket){
        $r=(new RateCleanup())->run(PrivateRateStorage::configured($bucket),time());
        foreach($totals as $k=>$v){$totals[$k]+=$r[$k];}
    }
    echo json_encode(['result'=>'RATE_CLEANUP_PASS']+$totals,JSON_THROW_ON_ERROR).PHP_EOL;
}catch(Throwable){fwrite(STDERR,"RATE_CLEANUP_FAILED\n");exit(1);}
