<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;
use LoveFortune\Core\Support\Rational as R;

/** Pre-SC07 internal results. Rational values are intentionally not public wire DTOs. */
final class SajuFeatureAggregator
{
    public const MULTI_VERSION='SAJU_MULTI_VARIANT_IDENTITY_V1';
    public function __construct(private readonly SajuFeatureCatalog $catalog = new SajuFeatureCatalog()) {}

    public function aggregate(string $ruleId, string $category, array $rows, R $baseConfidence): array
    {
        $rule=$this->catalog->rule($ruleId);$categories=[];
        foreach($rule['variants'] as $v){foreach($v['categoryMappings'] as $m){$categories[$m['category']]=true;}}
        if (!$rule['enabled'] || $rule['contextOnly'] || !isset($categories[$category]) || $baseConfidence->compare(R::of(0))<0 || $baseConfidence->compare(R::of(1))>0) { throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR'); }
        usort($rows,static fn(array $a,array $b):int=>strcmp($a['pairRef']??'',$b['pairRef']??''));
        $counts=SajuEvidenceLedger::counts($rows);$values=[];$contributions=[];
        foreach($rows as $row){
            if($row['state']!=='MATCHED'){continue;}
            $variant=$this->catalog->variant($ruleId,$row['ruleVariant']);$mapping=null;
            foreach($variant['categoryMappings'] as $m){if($m['category']===$category){$mapping=$m;}}
            if($mapping===null){throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR');}
            $value=R::of((string)$mapping['signedValue']);$values[]=$value;$key=$row['ruleVariant'];
            $contributions[$key]??=['ruleVariant'=>$key,'presentCount'=>0,'signedValue'=>$value,'pairRefs'=>[]];
            $contributions[$key]['presentCount']++;$contributions[$key]['pairRefs'][]=$row['pairRef'];
        }
        ksort($contributions,SORT_STRING);$feature=null;
        if($counts['presentCount']>0){
            $sum=R::of(0);$min=$values[0];$max=$values[0];
            foreach($values as $v){$sum=$sum->add($v);if($v->compare($min)<0){$min=$v;}if($v->compare($max)>0){$max=$v;}}
            $confidence=$baseConfidence->multiply(R::of(1)->subtract($max->subtract($min)->divide(R::of(2))))
                ->multiply(R::of($counts['presentCount'])->divide(R::of($counts['totalCount'])));
            $variants=array_keys($contributions);$identity=self::identity($ruleId,$category,$variants);
            $signed=$sum->divide(R::of(count($values)));
            $feature=$identity+['featureId'=>self::featureId($identity),'signedValue'=>$signed,
                'direction'=>$signed->compare(R::of(0))>0?'POSITIVE':($signed->isZero()?'NEUTRAL':'NEGATIVE'),
                'rawValue'=>null,'baseWeight'=>R::of((string)$rule['baseWeight']),'preConfidenceWeight'=>$this->catalog->weight($ruleId),
                'confidence'=>$confidence,'baseConfidence'=>$baseConfidence,'variantContributions'=>array_values($contributions)];
            if(count($variants)===1){$feature['metadata']+= $this->catalog->variant($ruleId,$variants[0])['metadata'];}
        }
        return ['ruleId'=>$ruleId,'subject'=>'PAIR','category'=>$category,'period'=>['type'=>'LIFETIME'],'rows'=>$rows,'feature'=>$feature]
            +$counts+SajuCoverageProjection::group($counts,$this->catalog->weight($ruleId));
    }

    public static function identity(string $ruleId,string $category,array $variants): array
    {
        $variants=array_values(array_unique($variants));sort($variants,SORT_STRING);
        if($variants===[]){throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR');}
        $id=['ruleId'=>$ruleId,'source'=>'SAJU','subject'=>'PAIR','category'=>$category,'period'=>['type'=>'LIFETIME']];
        return count($variants)===1?$id+['metadata'=>['ruleVariant'=>$variants[0]]]:$id+[
            'identityVersion'=>self::MULTI_VERSION,'aggregateVariantType'=>'MULTI','ruleVariants'=>$variants];
    }

    public static function featureId(array $identity): string
    {
        // Identity vocabulary is ASCII. No user strings, counts or provenance enter the hash.
        $sort=static function(array $v) use (&$sort):array {if(!array_is_list($v)){ksort($v,SORT_STRING);}foreach($v as &$x){if(is_array($x)){$x=$sort($x);}}unset($x);return $v;};
        return 'ft_'.substr(hash('sha256',json_encode($sort($identity),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),0,48);
    }
}
