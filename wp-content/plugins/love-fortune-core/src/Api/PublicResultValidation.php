<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

use LoveFortune\Core\Application\Score\LifetimeSourceEnvelope;
use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;
use LoveFortune\Core\Support\Rational as R;

/** Closed public DTO validation shared by issuing and interpretation verification. */
final class PublicResultValidation
{
    private static function keys(array $a,array $required,array $optional=[]): void
    {
        if(array_diff($required,array_keys($a))!==[]||array_diff(array_keys($a),[...$required,...$optional])!==[]){throw new \InvalidArgumentException('Invalid public shape');}
    }
    private static function number(mixed $v,int $min,int $max,bool $four=false): void
    {
        if((!is_int($v)&&!is_float($v))||!is_finite((float)$v)||$v<$min||$v>$max){throw new \InvalidArgumentException('Invalid public number');}
        if($four && R::of((string)$v)->compare(R::of(R::of((string)$v)->halfUp4()))!==0){throw new \InvalidArgumentException('Invalid public precision');}
    }
    private static function row(array $r): void
    {
        self::keys($r,['score','coverage','resultConfidence','status']);
        self::number($r['coverage'],0,1,true);self::number($r['resultConfidence'],0,1,true);
        if($r['score']!==null){self::number($r['score'],0,100,true);}
        if($r['resultConfidence']>$r['coverage']||($r['score']===null&&$r['resultConfidence']!=0)){throw new \InvalidArgumentException('Invalid confidence');}
        // A positive exact coverage can serialize to zero; do not reconstruct its internal invariant.
        $status=$r['score']===null?'INSUFFICIENT_DATA':(new CategoryResult(R::of((string)$r['score']),R::of(1),R::of(0)))->status();
        if($r['status']!==$status){throw new \InvalidArgumentException('Invalid status');}
    }
    public static function validate(array $r): void
    {
        self::keys($r,['meta','overallScore','coverage','resultConfidence','status','categories','features','warnings'],['zodiacContext']);
        self::keys($r['meta'],['requestId','versions']);
        if(!is_string($r['meta']['requestId'])||!preg_match('/^[0-9a-f]{32}$/D',$r['meta']['requestId'])||CanonicalJson::encode($r['meta']['versions'])!==CanonicalJson::encode(PublicRelease::versions())){throw new \InvalidArgumentException('Invalid public metadata');}
        self::row(['score'=>$r['overallScore'],'coverage'=>$r['coverage'],'resultConfidence'=>$r['resultConfidence'],'status'=>$r['status']]);
        self::keys($r['categories'],LifetimeSourceEnvelope::CATEGORIES);
        foreach($r['categories'] as $row){self::row($row);}
        $c=$r['categories']['COMMUNICATION'];if($c['score']!==null||$c['coverage']!=0||$c['resultConfidence']!=0){throw new \InvalidArgumentException('Invalid communication');}
        // This release emits no arbitrary free-text warnings.
        if($r['warnings']!==[]||!is_array($r['features'])||!array_is_list($r['features'])){throw new \InvalidArgumentException('Invalid evidence');}
        if(isset($r['zodiacContext'])){
            $ctx=$r['zodiacContext'];if(CanonicalJson::encode($ctx)!==CanonicalJson::encode((new ZodiacPairResolver())->resolve($ctx['signA'],$ctx['signB']))){throw new \InvalidArgumentException('Invalid context');}
        }
        $ids=[];
        foreach($r['features'] as $f){
            self::keys($f,['featureId','ruleId','source','subject','category','direction','rawValue','baseWeight','confidence','period','metadata','signedValue']);
            if(!in_array($f['source'],['SAJU','ZODIAC'],true)||$f['subject']!=='PAIR'||$f['period']!==['type'=>'LIFETIME']||!in_array($f['category'],LifetimeSourceEnvelope::ELIGIBLE[$f['source']],true)){throw new \InvalidArgumentException('Invalid feature scope');}
            self::number($f['confidence'],0,1);self::number($f['signedValue'],-1,1);self::number($f['baseWeight'],0,100);
            if(!is_scalar($f['rawValue']) && $f['rawValue']!==null){throw new \InvalidArgumentException('Invalid raw value');}
            if($f['source']==='SAJU'){
                self::keys($f['metadata'],['ruleVariant'],['pillar','element','relation','referenceId','transformationStatus']);
                if($f['rawValue']!==null){throw new \InvalidArgumentException('Invalid Saju raw value');}
                $catalog=new \LoveFortune\Core\Engine\Saju\SajuFeatureCatalog();$rule=$catalog->rule($f['ruleId']);$variant=$catalog->variant($f['ruleId'],$f['metadata']['ruleVariant']);
                $mapping=null;foreach($variant['categoryMappings'] as $m){if($m['category']===$f['category']){$mapping=$m;}}
                if(!$rule['enabled']||$rule['contextOnly']||$mapping===null||R::of((string)$mapping['signedValue'])->compare(R::of((string)$f['signedValue']))!==0||R::of((string)$rule['baseWeight'])->compare(R::of((string)$f['baseWeight']))!==0){throw new \InvalidArgumentException('Invalid Saju evidence');}
                $expected=['ruleVariant'=>$f['metadata']['ruleVariant']]+$variant['metadata'];
                if(CanonicalJson::encode($expected)!==CanonicalJson::encode($f['metadata'])){throw new \InvalidArgumentException('Invalid Saju metadata');}
            }else{
                $ctx=$r['zodiacContext']??throw new \InvalidArgumentException('Missing context');
                $expected=(new \LoveFortune\Core\Engine\Zodiac\ZodiacScorer())->features($ctx['signA'],$ctx['signB']);
                $found=false;foreach($expected as $e){if(CanonicalJson::encode($e)===CanonicalJson::encode($f)){$found=true;}}
                if(!$found){throw new \InvalidArgumentException('Invalid Zodiac evidence');}
            }
            $direction=$f['signedValue']>0?'POSITIVE':($f['signedValue']<0?'NEGATIVE':'NEUTRAL');
            if($f['direction']!==$direction||CompatibilityProjection::featureId($f)!==$f['featureId']||isset($ids[$f['featureId']])){throw new \InvalidArgumentException('Invalid identity');}
            $ids[$f['featureId']]=true;
        }
    }
}
