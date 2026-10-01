<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}

use LoveFortune\Core\Engine\Saju\{NatalResolutionService,SajuFeatureExtractionService,SajuFeatureAggregator,SajuCoverageProjection,SajuLifetimeScoringService};
use LoveFortune\Core\Domain\Score\{SajuCategoryScorer,CategoryResult};
use LoveFortune\Core\Support\Rational as R;
use PHPUnit\Framework\TestCase;

final class SajuLifetimeScoringTest extends TestCase
{
    private function f(string $id,string $v,string $p='1',string $c='1'): array
    {return ['featureId'=>$id,'signedValue'=>R::of($v),'preConfidenceWeight'=>R::of($p),'confidence'=>R::of($c)];}
    private function eq(string $value,?R $actual): void {self::assertNotNull($actual);self::assertSame((string)R::of($value),(string)$actual);}
    private function error(callable $f,?string $code=null): void
    {try{$f();}catch(InvalidArgumentException|RuntimeException $e){if($code!==null){self::assertSame($code,$e->getMessage());}else{self::assertNotEmpty($e->getMessage());}return;}self::fail('Expected closed error');}
    private function extraction(): array
    {$n=(new NatalResolutionService())->resolve('2020-01-15','LOC000001','12:00');return (new SajuFeatureExtractionService())->extract($n,$n);}
    private function mutateFeature(array $input,callable $change): array
    {
        $old=$input['features'][0]['featureId'];$f=$change($input['features'][0]);$input['features'][0]=$f;
        foreach($input['ledger'] as &$g){if(($g['feature']['featureId']??null)===$old){$g['feature']=$f;}}unset($g);return $input;
    }

    public function testFeCovSixCasesInScoring(): void
    {
        foreach([[2,0,0,'1','70','1'],[1,1,0,'1','70','1/2'],[1,0,1,'1/2','60','1/2'],[2,1,1,'3/4','65','1/2'],[0,2,0,'1',null,'0'],[0,0,2,'0',null,'0']] as [$m,$n,$u,$cov,$score,$confidence]){
            $rows=[];$states=array_merge(array_fill(0,$m,'MATCHED'),array_fill(0,$n,'NOT_MATCHED'),array_fill(0,$u,'UNAVAILABLE'));
            foreach($states as $i=>$state){$rows[]=['pairRef'=>'A:C0|B:C'.$i,'state'=>$state]+($state==='MATCHED'?['ruleVariant'=>'SAME']:[]);}
            $g=(new SajuFeatureAggregator())->aggregate('DAY_MASTER_RELATION','SUPPORT',$rows,R::of(1));
            $coverage=SajuCoverageProjection::categories([$g])['SUPPORT']['coverage'];
            $r=(new SajuCategoryScorer())->score($g['feature']===null?[]:[$g['feature']],$coverage);$out=$r['result'];
            $this->eq($cov,$out->coverage);$this->eq($confidence,$out->confidence);
            if($score===null){self::assertNull($out->score);self::assertSame('INSUFFICIENT_DATA',$out->status());self::assertNull($r['diagnostics']['guardedScore']);}
            else{$this->eq($score,$out->score);$this->eq('70',$r['diagnostics']['guardedScore']);}
            if($m===1&&$u===1){$this->eq('6/5',$r['diagnostics']['W0']);$this->eq('1',$r['diagnostics']['categoryResultConfidence']);$this->eq('20',array_values($r['diagnostics']['features'])[0]['impactPoints']);}
        }
    }

    public function testAvailableZeroConfidenceStaysInDenominator(): void
    {
        $s=new SajuCategoryScorer();$f=[$this->f('a','0.4'),$this->f('b','-0.5','1','0')];
        $r=$s->score($f,R::of('3/4'));$this->eq('1/2',$r['diagnostics']['categoryResultConfidence']);$this->eq('3/8',$r['result']->confidence);$this->eq('65',$r['result']->score);
        self::assertSame(['a'],$r['diagnostics']['evidenceRefs']);
        $r=$s->score([$this->f('a','0.4','1','0')],R::of(1));self::assertNull($r['result']->score);$this->eq('1',$r['result']->coverage);$this->eq('0',$r['result']->confidence);
    }

    public function testExactCapBoundariesAndNeutralSignal(): void
    {
        $s=new SajuCategoryScorer();
        foreach([['0.399999','69.99995',false],['0.4','70',false],['0.400001','70',true],['-0.4','30',false],['-0.400001','30',true],['0','50',false]] as [$v,$score,$cap]){
            $r=$s->score([$this->f('a',$v)],R::of(1));$this->eq($score,$r['result']->score);self::assertSame($cap,$r['diagnostics']['features']['a']['capped']);
            self::assertLessThanOrEqual(0,$r['diagnostics']['features']['a']['guardedImpactPoints']->compare(R::of(20)));
        }
    }

    public function testMultipleCapsFixedW0AndNeutralMass(): void
    {
        $s=new SajuCategoryScorer();$f=[$this->f('b','0.9'),$this->f('a','0.9')];$before=serialize($f);
        $r=$s->score($f,R::of('1/2'));$d=$r['diagnostics'];
        $this->eq('2',$d['W0']);$this->eq('95',$d['diagnosticRawScore']);$this->eq('90',$d['guardedScore']);$this->eq('70',$r['result']->score);$this->eq('2/9',$d['neutralMass']);
        foreach($d['features'] as $x){$this->eq('8/9',$x['adjustedWeight']);$this->eq('20',$x['guardedImpactPoints']);}
        self::assertSame(['a','b'],$d['evidenceRefs']);self::assertEquals($r,$s->score(array_reverse($f),R::of('1/2')));self::assertSame($before,serialize($f));
    }

    public function testMultiUsesAggregateNotContributions(): void
    {
        $rows=[['pairRef'=>'A:C0|B:C0','state'=>'MATCHED','ruleVariant'=>'BSAME'],['pairRef'=>'A:C0|B:C1','state'=>'MATCHED','ruleVariant'=>'BCONTROL']];
        $g=(new SajuFeatureAggregator())->aggregate('DAY_BRANCH_RELATION','EMOTION',$rows,R::of(1));$f=$g['feature'];
        self::assertSame('MULTI',$f['aggregateVariantType']);$r=(new SajuCategoryScorer())->score([$f],R::of(1));
        $this->eq('55',$r['result']->score);$this->eq('11/20',$r['result']->confidence);self::assertSame(1,$r['diagnostics']['usableFeatureCount']);
        $this->eq('33/50',$r['diagnostics']['W0']);self::assertSame([$f['featureId']],$r['diagnostics']['evidenceRefs']);
    }

    public function testCanonicalStatusAndRange(): void
    {
        foreach([[45,'BALANCED','CAUTION'],[60,'GOOD','BALANCED'],[75,'VERY_GOOD','GOOD'],[85,'EXCELLENT','VERY_GOOD']] as [$at,$status,$below]){
            foreach([['-0.000051',$below],['-0.00005',$status],['0',$status],['0.000001',$status]] as [$delta,$want]){
                $r=new CategoryResult(R::of($at)->add(R::of($delta)),R::of(1),R::of(1));self::assertSame($want,$r->status());
            }
        }
        $s=new SajuCategoryScorer();$r=$s->score([$this->f('a','0.399999')],R::of(1));self::assertSame('70.0000',$r['result']->score->halfUp4());$this->eq('69.99995',$r['result']->score);
        foreach([[$this->f('a','1.1')],[$this->f('a','0','1','-0.1')],[$this->f('a','0','-1')],[$this->f('a','0'),$this->f('a','0')]] as $f){$this->error(fn()=>$s->score($f,R::of(1)));}
        $this->error(fn()=>$s->score([$this->f('a','0.4')],R::of(0)));
    }

    public function testRealNatalFeatureScoringPipeline(): void
    {
        $n=new NatalResolutionService();$e=new SajuFeatureExtractionService();$s=new SajuLifetimeScoringService();
        $known=$n->resolve('2020-01-15','LOC000001','12:00');$fold=$n->resolve('2020-11-01','LOC000003','01:30');$unknown=$n->resolve('2020-01-15','LOC000001');$lichun=$n->resolve('2000-02-04','LOC000001');
        foreach([[$known,$known,1],[$fold,$known,2],[$unknown,$known,2],[$unknown,$lichun,6]] as [$a,$b,$count]){
            $input=$e->extract($a,$b);self::assertSame($count,$input['candidatePairCount']);$saved=serialize($input);$r=$s->score($input);
            self::assertSame('SAJU_LIFETIME_SCORING_V1',$r['scoringVersion']);self::assertSame(SajuLifetimeScoringService::DEPENDENCIES,$r['dependencies']);
            self::assertCount(7,$r['categories']);self::assertArrayNotHasKey('COMMUNICATION',$r['categories']);self::assertArrayNotHasKey('overall',$r);self::assertSame($input['tenGods'],$r['tenGods']);
            foreach($r['categories'] as $cat=>$row){self::assertInstanceOf(CategoryResult::class,$row);$this->eq((string)$input['coverage'][$cat]['coverage'],$row->coverage);
                self::assertLessThanOrEqual(0,$row->confidence->compare($row->coverage));self::assertNotEmpty($row->status());
                foreach($r['diagnostics'][$cat]['features'] as $f){self::assertLessThanOrEqual(0,$f['guardedImpactPoints']->compare(R::of(20)));}
            }
            $input['features']=array_reverse($input['features']);$input['ledger']=array_reverse($input['ledger']);self::assertEquals($r,$s->score($input));
            self::assertSame($saved,serialize($e->extract($a,$b)));
        }
        $gap=$n->resolve('2020-03-08','LOC000003','02:30');foreach($s->score($e->extract($gap,$known))['categories'] as $c){self::assertNull($c->score);$this->eq('0',$c->coverage);}
    }

    public function testEnvelopeInsufficientCases(): void
    {
        $s=new SajuLifetimeScoringService();$original=$this->extraction();
        foreach(['NOT_MATCHED','UNAVAILABLE'] as $state){
            $input=$original;$input['features']=[];$input['ledger']=[];
            foreach($original['ledger'] as $g){$input['ledger'][]=(new SajuFeatureAggregator())->aggregate($g['ruleId'],$g['category'],[['pairRef'=>'A:C0|B:C0','state'=>$state]],R::of(1));}
            $input['coverage']=SajuCoverageProjection::categories($input['ledger']);
            foreach($s->score($input)['categories'] as $r){self::assertNull($r->score);$this->eq($state==='NOT_MATCHED'?'1':'0',$r->coverage);$this->eq('0',$r->confidence);}
        }
        $input=$original;
        foreach($input['features'] as &$f){$f['confidence']=R::of(0);}unset($f);
        foreach($input['ledger'] as &$g){if($g['feature']!==null){$g['feature']['confidence']=R::of(0);}}unset($g);
        foreach($s->score($input)['categories'] as $r){self::assertNull($r->score);$this->eq('1',$r->coverage);$this->eq('0',$r->confidence);}
    }

    public function testVersionRejectionAndPublicSeparation(): void
    {
        $this->error(fn()=>new SajuLifetimeScoringService('UNKNOWN'),'SCORING_CONTRACT_MISMATCH');
        foreach(array_keys(SajuLifetimeScoringService::DEPENDENCIES) as $key){$deps=SajuLifetimeScoringService::DEPENDENCIES;$deps[$key]='WRONG';$this->error(fn()=>new SajuLifetimeScoringService(dependencies:$deps),'SCORING_CONTRACT_MISMATCH');}
        $input=$this->extraction();$input['scoreVersion']='SCORE_ZODIAC_V1';self::assertSame(SajuLifetimeScoringService::VERSION,(new SajuLifetimeScoringService())->score($input)['scoringVersion']);
        foreach(['version','catalogVersion'] as $key){$bad=$input;$bad[$key]='WRONG';$this->error(fn()=>(new SajuLifetimeScoringService())->score($bad),'SCORING_CONTRACT_MISMATCH');}
        $config=require dirname(__DIR__,2).'/config/bootstrap.php';self::assertSame('SCORE_ZODIAC_V1',$config['versions']['scoreVersion']);self::assertInstanceOf(SajuLifetimeScoringService::class,$config['engines']['saju.lifetime_scoring']);
    }

    public function testInvalidEvidenceFailsClosed(): void
    {
        $input=$this->extraction();$s=new SajuLifetimeScoringService();
        foreach(['ruleId'=>'UNKNOWN','category'=>'COMMUNICATION','subject'=>'PERSON_A','source'=>'ZODIAC','period'=>['type'=>'DAY'],
            'signedValue'=>R::of('1.1'),'confidence'=>R::of('-1'),'preConfidenceWeight'=>R::of(999),'featureId'=>'ft_wrong'] as $key=>$value){
            $bad=$this->mutateFeature($input,static function(array $f)use($key,$value):array{$f[$key]=$value;return $f;});$this->error(fn()=>$s->score($bad));
        }
        $bad=$input;$bad['ledger'][]=$bad['ledger'][0];$this->error(fn()=>$s->score($bad));
        $bad=$input;$bad['features'][]=$bad['features'][0];$this->error(fn()=>$s->score($bad));
        $bad=$input;$bad['ledger'][0]['coverageAvailableWeight']=R::of(999);$this->error(fn()=>$s->score($bad));
        $bad=$input;$bad['coverage']['HARMONY']['coverage']=R::of('1/2');$this->error(fn()=>$s->score($bad));
        $bad=$input;$bad['ledger'][0]['rows'][0]['state']='ERROR';$this->error(fn()=>$s->score($bad));
        $bad=$input;array_pop($bad['ledger']);$this->error(fn()=>$s->score($bad));
    }

    public function testMultiIdentityRejection(): void
    {
        $n=new NatalResolutionService();$input=(new SajuFeatureExtractionService())->extract($n->resolve('2020-01-15','LOC000001'),$n->resolve('2000-02-04','LOC000001'));
        $index=null;foreach($input['features'] as $i=>$f){if(($f['aggregateVariantType']??null)==='MULTI'){$index=$i;break;}}self::assertNotNull($index);
        [$input['features'][0],$input['features'][$index]]=[$input['features'][$index],$input['features'][0]];
        foreach(['identityVersion'=>'WRONG','ruleVariants'=>['BSAME'],'featureId'=>'ft_wrong'] as $key=>$value){
            $bad=$this->mutateFeature($input,static function(array $f)use($key,$value):array{$f[$key]=$value;return $f;});
            $this->error(fn()=>(new SajuLifetimeScoringService())->score($bad),'INVALID_MULTI_IDENTITY');
        }
    }
}
