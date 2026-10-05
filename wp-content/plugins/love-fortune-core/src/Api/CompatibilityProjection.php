<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Support\Rational;
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;

final class CompatibilityProjection
{
    public static function row(CategoryResult $r): array
    {
        return ['score'=>$r->score===null?null:(float)$r->score->halfUp4(),
            'coverage'=>(float)$r->coverage->halfUp4(),'resultConfidence'=>(float)$r->confidence->halfUp4(),'status'=>$r->status()];
    }
    public function project(array $combined,array $features,string $requestId): array
    {
        $overall=self::row($combined['overall']);$categories=[];
        foreach($combined['categories'] as $key=>$row){$categories[$key]=self::row($row);}
        $out=['meta'=>['requestId'=>$requestId,'versions'=>PublicRelease::versions()],
            'overallScore'=>$overall['score'],'coverage'=>$overall['coverage'],'resultConfidence'=>$overall['resultConfidence'],
            'status'=>$overall['status'],'categories'=>$categories,'features'=>$this->features($features),'warnings'=>[]];
        if(isset($combined['contexts']['ZODIAC']['context'])){
            $c=$combined['contexts']['ZODIAC']['context'];
            if(CanonicalJson::encode($c)!==CanonicalJson::encode((new ZodiacPairResolver())->resolve($c['signA'],$c['signB']))){throw new \RuntimeException('Invalid context');}
            $out['zodiacContext']=$c;
        }
        return $out;
    }
    public static function featureId(array $f): string
    {
        $keys=$f['source']==='ZODIAC'?['signA','signB','pairId','relation','modelVersion','dateRangeVersion']:['pillar','relation','referenceId','ruleVariant'];
        $identity=array_intersect_key($f,array_flip(['ruleId','subject','category','period','source']));
        $identity['metadata']=array_intersect_key($f['metadata'],array_flip($keys));
        return 'ft_'.substr(hash('sha256',CanonicalJson::encode($identity)),0,48);
    }
    public function features(array $rows): array
    {
        $out=[];$fields=['featureId','ruleId','source','subject','category','direction','rawValue','baseWeight','confidence','period','metadata','signedValue'];
        $allowed=['pillar','element','relation','referenceId','ruleVariant','transformationStatus'];
        foreach($rows as $f){
            if(isset($f['identityVersion']) || ($f['aggregateVariantType']??null)==='MULTI'){continue;}
            $public=array_intersect_key($f,array_flip($fields));
            if(count($public)!==count($fields)){continue;}
            if($f['source']==='SAJU'){$public['metadata']=array_intersect_key($f['metadata'],array_flip($allowed));}
            foreach(['rawValue','baseWeight','confidence','signedValue'] as $key){
                if($public[$key] instanceof Rational){
                    // Only exactly representable decimal evidence is published. Scores use their separate HALF_UP contract.
                    [$n,$d]=explode('/',(string)$public[$key]);$den=(int)$d;
                    if((string)$den!==$d || $den<1){continue 2;}
                    while($den%2===0){$den=intdiv($den,2);}while($den%5===0){$den=intdiv($den,5);}
                    if($den!==1){continue 2;}
                    $public[$key]=(float)$n/(float)$d;
                    if(Rational::of(CanonicalJson::encode($public[$key]))->compare($f[$key])!==0){continue 2;}
                }
            }
            if(self::featureId($public)!==$f['featureId']){continue;}
            $out[$public['featureId']]=$public;
        }
        ksort($out,SORT_STRING);return array_values($out);
    }
}
