<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

/** Atomic local infrastructure counters only. No raw IP or request content is stored. */
final class CoreRateLimit
{
    public function __construct(private readonly string $secret,private readonly string $directory,private readonly int $limit=60,private readonly string $scope='Core') {}
    public function consume(string $address,int $now): void
    {
        if(strlen($this->secret)<32){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        $packed=@inet_pton($address);if($packed===false){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
        if(strlen($packed)===16 && substr($packed,0,12)===str_repeat("\0",10)."\xff\xff"){$packed=substr($packed,12);}
        $ip=inet_ntop($packed);$window=intdiv($now,600)*600;$hour=intdiv($now,3600)*3600;
        $secret=hash_hmac('sha256',(string)$hour,$this->secret,true);
        $id=hash_hmac('sha256',$this->scope.':'.$window.':'.$ip,$secret);
        if(!is_dir($this->directory)&&!@mkdir($this->directory,0700,true)&&!is_dir($this->directory)){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        // Expired infrastructure keys are removed on use. No source identifier appears in names.
        foreach(glob($this->directory.'/*.rate')?:[] as $old){if(filemtime($old)<$now-3600){@unlink($old);}}
        $handle=@fopen($this->directory.'/'.$id.'.rate','c+');
        if($handle===false){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        try{
            if(!flock($handle,LOCK_EX)){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
            $count=(int)stream_get_contents($handle);
            if($count>=$this->limit){throw new PublicError(429,'RATE_LIMITED',['Retry-After'=>(string)max(1,$window+600-$now)]);}
            rewind($handle);if(!ftruncate($handle,0)||fwrite($handle,(string)($count+1))===false){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
            touch($this->directory.'/'.$id.'.rate',$now);
        }finally{flock($handle,LOCK_UN);fclose($handle);}
    }
}
