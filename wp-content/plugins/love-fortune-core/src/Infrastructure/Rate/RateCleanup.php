<?php
declare(strict_types=1);
namespace LoveFortune\Core\Infrastructure\Rate;
final class RateCleanup
{
    public function run(PrivateRateStorage $store,int $now): array
    {
        return $store->locked(function()use($store,$now):array{
            $deleted=0;$active=0;
            foreach($store->entries() as $name){$window=(int)strtok($name,'-');$h=$store->open($name,false);if($h===null){continue;}
                $store->counterLocked($h,function()use($store,$now,$window,$name,$h,&$deleted,&$active):void{
                    $store->count($h);
                    if($now>=$window+660){$store->remove($name,$h);$deleted++;}else{$active++;}
                });
            }return ['deleted'=>$deleted,'active'=>$active];
        });
    }
}
