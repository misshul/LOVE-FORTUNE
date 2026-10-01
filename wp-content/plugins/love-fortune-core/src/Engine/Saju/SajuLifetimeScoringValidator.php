<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

use LoveFortune\Core\Support\Rational as R;

/** Internal extractor-output consistency checks, not a public Birth/Feature parser. */
final class SajuLifetimeScoringValidator
{
    public function __construct(private readonly SajuFeatureCatalog $catalog = new SajuFeatureCatalog()) {}

    private function require(bool $condition,string $code='INVALID_FEATURE_EVIDENCE'): void
    {
        if(!$condition){throw new \InvalidArgumentException($code);}
    }
    private function same(mixed $actual,mixed $expected): bool
    {
        if($expected instanceof R){return $actual instanceof R && $actual->compare($expected)===0;}
        if(is_array($expected)){
            if(!is_array($actual) || count($actual)!==count($expected)){return false;}
            foreach($expected as $k=>$v){if(!array_key_exists($k,$actual) || !$this->same($actual[$k],$v)){return false;}}
            return true;
        }
        return $actual===$expected;
    }

    public function validate(array $input): array
    {
        $this->require(($input['version']??null)===SajuFeatureExtractionService::VERSION
            && ($input['catalogVersion']??null)===SajuFeatureCatalog::VERSION
            && ($input['branchPolarityVersion']??null)===SajuBranchPolarity::VERSION,'SCORING_CONTRACT_MISMATCH');
        foreach(['features','ledger','coverage','tenGods'] as $k){$this->require(is_array($input[$k]??null));}
        $this->require(array_is_list($input['features']) && array_is_list($input['ledger']));
        $this->require(is_int($input['candidatePairCount']??null) && $input['candidatePairCount']>=0);
        $expected=[];$categories=[];
        foreach($this->catalog->rules() as $r){
            if(!$r['enabled'] || $r['contextOnly']){continue;}
            foreach($r['variants'] as $v){foreach($v['categoryMappings'] as $m){
                $expected[$r['ruleId'].'|'.$m['category']]=$r;$categories[$m['category']]=[];
            }}
        }
        $features=[];
        foreach($input['features'] as $f){
            $this->require(is_array($f) && is_string($f['featureId']??null));
            $this->require(!isset($features[$f['featureId']]));$features[$f['featureId']]=$f;
        }
        $groups=[];$referenced=[];$pairSet=null;
        foreach($input['ledger'] as $g){
            $this->require(is_array($g) && is_string($g['ruleId']??null) && is_string($g['category']??null));
            $key=$g['ruleId'].'|'.$g['category'];
            $this->require(isset($expected[$key]) && !isset($groups[$key]));
            $this->require(($g['subject']??null)==='PAIR' && ($g['period']??null)===['type'=>'LIFETIME']);
            $this->require(is_array($g['rows']??null) && array_is_list($g['rows']) && array_key_exists('feature',$g));
            $counts=SajuEvidenceLedger::counts($g['rows']);
            $this->require($counts['deferredCount']===0 && $counts['eligibleCount']===$input['candidatePairCount']);
            foreach($counts as $k=>$v){$this->require(($g[$k]??null)===$v);}
            $refs=array_column($g['rows'],'pairRef');sort($refs,SORT_STRING);
            if($pairSet===null){$pairSet=$refs;}else{$this->require($refs===$pairSet);}
            $weight=$this->catalog->weight($g['ruleId']);
            foreach(SajuCoverageProjection::group($counts,$weight) as $k=>$v){$this->require(array_key_exists($k,$g) && $this->same($g[$k],$v));}
            $this->require(($counts['presentCount']>0)===is_array($g['feature']));
            if($g['feature']!==null){
                $f=$g['feature'];$id=$f['featureId']??null;
                $this->require(is_string($id) && isset($features[$id]) && !isset($referenced[$id]) && $this->same($features[$id],$f));
                $this->feature($f,$g,$expected[$key]);$referenced[$id]=true;$categories[$g['category']][]=$f;
            }
            $groups[$key]=$g;
        }
        $this->require(count($groups)===count($expected) && count($referenced)===count($features));
        $coverage=SajuCoverageProjection::categories(array_values($groups));
        $this->require($this->same($input['coverage'],$coverage));
        $this->require(array_keys($input['tenGods'])===['PERSON_A','PERSON_B']);
        foreach($input['tenGods'] as $context){
            $this->require(is_array($context) && is_array($context['rows']??null));
            $actual=(new SajuContextAggregator($this->catalog))->aggregate($context['rows']);
            $this->require($this->same($context,$actual) && $actual['eligibleCount']===$input['candidatePairCount']);
            $refs=array_column($context['rows'],'pairRef');sort($refs,SORT_STRING);$this->require($refs===($pairSet??[]));
        }
        ksort($categories,SORT_STRING);
        foreach($categories as &$f){usort($f,static fn(array $a,array $b):int=>strcmp($a['featureId'],$b['featureId']));}unset($f);
        return ['featuresByCategory'=>$categories,'coverage'=>$coverage];
    }

    private function feature(array $f,array $g,array $rule): void
    {
        foreach(['ruleId','subject','category','period'] as $k){$this->require(($f[$k]??null)===$g[$k]);}
        $this->require(($f['source']??null)==='SAJU');
        foreach(['signedValue','confidence','baseConfidence','baseWeight','preConfidenceWeight'] as $k){$this->require(($f[$k]??null) instanceof R);}
        $this->require($f['signedValue']->abs()->compare(R::of(1))<=0);
        foreach(['confidence','baseConfidence'] as $k){$this->require($f[$k]->compare(R::of(0))>=0 && $f[$k]->compare(R::of(1))<=0);}
        $this->require($f['confidence']->compare($f['baseConfidence'])<=0);
        $this->require($this->same($f['baseWeight'],R::of((string)$rule['baseWeight']))
            && $this->same($f['preConfidenceWeight'],$this->catalog->weight($g['ruleId'])));
        $direction=$f['signedValue']->isZero()?'NEUTRAL':($f['signedValue']->compare(R::of(0))>0?'POSITIVE':'NEGATIVE');
        $this->require(($f['direction']??null)===$direction);
        $variants=[];
        foreach($g['rows'] as $row){if($row['state']==='MATCHED'){$variants[$row['ruleVariant']][]=$row['pairRef'];}}
        ksort($variants,SORT_STRING);$contributions=[];$approved=[];
        foreach($variants as $variant=>$refs){
            sort($refs,SORT_STRING);$mapped=null;
            foreach($this->catalog->variant($g['ruleId'],$variant)['categoryMappings'] as $m){if($m['category']===$g['category']){$mapped=R::of((string)$m['signedValue']);}}
            $this->require($mapped!==null);$approved[]=$mapped;
            $contributions[]=['ruleVariant'=>$variant,'presentCount'=>count($refs),'signedValue'=>$mapped,'pairRefs'=>$refs];
        }
        $this->require($this->same($f['variantContributions']??null,$contributions));
        // Do not recompute the candidate mean or feature confidence. Check registered bounds only.
        usort($approved,static fn(R $a,R $b):int=>$a->compare($b));
        $this->require($f['signedValue']->compare($approved[0])>=0 && $f['signedValue']->compare($approved[count($approved)-1])<=0);
        $identity=SajuFeatureAggregator::identity($g['ruleId'],$g['category'],array_keys($variants));
        $multi=count($variants)>1;$error=$multi?'INVALID_MULTI_IDENTITY':'INVALID_FEATURE_EVIDENCE';
        foreach($identity as $k=>$v){
            if($k==='metadata'){$v+=$this->catalog->variant($g['ruleId'],array_key_first($variants))['metadata'];}
            $this->require($this->same($f[$k]??null,$v),$error);
        }
        $this->require(($f['featureId']??null)===SajuFeatureAggregator::featureId($identity),$error);
        if($multi){$this->require(!array_key_exists('metadata',$f),$error);}
        else{foreach(['identityVersion','aggregateVariantType','ruleVariants'] as $k){$this->require(!array_key_exists($k,$f),'INVALID_MULTI_IDENTITY');}}
    }
}
