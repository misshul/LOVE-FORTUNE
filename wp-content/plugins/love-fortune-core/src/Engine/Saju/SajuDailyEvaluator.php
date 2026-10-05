<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

use LoveFortune\Core\Support\Rational as R;

/** Daily Feature-first aggregation. No Lifetime mappings, SC-07, or A x B evaluation. */
final class SajuDailyEvaluator
{
    public const CATEGORIES = ['ATTRACTION','EMOTION','COMMUNICATION','PASSION','STABILITY','HARMONY','SUPPORT'];
    public const CATALOG_SHA256 = '54e51d4492c632048742cff04df53aae85549553664c3d25e5277e818e6ffce8';
    private array $rules;
    private readonly SajuFeatureCatalog $saju;
    public function __construct(string $path = __DIR__.'/../../../config/references/daily-rules-v1.json')
    {
        if (str_contains($path,'://') || !is_file($path)) { throw new \RuntimeException('DAILY_CATALOG_MISMATCH'); }
        $bytes=file_get_contents($path);
        if ($bytes===false || hash('sha256',$bytes)!==self::CATALOG_SHA256) { throw new \RuntimeException('DAILY_CATALOG_MISMATCH'); }
        $data=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);
        $this->rules=array_column(array_filter($data['rules'],static fn(array $r):bool=>$r['source']==='SAJU'),null,'ruleId');
        $this->saju=new SajuFeatureCatalog();
    }

    public function candidates(array $natal): array
    {
        foreach (['natalResolverVersion'=>NatalResolutionService::VERSION,'locationReferenceVersion'=>LocationReferenceRepository::VERSION,
            'timezoneReferenceVersion'=>TimezoneReferenceRepository::VERSION] as $k=>$v) {
            if (($natal[$k]??null)!==$v) { throw new \InvalidArgumentException('REFERENCE_VERSION_MISMATCH'); }
        }
        foreach (['locationFileChecksum'=>LocationReferenceRepository::FILE_SHA256,'timezoneFileChecksum'=>TimezoneReferenceRepository::FILE_SHA256] as $k=>$v) {
            if (($natal['referenceProvenance'][$k]??null)!==$v) { throw new \InvalidArgumentException('REFERENCE_VERSION_MISMATCH'); }
        }
        if (!is_bool($natal['timeKnown']??null) || !is_array($natal['candidates']??null) || !array_is_list($natal['candidates'])) { throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE'); }
        $count=count($natal['candidates']);
        $status=$count===0?'GAP_UNRESOLVED':(!$natal['timeKnown']?'UNKNOWN_TIME':($count===1?'UNIQUE':'FOLD_AMBIGUOUS'));
        if (($natal['status']??null)!==$status) { throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE'); }
        $extractor=new SajuCandidateRawExtractor($this->saju); $out=[];
        foreach ($natal['candidates'] as $c) {
            $raw=$extractor->extract($c); $id=$raw['candidateId'];
            if (isset($out[$id]) || $raw['hourKnown']!==$natal['timeKnown']) { throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE'); }
            $out[$id]=$raw;
        }
        uksort($out,static fn(string $a,string $b):int=>strlen($a)<=>strlen($b) ?: strcmp($a,$b));
        if (array_keys($out)!==array_map(static fn(int $i):string=>'C'.$i,$count?range(0,$count-1):[])) { throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE'); }
        return array_values($out);
    }

    /** Fixed rule/category values make agreement=1; presence retains all equal-unit candidates. */
    private function person(array $raw, array $day): array
    {
        if ($raw===[]) { return array_fill_keys(self::CATEGORIES,null); }
        $detector=new SajuRelationDetector($this->saju); $hits=[];
        foreach ($raw as $c) {
            [, $stem]=$detector->dayRelation($day['stemIndex'],$c['dayStem']);
            [, $branch]=$detector->dayRelation($day['branchIndex'],$c['dayBranch'],true);
            $stem=$stem==='COMB'?'COMBINATION':$stem;
            $branch=str_starts_with($branch,'B')?substr($branch,1):$branch;
            foreach (['STEM_'.$stem,'BRANCH_'.$branch] as $owner) { $id='DAILY_SAJU_'.$owner; $hits[$id]=($hits[$id]??0)+1; }
        }
        ksort($hits,SORT_STRING); $events=array_fill_keys(self::CATEGORIES,[]);
        foreach ($hits as $id=>$present) {
            $rule=$this->rules[$id]??throw new \RuntimeException('DAILY_CATALOG_MISMATCH');
            $weight=R::of((string)$rule['baseWeight'])->multiply(R::of((string)$rule['ruleWeight']))->multiply(R::of((string)$rule['pairWeight']));
            $confidence=R::of($present)->divide(R::of(count($raw)));
            foreach ($rule['categoryMappings'] as $mapping) {
                $events[$mapping['category']][]=['weight'=>$weight,'confidence'=>$confidence,'value'=>R::of((string)$mapping['signedValue'])];
            }
        }
        $result=[];
        foreach ($events as $cat=>$rows) {
            if ($rows===[]) { $result[$cat]=['source'=>'SAJU','signal'=>R::of(0),'confidence'=>R::of(1)]; continue; }
            $pre=$effective=$sum=R::of(0);
            foreach ($rows as $r) { $w=$r['weight']->multiply($r['confidence']); $pre=$pre->add($r['weight']); $effective=$effective->add($w); $sum=$sum->add($w->multiply($r['value'])); }
            $result[$cat]=$effective->compare(R::of(0))===0?null:['source'=>'SAJU','signal'=>$sum->divide($effective),'confidence'=>$effective->divide($pre)];
        }
        return $result;
    }

    public function evaluate(array $a, array $b, array $samples): array
    {
        $a=$this->candidates($a); $b=$this->candidates($b);
        if (count($samples)!==4 || !array_is_list($samples)) { throw new \InvalidArgumentException('INVALID_DAILY_SAMPLES'); }
        $slots=[];
        foreach ($samples as $i=>$sample) {
            if (($sample['sampleRef']['time']??null)!==FrozenDailySampleResolver::TIMES[$i]) { throw new \InvalidArgumentException('INVALID_DAILY_SAMPLES'); }
            $day=$sample['pillar'];
            if ($day!==(new DayPillarCalculator())->calculate($sample['calculationDate'])) { throw new \InvalidArgumentException('INVALID_DAILY_SAMPLE_PILLAR'); }
            $pa=$this->person($a,$day); $pb=$this->person($b,$day); $pair=[];
            foreach (self::CATEGORIES as $cat) {
                $pair[$cat]=$pa[$cat]===null||$pb[$cat]===null?null:['source'=>'SAJU',
                    'signal'=>$pa[$cat]['signal']->add($pb[$cat]['signal'])->divide(R::of(2)),
                    'confidence'=>$pa[$cat]['confidence']->add($pb[$cat]['confidence'])->divide(R::of(2))];
            }
            $slots[]=$pair;
        }
        return $slots;
    }
}
