<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__).'/autoload.php';
use LoveFortune\Core\Api\{CoreRateLimit,PublicError};
use LoveFortune\Core\Infrastructure\Rate\{PrivateRateStorage,RateCleanup};
$ok=0;$limited=0;$now=1800000000;
for($i=0;$i<40;$i++){
    try{
        if(($argv[2]??'')==='clean'){(new RateCleanup())->run(new PrivateRateStorage($argv[1]),$now);}
        else{(new CoreRateLimit(str_repeat('synthetic',8),$argv[1]))->consume('192.0.2.99',$now);}
        $ok++;
    }catch(PublicError $e){if($e->status!==429){throw $e;}$limited++;}
}
echo json_encode(['ok'=>$ok,'limited'=>$limited],JSON_THROW_ON_ERROR);
