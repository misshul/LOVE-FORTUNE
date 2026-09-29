<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;
use LoveFortune\Core\Support\Rational as R;

final class SajuContextAggregator
{
    public function __construct(private readonly SajuFeatureCatalog $catalog = new SajuFeatureCatalog()) {}

    public function aggregate(array $rows): array
    {
        $allowed=array_merge(...array_values($this->catalog->tables()['tenGods']));$values=[];$e=0;$c=0;$seen=[];
        usort($rows,static fn(array $a,array $b):int=>strcmp($a['pairRef']??'',$b['pairRef']??''));
        foreach($rows as $r){
            if(!in_array($r['state']??null,['MATCHED','UNAVAILABLE'],true)
                || !is_string($r['pairRef']??null) || !preg_match('/^A:C(?:0|[1-9][0-9]*)\|B:C(?:0|[1-9][0-9]*)$/D',$r['pairRef']) || isset($seen[$r['pairRef']])){throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR');}
            $seen[$r['pairRef']]=true;$e++;
            if($r['state']==='MATCHED'){
                if(!in_array($r['value']??null,$allowed,true)){throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR');}
                $c++;$values[$r['value']]=($values[$r['value']]??0)+1;
            }elseif(array_key_exists('value',$r)){throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR');}
        }
        ksort($values,SORT_STRING);
        return ['state'=>$c===0?'UNAVAILABLE':($c<$e?'PARTIAL':(count($values)===1?'AGREED':'VARIANT')),
            'eligibleCount'=>$e,'computableCount'=>$c,'valueCounts'=>$values,
            'contextConfidence'=>$e===0?null:R::of($c)->divide(R::of($e)),'rows'=>$rows];
    }
}
