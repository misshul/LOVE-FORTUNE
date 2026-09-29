<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;
use LoveFortune\Core\Support\Rational as R;

/** Internal NatalResolutionResult -> pre-SC07 evidence; no HTTP/storage/scoring orchestration. */
final class SajuFeatureExtractionService
{
    public const VERSION='SAJU_FEATURE_EXTRACTOR_V1';
    public function __construct(private readonly SajuFeatureCatalog $catalog = new SajuFeatureCatalog()) {}

    private function candidates(array $n): array
    {
        foreach(['natalResolverVersion'=>NatalResolutionService::VERSION,'locationReferenceVersion'=>LocationReferenceRepository::VERSION,
            'timezoneReferenceVersion'=>TimezoneReferenceRepository::VERSION] as $k=>$v){
            if(($n[$k]??null)!==$v){throw new \InvalidArgumentException('REFERENCE_VERSION_MISMATCH');}
        }
        foreach(['locationFileChecksum'=>LocationReferenceRepository::FILE_SHA256,'timezoneFileChecksum'=>TimezoneReferenceRepository::FILE_SHA256] as $k=>$v){
            if(($n['referenceProvenance'][$k]??null)!==$v){throw new \InvalidArgumentException('REFERENCE_VERSION_MISMATCH');}
        }
        if(!is_bool($n['timeKnown']??null) || !is_array($n['candidates']??null) || !array_is_list($n['candidates'])){throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE');}
        $count=count($n['candidates']);$status=$count===0?'GAP_UNRESOLVED':(!$n['timeKnown']?'UNKNOWN_TIME':($count===1?'UNIQUE':'FOLD_AMBIGUOUS'));
        if(($n['status']??null)!==$status){throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE');}
        $extractor=new SajuCandidateRawExtractor($this->catalog);$out=[];
        foreach($n['candidates'] as $candidate){
            if(!is_array($candidate)){throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE');}
            $raw=$extractor->extract($candidate);$id=$raw['candidateId'];
            if(isset($out[$id]) || $raw['hourKnown']!==$n['timeKnown']){throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE');}
            $out[$id]=$raw;
        }
        uksort($out,static fn(string $a,string $b):int=>strlen($a)<=>strlen($b) ?: strcmp($a,$b));
        if(array_keys($out)!==array_map(static fn(int $i):string=>'C'.$i,$count===0?[]:range(0,$count-1))){throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE');}
        return array_values($out);
    }

    public function extract(array $personA,array $personB): array
    {
        // Validate both complete sets before evaluating any pair. No partial success on errors.
        $a=$this->candidates($personA);$b=$this->candidates($personB);$pairs=[];
        $evaluator=new SajuCandidatePairEvaluator(new SajuRelationDetector($this->catalog));
        foreach($a as $ca){foreach($b as $cb){$pairs[]=$evaluator->evaluate($ca,$cb);}}
        $aggregator=new SajuFeatureAggregator($this->catalog);$groups=[];$features=[];$deferred=[];
        $rules=$this->catalog->rules();usort($rules,static fn(array $a,array $b):int=>strcmp($a['ruleId'],$b['ruleId']));
        foreach($rules as $rule){
            if($rule['detectorStatus']==='DEFERRED'){$deferred[]=['ruleId'=>$rule['ruleId'],'state'=>'DEFERRED'];continue;}
            if(!$rule['enabled'] || $rule['contextOnly']){continue;}
            $categories=[];foreach($rule['variants'] as $v){foreach($v['categoryMappings'] as $m){$categories[$m['category']]=true;}}ksort($categories,SORT_STRING);
            foreach(array_keys($categories) as $category){
                $rows=[];$base=R::of(1);
                foreach($pairs as $pair){
                    $variant=$pair['variants'][$rule['ruleId']]??null;$matched=false;
                    if($variant!==null){foreach($this->catalog->variant($rule['ruleId'],$variant)['categoryMappings'] as $m){if($m['category']===$category){$matched=true;}}}
                    $rows[]=['pairRef'=>$pair['pairRef'],'state'=>$matched?'MATCHED':'NOT_MATCHED']+($matched?['ruleVariant'=>$variant]:[]);
                    if($rule['scope']==='KNOWN_TOKEN_POOL'){$base=$pair['wholeChart']['baseConfidence'];}
                }
                $group=$aggregator->aggregate($rule['ruleId'],$category,$rows,$base);$groups[]=$group;
                if($group['feature']!==null){$features[]=$group['feature'];}
            }
        }
        $contexts=new SajuContextAggregator($this->catalog);$context=[];
        foreach(['A','B'] as $side){$rows=[];foreach($pairs as $p){$rows[]=['pairRef'=>$p['pairRef'],'state'=>'MATCHED','value'=>$p['tenGods'.$side]];}$context['PERSON_'.$side]=$contexts->aggregate($rows);}
        return ['version'=>self::VERSION,'catalogVersion'=>SajuFeatureCatalog::VERSION,'branchPolarityVersion'=>SajuBranchPolarity::VERSION,
            'rawCandidates'=>['PERSON_A'=>$a,'PERSON_B'=>$b],'candidatePairCount'=>count($pairs),'pairs'=>$pairs,'ledger'=>$groups,
            'features'=>$features,'coverage'=>SajuCoverageProjection::categories($groups),'tenGods'=>$context,'deferred'=>$deferred];
    }
}
