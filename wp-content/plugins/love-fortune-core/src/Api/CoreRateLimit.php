<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Infrastructure\Rate\PrivateRateStorage;

/** Atomic local infrastructure counters only. No raw IP or request content is stored. */
final class CoreRateLimit
{
    public function __construct(private readonly string $secret,private readonly string $directory,private readonly int $limit=60,private readonly string $scope='Core') {}
    public function consume(string $address,int $now): void
    {
        if(strlen($this->secret)<32||$now<0){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        $packed=@inet_pton($address);if($packed===false){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
        if(strlen($packed)===16 && substr($packed,0,12)===str_repeat("\0",10)."\xff\xff"){$packed=substr($packed,12);}
        $ip=inet_ntop($packed);$window=intdiv($now,600)*600;$hour=intdiv($now,3600)*3600;
        $secret=hash_hmac('sha256',(string)$hour,$this->secret,true);
        $name=$window.'-'.hash_hmac('sha256',$this->scope.':'.$window.':'.$ip,$secret).'.rate';
        $store=new PrivateRateStorage($this->directory);
        $store->locked(function()use($store,$name,$now,$window):void{
            $exists=file_exists($this->directory.'/'.$name);
            if(!$exists&&count($store->entries())>=PrivateRateStorage::MAX_COUNTERS){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
            $h=$store->open($name);
            $store->counterLocked($h,function()use($h,$store,$exists,$window,$now):void{
                $count=$exists?$store->count($h):0;
                if($count>=$this->limit){throw new PublicError(429,'RATE_LIMITED',['Retry-After'=>(string)max(1,$window+600-$now)]);}
                $store->write($h,$count+1);
            });
        });
    }
}
