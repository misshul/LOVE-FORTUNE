<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

use LoveFortune\Core\Application\Score\{CombinedLifetimeService, LifetimeSourceEnvelope as E};
use LoveFortune\Core\Domain\Score\CategoryResult as C;
use LoveFortune\Core\Engine\Saju\{NatalResolutionService, SajuFeatureExtractionService, SajuLifetimeScoringService};
use LoveFortune\Core\Engine\Zodiac\{ZodiacScorer, ZodiacDateResolver, ZodiacPairResolver};
use LoveFortune\Core\Support\Rational as R;
use PHPUnit\Framework\TestCase;

final class CombinedLifetimeTest extends TestCase
{
    private function row(?string $score='70', string $coverage='1', string $confidence='1'): C
    { return new C($score===null?null:R::of($score), R::of($coverage), R::of($confidence)); }
    private function envelope(string $source, C $row): E
    { return E::fromEligible($source,E::IDENTITIES[$source],array_fill_keys(E::ELIGIBLE[$source],$row)); }
    private function eq(string $value, R $actual): void { self::assertSame((string)R::of($value),(string)$actual); }
    private function error(callable $call): void
    { try { $call(); } catch (InvalidArgumentException $e) { self::assertNotEmpty($e->getMessage()); return; } self::fail('Expected closed error'); }
    private function pack(C $row): array
    { return ['score'=>$row->score===null?null:(string)$row->score,'cov'=>(string)$row->coverage,'q'=>(string)$row->confidence,'api'=>$row->score?->halfUp4(),'status'=>$row->status()]; }

    public function testCategoryGoldens(): void
    {
        $service=new CombinedLifetimeService();$empty=C::unavailable();
        foreach ([[$this->row(),$this->row('50','1','3/4'),'66','1','19/20'],
            [$this->row(),$empty,'66','4/5','4/5'],[$empty,$this->row('50','1','3/4'),'50','1/5','3/20'],
            [$empty,$empty,null,'0','0']] as [$s,$z,$score,$cov,$conf]) {
            $result=$service->score([$this->envelope('SAJU',$s),$this->envelope('ZODIAC',$z)]);
            $r=$result['categories']['ATTRACTION'];self::assertSame($score===null?null:(string)R::of($score),$r->score===null?null:(string)$r->score);
            $this->eq($cov,$r->coverage);$this->eq($conf,$r->confidence);
            self::assertEquals($s,$result['categories']['STABILITY']);
            self::assertEquals($empty,$result['categories']['COMMUNICATION']);
        }
    }

    public function testPartialCoverageAndZeroConfidence(): void
    {
        $service=new CombinedLifetimeService();
        $r=$service->score([$this->envelope('SAJU',$this->row('70','1/2','1/2')),$this->envelope('ZODIAC',$this->row('50','1','3/4'))])['categories']['EMOTION'];
        $this->eq('66',$r->score);$this->eq('3/5',$r->coverage);$this->eq('11/20',$r->confidence);$this->eq('11/12',$r->preCoverageConfidence());
        $r=$service->score([$this->envelope('SAJU',$this->row('70','1','0')),$this->envelope('ZODIAC',C::unavailable())])['categories']['EMOTION'];
        $this->eq('66',$r->score);$this->eq('4/5',$r->coverage);$this->eq('0',$r->confidence);
    }

    public function testOverallDenominatorsAndNullCoverage(): void
    {
        $service=new CombinedLifetimeService();$s=$this->envelope('SAJU',$this->row());$z=$this->envelope('ZODIAC',$this->row());
        $all=$service->score([$s,$z]);$this->eq('70',$all['overall']->score);$this->eq('1',$all['overall']->coverage);$this->eq('1',$all['overall']->confidence);
        $categories=$s->categories;$categories['STABILITY']=C::unavailable();
        $r=$service->score([new E('SAJU',$s->versions,$categories),$z]);
        $this->eq('70',$r['overall']->score);$this->eq('71/86',$r['overall']->coverage);$this->eq('71/86',$r['overall']->confidence);$this->eq('1',$r['overallPreCoverageConfidence']);
        $r=$service->score([$this->envelope('SAJU',C::unavailable(R::of(1))),$this->envelope('ZODIAC',C::unavailable(R::of(1)))]);
        self::assertNull($r['overall']->score);self::assertSame('INSUFFICIENT_DATA',$r['overall']->status());$this->eq('1',$r['overall']->coverage);$this->eq('0',$r['overall']->confidence);
    }

    public function testNormalizationAndInvalidShapes(): void
    {
        foreach (['SAJU','ZODIAC'] as $id) {
            $rows=array_fill_keys(E::ELIGIBLE[$id],$this->row());$e=E::fromEligible($id,E::IDENTITIES[$id],$rows);
            self::assertSame(E::CATEGORIES,array_keys($e->categories));self::assertSame(E::ELIGIBLE[$id],array_keys($e->blenderCategories()));
            foreach (array_diff(E::CATEGORIES,E::ELIGIBLE[$id]) as $cat) { self::assertEquals(C::unavailable(),$e->categories[$cat]); }
            $bad=$rows;unset($bad['EMOTION']);$this->error(fn()=>E::fromEligible($id,E::IDENTITIES[$id],$bad));
            $bad=$rows;$bad['UNKNOWN']=$this->row();$this->error(fn()=>E::fromEligible($id,E::IDENTITIES[$id],$bad));
            $list=[['category'=>'EMOTION','result'=>$this->row()],['category'=>'EMOTION','result'=>$this->row()]];
            $this->error(fn()=>E::fromEligible($id,E::IDENTITIES[$id],$list));
            $bad=$e->categories;unset($bad['COMMUNICATION']);$this->error(fn()=>new E($id,E::IDENTITIES[$id],$bad));
            $bad=$e->categories;$bad['COMMUNICATION']=$this->row();$this->error(fn()=>new E($id,E::IDENTITIES[$id],$bad));
            $bad=$e->categories;$bad['EMOTION']=['score'=>70,'status'=>'CAUTION'];$this->error(fn()=>new E($id,E::IDENTITIES[$id],$bad));
        }
        foreach ([fn()=>$this->row('101'),fn()=>$this->row('70','2'),fn()=>$this->row('70','1','2'),fn()=>$this->row(null,'1','1')] as $call) { $this->error($call); }
    }

    public function testVersionAndSourceFailures(): void
    {
        $this->error(fn()=>new CombinedLifetimeService('WRONG'));
        foreach (CombinedLifetimeService::DEPENDENCIES as $key=>$_) {
            $deps=CombinedLifetimeService::DEPENDENCIES;$deps[$key]='WRONG';$this->error(fn()=>new CombinedLifetimeService(dependencies:$deps));
        }
        foreach (E::IDENTITIES as $id=>$identity) {
            foreach ($identity as $key=>$_) {
                $bad=$identity;$bad[$key]='WRONG';$this->error(fn()=>E::fromEligible($id,$bad,array_fill_keys(E::ELIGIBLE[$id],$this->row())));
            }
            $bad=$identity;$bad['contractVersion']='WRONG';$this->error(fn()=>E::fromEligible($id,$bad,array_fill_keys(E::ELIGIBLE[$id],$this->row())));
        }
        $this->error(fn()=>new E('UNKNOWN',[],[]));$service=new CombinedLifetimeService();$s=$this->envelope('SAJU',$this->row());
        foreach ([[],[$s],[$s,$s],[$s,[]],['SAJU'=>$s,'ZODIAC'=>$this->envelope('ZODIAC',$this->row())]] as $bad) { $this->error(fn()=>$service->score($bad)); }
    }

    public function testProductionNatalAndZodiacE2E(): void
    {
        $n=new NatalResolutionService();$extractor=new SajuFeatureExtractionService();$scorer=new SajuLifetimeScoringService();$combined=new CombinedLifetimeService();
        $known=$n->resolve('2020-01-15','LOC000001','12:00');$fold=$n->resolve('2020-11-01','LOC000003','01:30');
        $unknown=$n->resolve('2020-01-15','LOC000001');$lichun=$n->resolve('2000-02-04','LOC000001');
        foreach ([[$known,$known,'2020-01-15','2020-01-15'],[$fold,$known,'2020-11-01','2020-01-15'],
            [$unknown,$known,'2020-01-15','2020-01-15'],[$unknown,$lichun,'2020-01-15','2000-02-04']] as [$a,$b,$dateA,$dateB]) {
            $source=$scorer->score($extractor->extract($a,$b));$s=E::fromSaju($source);
            $signA=(new ZodiacDateResolver())->resolve($dateA)['sign'];$signB=(new ZodiacDateResolver())->resolve($dateB)['sign'];
            $context=(new ZodiacPairResolver())->resolve($signA,$signB);
            $z=E::fromEligible('ZODIAC',E::IDENTITIES['ZODIAC'],(new ZodiacScorer())->score($signA,$signB),$context);
            $saved=serialize([$s,$z]);$r=$combined->score([$s,$z]);self::assertSame($saved,serialize([$s,$z]));
            self::assertCount(8,$s->categories);self::assertCount(8,$z->categories);self::assertSame(E::CATEGORIES,array_keys($r['categories']));
            self::assertSame(CombinedLifetimeService::VERSION,$r['combinedVersion']);self::assertEquals(CombinedLifetimeService::DEPENDENCIES,$r['dependencies']);
            self::assertEquals($source['tenGods'],$r['contexts']['SAJU']['context']);self::assertEquals($context,$r['contexts']['ZODIAC']['context']);
            self::assertSame($z->versions,$r['contexts']['ZODIAC']['versions']);self::assertNotNull($r['overall']->score);
            foreach ($r['categories'] as $row) { self::assertInstanceOf(C::class,$row);self::assertNotEmpty($row->status()); }
            self::assertSame(serialize($r),serialize($combined->score([$z,$s])));
            $shuffled=new E('ZODIAC',array_reverse($z->versions,true),array_reverse($z->categories,true),array_reverse($context,true));
            self::assertSame(serialize($r),serialize($combined->score([$shuffled,$s])));
            $bad=$source;$bad['scoringVersion']='WRONG';$this->error(fn()=>E::fromSaju($bad));
            $bad=$source;unset($bad['categories']['EMOTION']);$this->error(fn()=>E::fromSaju($bad));
        }
    }

    public function testContextBindingAndNumericIndependence(): void
    {
        $context=(new ZodiacPairResolver())->resolve('ARIES','TAURUS');$s=$this->envelope('SAJU',$this->row());$z=$this->envelope('ZODIAC',$this->row());
        $with=new E('ZODIAC',$z->versions,$z->categories,$context);$service=new CombinedLifetimeService();
        $a=$service->score([$s,$z]);$b=$service->score([$s,$with]);self::assertEquals($a['categories'],$b['categories']);self::assertEquals($a['overall'],$b['overall']);
        foreach (['source','contextRole','modelVersion','dateRangeVersion'] as $key) { $bad=$context;$bad[$key]='WRONG';$this->error(fn()=>new E('ZODIAC',$z->versions,$z->categories,$bad)); }
    }

    public function testAllExistingLifetimeGoldensThroughWrapper(): void
    {
        $fixture=json_decode(file_get_contents(dirname(__DIR__).'/fixtures/zodiac-golden.json'),true,512,JSON_THROW_ON_ERROR);self::assertCount(188,$fixture['cases']);
        foreach ($fixture['cases'] as $case) {
            $rows=[];foreach ($case['saju'] as $cat=>$r) { $rows[$cat]=$this->row($r['score'],$r['coverage'],$r['confidence']); }
            $s=E::fromEligible('SAJU',E::IDENTITIES['SAJU'],$rows);
            $z=$case['zodiacAvailable']?E::fromEligible('ZODIAC',E::IDENTITIES['ZODIAC'],(new ZodiacScorer())->score(...$case['signs'])):$this->envelope('ZODIAC',C::unavailable());
            $r=(new CombinedLifetimeService())->score([$s,$z]);
            self::assertEquals($case['expected']['categories'],array_values(array_map($this->pack(...),$r['categories'])));
            self::assertEquals($case['expected']['overall'],$this->pack($r['overall'])+['literalQ'=>(string)$r['overallPreCoverageConfidence']]);
        }
    }

    public function testCanonicalStatusesAndPublicRegistry(): void
    {
        foreach ([[45,'BALANCED','CAUTION'],[60,'GOOD','BALANCED'],[75,'VERY_GOOD','GOOD'],[85,'EXCELLENT','VERY_GOOD']] as [$threshold,$at,$below]) {
            foreach ([['-0.000051',$below],['-0.00005',$at],['0',$at],['0.000001',$at]] as [$delta,$status]) {
                $value=R::of($threshold)->add(R::of($delta));$row=new C($value,R::of(1),R::of(1));
                $r=(new CombinedLifetimeService())->score([$this->envelope('SAJU',$row),$this->envelope('ZODIAC',$row)]);
                self::assertSame($status,$r['overall']->status());self::assertEquals($value,$r['overall']->score);
            }
        }
        $config=require dirname(__DIR__,2).'/config/bootstrap.php';self::assertInstanceOf(CombinedLifetimeService::class,$config['engines']['score.lifetime_combined']);
        self::assertSame(CombinedLifetimeService::VERSION,$config['versions']['score.lifetime_combined']);self::assertSame('SCORE_COMBINED_LIFETIME_V1',$config['versions']['scoreVersion']);
    }
}
