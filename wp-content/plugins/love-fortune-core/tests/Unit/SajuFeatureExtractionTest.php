<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

use LoveFortune\Core\Engine\Saju\{NatalResolutionService,SajuFeatureCatalog,SajuBranchPolarity,SajuCandidateRawExtractor,
    SajuRelationDetector,SajuEvidenceLedger,SajuFeatureAggregator,SajuCoverageProjection,SajuContextAggregator,SajuFeatureExtractionService};
use LoveFortune\Core\Support\Rational as R;
use PHPUnit\Framework\TestCase;

final class SajuFeatureExtractionTest extends TestCase
{
    private function error(string $code,callable $fn): void
    {
        try{$fn();}catch(Throwable $e){self::assertSame($code,$e->getMessage());return;}
        self::fail('Expected explicit closed failure');
    }
    private function rows(array $states,array $variants=[]): array
    {
        $rows=[];foreach($states as $i=>$s){$rows[]=['pairRef'=>'A:C0|B:C'.$i,'state'=>$s]+($s==='MATCHED'?['ruleVariant'=>$variants[$i]??'SAME']:[]);}return $rows;
    }

    public function testBranchPolarityAndClosedErrors(): void
    {
        $t=(new SajuFeatureCatalog())->tables();
        foreach($t['branches'] as $i=>$s){self::assertSame($i%2===0?'YANG':'YIN',SajuBranchPolarity::of($i,$s));
            $this->error('UNSUPPORTED_PILLAR_VALUE',fn()=>SajuBranchPolarity::of($i,$t['branches'][($i+1)%12]));}
        $this->error('UNSUPPORTED_PILLAR_VALUE',fn()=>SajuBranchPolarity::of(12,'INVALID'));
        $this->error('FEATURE_CATALOG_MISMATCH',fn()=>new SajuFeatureCatalog(__FILE__));
    }

    public function testExhaustiveStemAndBranchOwners(): void
    {
        $d=new SajuRelationDetector();$t=(new SajuFeatureCatalog())->tables();
        // Independent index tables and five-element matrix, not calls to the detector as oracle.
        $generation=[[0,1],[1,2],[2,3],[3,4],[0,4]];
        foreach([false,true] as $branch){$n=$branch?12:10;$elements=$branch?[4,2,0,0,2,1,1,2,3,3,2,4]:[0,0,1,1,2,2,3,3,4,4];
            $comb=$branch?[[0,1],[2,11],[3,10],[4,9],[5,8],[6,7]]:[[0,5],[1,6],[2,7],[3,8],[4,9]];
            $clash=[[0,6],[1,7],[2,8],[3,9],[4,10],[5,11]];
            for($a=0;$a<$n;$a++){for($b=0;$b<$n;$b++){
                $pair=[$a,$b];sort($pair);$elementPair=[$elements[$a],$elements[$b]];sort($elementPair);
                if(in_array($pair,$comb,true)){$want=$branch?['BRANCH_COMBINATION','LIUHE']:['STEM_COMBINATION','COMB'];}
                elseif($branch&&in_array($pair,$clash,true)){$want=['BRANCH_CLASH','CLASH'];}
                else{$v=$elements[$a]===$elements[$b]?'SAME':(in_array($elementPair,$generation,true)?'GENERATION':'CONTROL');
                    $want=$branch?['DAY_BRANCH_RELATION','B'.$v]:[['SAME'=>'DAY_MASTER_RELATION','GENERATION'=>'ELEMENT_SUPPORT','CONTROL'=>'ELEMENT_CONTROL'][$v],$v];}
                self::assertSame($want,$d->dayRelation($a,$b,$branch));self::assertSame($want,$d->dayRelation($b,$a,$branch));
                if(!$branch){$delta=(intdiv($b,2)-intdiv($a,2)+5)%5;$names=['SAME','PRODUCES','CONTROLS','CONTROLLED_BY','PRODUCED_BY'];
                    self::assertSame($t['tenGods'][$names[$delta]][$a%2===$b%2?0:1],$d->tenGod($a,$b));}
            }}
        }
    }

    public function testExactWholeChartBoundaries(): void
    {
        $d=new SajuRelationDetector();
        foreach(['0'=>null,'0.049999'=>null,'0.05'=>'EC1','0.149999'=>'EC1','0.15'=>'EC2','0.299999'=>'EC2','0.3'=>'EC3','0.5'=>'EC3'] as $n=>$v){self::assertSame($v,$d->band('ELEMENT_COMPLEMENT',R::of((string)$n)));}
        foreach(['0'=>'YY0','0.249999'=>'YY0','0.25'=>'YY1','0.499999'=>'YY1','0.5'=>'YY2','0.749999'=>'YY2','0.75'=>'YY3','1'=>'YY3'] as $n=>$v){self::assertSame($v,$d->band('YIN_YANG_BALANCE',R::of((string)$n)));}
        $raw=static fn(array $c,int $yang):array=>['elementCounts'=>array_combine(['WOOD','FIRE','EARTH','METAL','WATER'],$c),'knownTokenCount'=>array_sum($c),'polarityCounts'=>['YANG'=>$yang,'YIN'=>array_sum($c)-$yang]];
        $a=$raw([8,0,0,0,0],8);$b=$raw([0,8,0,0,0],0);$v=$d->wholeChart($a,$b);
        self::assertSame('1/4',(string)$v['complementGain']);self::assertSame('1/1',(string)$v['yinYangBalance']);
        self::assertSame('EC2',$v['ELEMENT_COMPLEMENT']);self::assertSame('0/1',(string)$d->wholeChart($a,$a)['complementGain']);
        foreach([[8,8,'1'],[6,8,'7/8'],[6,6,'3/4']] as [$na,$nb,$expected]){self::assertSame((string)R::of($expected),(string)$d->wholeChart($raw([$na,0,0,0,0],$na),$raw([0,$nb,0,0,0],0))['baseConfidence']);}
    }

    public function testCoverageLedgerCasesAndNoDoublePenalty(): void
    {
        $g=new SajuFeatureAggregator();
        foreach([[2,0,0,'1','1'],[1,1,0,'1','1/2'],[1,0,1,'1/2','1'],[2,1,1,'3/4','2/3'],[0,2,0,'1',null],[0,0,2,'0',null]] as [$m,$n,$u,$availability,$presence]){
            $states=array_merge(array_fill(0,$m,'MATCHED'),array_fill(0,$n,'NOT_MATCHED'),array_fill(0,$u,'UNAVAILABLE'));
            $x=$g->aggregate('DAY_MASTER_RELATION','SUPPORT',$this->rows($states),R::of(1));
            self::assertSame($m+$n+$u,$x['eligibleCount']);self::assertSame($m+$n,$x['totalCount']);self::assertSame($m,$x['presentCount']);
            self::assertSame((string)R::of($availability),(string)$x['candidateAvailabilityRatio']);self::assertSame('6/5',(string)$x['coverageEligibleWeight']);
            if($presence===null){self::assertNull($x['feature']);}else{self::assertSame((string)R::of($presence),(string)$x['feature']['confidence']);self::assertSame('2/5',(string)$x['feature']['signedValue']);}
            self::assertSame((string)R::of($availability),(string)SajuCoverageProjection::categories([$x])['SUPPORT']['coverage']);
        }
        $x=$g->aggregate('DAY_MASTER_RELATION','SUPPORT',$this->rows(['DEFERRED']),R::of(1));self::assertNull($x['candidateAvailabilityRatio']);self::assertNull($x['feature']);self::assertSame('0/1',(string)$x['coverageEligibleWeight']);
        $this->error('FEATURE_EVIDENCE_ERROR',fn()=>$g->aggregate('DAY_MASTER_RELATION','SUPPORT',$this->rows(['MATCHED','ERROR']),R::of(1)));
        $this->error('FEATURE_EVIDENCE_ERROR',fn()=>SajuEvidenceLedger::counts($this->rows(['BOGUS'])));
    }

    public function testMultiIdentityMultiplicityAndCategoryWeights(): void
    {
        $g=new SajuFeatureAggregator();$rows=$this->rows(['MATCHED','MATCHED','NOT_MATCHED','UNAVAILABLE'],['BSAME','BCONTROL']);
        $x=$g->aggregate('DAY_BRANCH_RELATION','EMOTION',$rows,R::of(1));$f=$x['feature'];
        self::assertSame(['BCONTROL','BSAME'],$f['ruleVariants']);self::assertSame('MULTI',$f['aggregateVariantType']);
        self::assertSame('1/10',(string)$f['signedValue']);self::assertSame('11/30',(string)$f['confidence']);
        self::assertSame('3/4',(string)$x['candidateAvailabilityRatio']);self::assertSame('6/5',(string)$x['coverageEligibleWeight']);
        self::assertEquals($x,$g->aggregate('DAY_BRANCH_RELATION','EMOTION',array_reverse($rows),R::of(1)));
        $rows[]=['pairRef'=>'A:C1|B:C0','state'=>'MATCHED','ruleVariant'=>'BSAME'];
        $changed=$g->aggregate('DAY_BRANCH_RELATION','EMOTION',$rows,R::of(1));
        self::assertSame($f['featureId'],$changed['feature']['featureId']);self::assertSame('1/4',(string)$changed['feature']['signedValue']);
        self::assertSame('33/80',(string)$changed['feature']['confidence']);
        $this->error('FEATURE_EVIDENCE_ERROR',fn()=>$g->aggregate('DAY_BRANCH_RELATION','SUPPORT',$this->rows(['MATCHED'],['BSAME']),R::of(1)));
    }

    public function testSixteenLegacyIdsRemainExact(): void
    {
        $fixtures=json_decode(file_get_contents(__DIR__.'/../fixtures/saju-feature-legacy-identities.json'),true,512,JSON_THROW_ON_ERROR);self::assertCount(16,$fixtures);
        foreach($fixtures as $f){$id=SajuFeatureAggregator::identity($f['ruleId'],$f['category'],[$f['metadata']['ruleVariant']]);
            self::assertSame($f['featureId'],SajuFeatureAggregator::featureId($id));
            $x=(new SajuFeatureAggregator())->aggregate($f['ruleId'],$f['category'],$this->rows(['MATCHED'],[$f['metadata']['ruleVariant']]),R::of(1));
            self::assertSame($f['featureId'],$x['feature']['featureId']);self::assertSame((string)R::of((string)$f['signedValue']),(string)$x['feature']['signedValue']);}
    }

    public function testTenGodStatesAndIndependentConfidence(): void
    {
        $a=new SajuContextAggregator();$m=['pairRef'=>'A:C0|B:C0','state'=>'MATCHED','value'=>'BI_JIAN'];$u=['pairRef'=>'A:C0|B:C1','state'=>'UNAVAILABLE'];
        self::assertSame('AGREED',$a->aggregate([$m])['state']);
        $v=$m;$v['pairRef']='A:C0|B:C1';$v['value']='JIE_CAI';$x=$a->aggregate([$m,$v]);self::assertSame('VARIANT',$x['state']);self::assertSame('1/1',(string)$x['contextConfidence']);
        $x=$a->aggregate([$m,$u]);self::assertSame('PARTIAL',$x['state']);self::assertSame('1/2',(string)$x['contextConfidence']);
        self::assertSame('UNAVAILABLE',$a->aggregate([$u])['state']);self::assertSame('0/1',(string)$a->aggregate([$u])['contextConfidence']);
        self::assertNull($a->aggregate([])['contextConfidence']);
        $bad=$m;$bad['value']='INVALID';$this->error('FEATURE_EVIDENCE_ERROR',fn()=>$a->aggregate([$bad]));
        self::assertSame('SHI_SHEN',(new SajuRelationDetector())->tenGod(0,2));self::assertSame('PIAN_YIN',(new SajuRelationDetector())->tenGod(2,0));
    }

    public function testRealNatalToFeatureE2EAndDeterminism(): void
    {
        $n=new NatalResolutionService();$s=new SajuFeatureExtractionService();
        $known=$n->resolve('2020-01-15','LOC000001','12:00');$fold=$n->resolve('2020-11-01','LOC000003','01:30');$unknown=$n->resolve('2020-01-15','LOC000001');
        foreach([[$known,$known,1,'1'],[$fold,$known,2,'1'],[$unknown,$known,2,'7/8'],[$unknown,$unknown,4,'3/4']] as [$a,$b,$count,$base]){
            $before=serialize([$a,$b]);$x=$s->extract($a,$b);self::assertSame($count,$x['candidatePairCount']);self::assertNotEmpty($x['features']);self::assertCount(4,$x['deferred']);
            foreach($x['ledger'] as $g){self::assertSame($count,$g['eligibleCount']);self::assertSame($count,$g['totalCount']);self::assertSame('1/1',(string)$g['candidateAvailabilityRatio']);
                if($g['feature']!==null && in_array($g['ruleId'],['ELEMENT_COMPLEMENT','YIN_YANG_BALANCE'],true)){self::assertSame((string)R::of($base),(string)$g['feature']['baseConfidence']);}}
            foreach($x['rawCandidates'] as $set){foreach($set as $r){self::assertSame($r['hourKnown']?8:6,$r['knownTokenCount']);self::assertSame($r['knownTokenCount'],array_sum($r['elementCounts']));self::assertSame($r['knownTokenCount'],array_sum($r['polarityCounts']));}}
            self::assertSame($count,$x['tenGods']['PERSON_A']['computableCount']);self::assertSame($before,serialize([$a,$b]));
            $a['candidates']=array_reverse($a['candidates']);$b['candidates']=array_reverse($b['candidates']);self::assertEquals($x,$s->extract($a,$b));self::assertEquals($x,$s->extract($a,$b));
            self::assertNotContains('TEN_GODS_RELATION',array_column($x['features'],'ruleId'));
        }
    }

    public function testCompleteTwoByThreeAtomicPairs(): void
    {
        $n=new NatalResolutionService();$s=new SajuFeatureExtractionService();$a=$n->resolve('2020-11-01','LOC000003','01:30');
        // Controlled complete-candidate set: retain atomic results, never splice individual pillars.
        $b=$n->resolve('2020-01-15','LOC000001','12:00');$base=$b['candidates'][0];$b['candidates']=[];
        foreach(range(0,2) as $i){$c=$base;$c['candidateId']='C'.$i;$b['candidates'][]=$c;}$b['status']='FOLD_AMBIGUOUS';
        $x=$s->extract($a,$b);self::assertSame(6,$x['candidatePairCount']);self::assertSame(['A:C0|B:C0','A:C0|B:C1','A:C0|B:C2','A:C1|B:C0','A:C1|B:C1','A:C1|B:C2'],array_column($x['pairs'],'pairRef'));
        foreach($x['ledger'] as $g){self::assertSame(6,$g['totalCount']);}self::assertSame(6,$x['tenGods']['PERSON_A']['computableCount']);
        $bad=$base;$bad['hourPillar']['dayStemIndexUsed']=($bad['dayPillar']['pillar']['stemIndex']+1)%10;
        $this->error('INVALID_CANDIDATE_LINEAGE',fn()=>(new SajuCandidateRawExtractor())->extract($bad));
    }

    public function testZeroCandidateGapHasNoFakeFeature(): void
    {
        $n=new NatalResolutionService();$gap=$n->resolve('2020-03-08','LOC000003','02:30');$known=$n->resolve('2020-01-15','LOC000001','12:00');
        $r=(new SajuFeatureExtractionService())->extract($gap,$known);self::assertSame(0,$r['candidatePairCount']);self::assertSame([],$r['features']);
        foreach($r['ledger'] as $g){self::assertNull($g['candidateAvailabilityRatio']);self::assertNull($g['feature']);self::assertSame('0/1',(string)$g['coverageAvailableWeight']);}
        self::assertSame('UNAVAILABLE',$r['tenGods']['PERSON_A']['state']);
    }

    public function testRealTwoByThreeUnknownBoundaryAndMultiFeatures(): void
    {
        $n=new NatalResolutionService();$s=new SajuFeatureExtractionService();
        $a=$n->resolve('2020-01-15','LOC000001');$b=$n->resolve('2000-02-04','LOC000001');
        self::assertCount(2,$a['candidates']);self::assertCount(3,$b['candidates']);
        $x=$s->extract($a,$b);self::assertSame(6,$x['candidatePairCount']);
        $raw=new SajuCandidateRawExtractor();
        self::assertEquals(array_map(fn(array $c):array=>$raw->extract($c),$a['candidates']),$x['rawCandidates']['PERSON_A']);
        self::assertEquals(array_map(fn(array $c):array=>$raw->extract($c),$b['candidates']),$x['rawCandidates']['PERSON_B']);
        $multi=array_values(array_filter($x['features'],fn(array $f):bool=>($f['aggregateVariantType']??null)==='MULTI'));
        self::assertNotEmpty($multi);
        foreach($multi as $f){self::assertGreaterThanOrEqual(2,count($f['ruleVariants']));self::assertArrayNotHasKey('ruleVariant',$f['metadata']??[]);}
        $a['candidates']=array_reverse($a['candidates']);$b['candidates']=array_reverse($b['candidates']);self::assertEquals($x,$s->extract($a,$b));
    }

    public function testCatalogSameVersionTamperingFailsClosed(): void
    {
        $path=tempnam(sys_get_temp_dir(),'lf-catalog-test-');
        try{
            $catalog=json_decode(file_get_contents(dirname(__DIR__,2).'/config/references/saju-rules-v1.json'),true,512,JSON_THROW_ON_ERROR);
            $catalog['rules'][0]['baseWeight']=999;
            file_put_contents($path,json_encode($catalog,JSON_THROW_ON_ERROR));
            $this->error('FEATURE_CATALOG_MISMATCH',fn()=>new SajuFeatureCatalog($path));
        }finally{unlink($path);}
    }

    public function testClosedInputAndDependencyErrors(): void
    {
        $n=(new NatalResolutionService())->resolve('2020-01-15','LOC000001','12:00');$s=new SajuFeatureExtractionService();
        $bad=$n;$bad['natalResolverVersion']='WRONG';$this->error('REFERENCE_VERSION_MISMATCH',fn()=>$s->extract($bad,$n));
        $bad=$n;$bad['candidates'][0]['yearPillar']['artifactChecksum']='WRONG';$this->error('REFERENCE_VERSION_MISMATCH',fn()=>$s->extract($bad,$n));
        $bad=$n;$bad['candidates'][0]['dayPillar']['pillar']['stem']='INVALID';$this->error('UNSUPPORTED_PILLAR_VALUE',fn()=>$s->extract($bad,$n));
        $bad=$n;$bad['candidates'][0]['monthPillar']['yearStemIndex']=99;$this->error('INVALID_CANDIDATE_LINEAGE',fn()=>$s->extract($bad,$n));
        $bad=$n;$bad['candidates'][]=$bad['candidates'][0];$bad['status']='FOLD_AMBIGUOUS';$this->error('INVALID_CANDIDATE_LINEAGE',fn()=>$s->extract($bad,$n));
        $bad=$n;$bad['candidates'][0]['candidateId']='PERSON_FINGERPRINT';$this->error('INVALID_CANDIDATE_LINEAGE',fn()=>$s->extract($bad,$n));
    }
}
