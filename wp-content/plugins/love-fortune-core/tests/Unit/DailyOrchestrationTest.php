<?php
declare(strict_types=1);
use LoveFortune\Core\Application\Score\{DailyOrchestrationService as D,CombinedLifetimeService as C,LifetimeSourceEnvelope as E};
use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Engine\Saju\{FrozenDailySampleResolver as F,NatalCivilTime,NatalResolutionService,SajuDailyEvaluator,TimezoneReferenceRepository};
use LoveFortune\Core\Api\{DailyEndpoint,DailyCalculation,DailyProjection,DailyRelease,DailyResultValidation,PublicResultValidation,CompatibilityCalculation,InterpretationContext,CanonicalJson,PublicError};
use LoveFortune\Core\Support\Rational as R;

final class DailyOrchestrationTest extends \PHPUnit\Framework\TestCase
{
    private const KEY='SYNTHETIC_DAILY_KEY_NOT_FOR_PRODUCTION_0001';
    private function baseline(?string $score='60'): array
    {
        $row=$score===null?CategoryResult::unavailable(R::of('1/2')):new CategoryResult(R::of($score),R::of('1/2'),R::of('1/4'));
        $cats=array_fill_keys(E::CATEGORIES,$row);$cats['COMMUNICATION']=CategoryResult::unavailable();
        return ['combinedVersion'=>C::VERSION,'dependencies'=>C::DEPENDENCIES,'overall'=>$row,'categories'=>$cats,'contexts'=>[]];
    }
    private function slots(array $values,bool $supportOnly=false): array
    {
        return array_map(static function($v)use($supportOnly){$slot=[];foreach(array_keys(D::WEIGHTS) as $c){$slot[$c]=$v===null||($supportOnly&&$c!=='SUPPORT')?null:['source'=>'SAJU','signal'=>R::of($v),'confidence'=>R::of(1)];}return $slot;},$values);
    }
    private function runDaily(array $values,?string $base='60',bool $support=false): array{return (new D())->aggregate($this->baseline($base),$this->slots($values,$support),'2024-09-02','Asia/Tokyo');}
    private function input(): array{return ['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001','birthTime'=>null],'relationshipType'=>'UNKNOWN','date'=>'2024-09-02','targetTimezone'=>'Asia/Tokyo','locale'=>'ko-KR'];}
    private function signer(): InterpretationContext{return new InterpretationContext('daily',['daily'=>self::KEY]);}

    public function testExactTemporalCoverageConfidenceAndNoDoublePenalty(): void
    {
        foreach([[[1,1,1,1],'1/1'],[[1,1,1,null],'3/4'],[[1,null,null,null],'1/4'],[[null,null,null,null],'0/1']] as [$slots,$expected]){
            $r=$this->runDaily($slots);foreach(['coverage','resultConfidence'] as $key){self::assertSame($expected,(string)$r['overall'][$key]);self::assertSame($expected,(string)$r['categories']['SUPPORT'][$key]);}
        }
        foreach([[[1,0,0,0],'7/16'],[[-1,0,0,0],'-7/16'],[[1,-1,0,0],'1/4'],[[1,null,null,null],'1/1'],[[0,0,0,0],'0/1']] as [$slots,$expected]){self::assertSame($expected,(string)$this->runDaily($slots)['overall']['signal']);}
        $r=$this->runDaily([1,1,1,1],'60',true);self::assertSame('1/11',(string)$r['overall']['coverage']);self::assertSame('1/1',(string)$r['overall']['resultConfidence']);
        DailyResultValidation::validate(DailyProjection::project($r,str_repeat('a',32)));
    }
    public function testBaselineNullClampAndIneligibleCategories(): void
    {
        $r=$this->runDaily(['1/2','1/2','1/2','1/2']);self::assertSame('9/1',(string)$r['overall']['delta']);self::assertSame('69/1',(string)$r['overall']['score']);
        $r=$this->runDaily([-1,-1,-1,-1],'5');self::assertSame('-18/1',(string)$r['overall']['delta']);self::assertSame('0/1',(string)$r['overall']['score']);
        $r=$this->runDaily([1,1,1,1],null);self::assertNull($r['overall']['score']);self::assertSame('0/1',(string)$r['overall']['delta']);self::assertSame('1/1',(string)$r['overall']['coverage']);self::assertSame('INSUFFICIENT_DATA',$r['overall']['dailyStatus']);
        $r=$this->runDaily([null,null,null,null]);self::assertSame('60/1',(string)$r['overall']['score']);self::assertSame('INSUFFICIENT_PERIOD_DATA',$r['overall']['dailyStatus']);
        $r=$this->runDaily([1,1,1,1]);self::assertSame('60/1',(string)$r['categories']['LONG_TERM']['score']);self::assertNull($r['categories']['LONG_TERM']['signal']);self::assertSame('STRUCTURALLY_INELIGIBLE_FOR_DAILY',$r['categories']['LONG_TERM']['availability']);self::assertSame('0/1',(string)$r['categories']['LONG_TERM']['coverage']);
        self::assertNull($r['categories']['COMMUNICATION']['score']);self::assertSame('1/1',(string)$r['categories']['COMMUNICATION']['signal']);self::assertSame('0/1',(string)$r['categories']['COMMUNICATION']['resultConfidence']);
    }
    public function testOverallOrderAndStatusBoundaries(): void
    {
        $slots=$this->slots([0,0,0,0]);$slots[0]['ATTRACTION']['signal']=R::of(1);$slots[1]['EMOTION']['signal']=R::of(1);
        $r=(new D())->aggregate($this->baseline(),$slots,'2024-09-02','Asia/Tokyo');
        self::assertSame('155/1408',(string)$r['overall']['signal']);
        $wrong=$r['categories']['ATTRACTION']['signal']->multiply(R::of('0.12'))->add($r['categories']['EMOTION']['signal']->multiply(R::of('0.17')))->divide(R::of('0.88'));
        self::assertNotSame((string)$wrong,(string)$r['overall']['signal']);
        foreach(['-5'=>'VERY_LOW','-4.9999'=>'LOW','-2'=>'LOW','-1.9999'=>'STABLE','1.9999'=>'STABLE','2'=>'GOOD','4.9999'=>'GOOD','5'=>'VERY_GOOD','1.99995'=>'GOOD','-4.99995'=>'VERY_LOW'] as $v=>$status){self::assertSame($status,D::status(R::of((string)$v)));}
    }
    public function testFrozenAliasesDstHistoricalBoundaryAndRange(): void
    {
        $f=new F();self::assertSame('Asia/Tokyo',$f->canonicalize('Japan'));self::assertSame('Asia/Seoul',$f->canonicalize('ROK'));
        foreach([['2024-03-10','02:30','America/New_York','2024-03-10T07:00:00'],['2024-11-03','01:30','America/New_York','2024-11-03T05:30:00'],['2024-10-06','02:15','Australia/Lord_Howe','2024-10-05T15:30:00'],['2011-12-30','12:00','Pacific/Apia','2011-12-30T10:00:00']] as [$date,$time,$zone,$expected]){self::assertSame($expected,NatalCivilTime::datetime($f->resolve($date,$time,$zone))->format('Y-m-d\TH:i:s'));}
        foreach(['2024-03-10','2024-11-03'] as $d){self::assertCount(4,$f->samples($d,'America/New_York'));}
        $apia=$f->samples('2011-12-30','Pacific/Apia');self::assertCount(4,$apia);self::assertCount(1,array_unique(array_column($apia,'instantUs')));
        foreach([['2019-01-27','23:29','2019-01-27'],['2019-01-27','23:30','2019-01-28'],['1955-01-01','23:00','1955-01-02']] as [$d,$t,$expected]){self::assertSame($expected,$f->day($f->resolve($d,$t,'Asia/Seoul'))['calculationDate']);}
        foreach(['1900-01-01','2099-12-31'] as $d){self::assertCount(4,$f->samples($d,'America/New_York'));}
        $old=date_default_timezone_get();try{$a=$f->samples('2024-09-02','Japan');date_default_timezone_set('Pacific/Honolulu');self::assertSame($a,$f->samples('2024-09-02','Asia/Tokyo'));}finally{date_default_timezone_set($old);}
        try{$f->canonicalize('Europe/Paris');self::fail('Unsupported zone accepted');}catch(InvalidArgumentException $e){self::assertSame('UNSUPPORTED_TIMEZONE',$e->getMessage());}
    }
    public function testNatalCandidateMultiplicityFeatureFirstAndFailure(): void
    {
        $n=new NatalResolutionService();$e=new SajuDailyEvaluator();$a=$n->resolve('2000-02-04','LOC000001',null);$b=$n->resolve('2020-11-01','LOC000003','01:30');
        self::assertGreaterThan(1,count($a['candidates']));self::assertCount(count($a['candidates']),$e->candidates($a));self::assertCount(2,$e->candidates($b));
        $samples=(new F())->samples('2024-09-02','Asia/Tokyo');$r=$e->evaluate($a,$b,$samples);self::assertEquals($r,$e->evaluate($b,$a,$samples));self::assertCount(4,$r);
        $double=$a;$double['candidates']=array_merge($a['candidates'],$a['candidates']);foreach($double['candidates'] as $i=>&$c){$c['candidateId']='C'.$i;}unset($c);
        self::assertCount(2*count($a['candidates']),$e->candidates($double));self::assertEquals($r,$e->evaluate($double,$b,$samples));
        $gap=$n->resolve('2024-03-10','LOC000003','02:30');foreach($e->evaluate($gap,$b,$samples) as $s){self::assertSame(array_fill_keys(SajuDailyEvaluator::CATEGORIES,null),$s);}
        $bad=$a;$bad['candidates'][0]['dayPillar']['pillar']['stemIndex']=99;
        $this->expectException(InvalidArgumentException::class);$e->evaluate($bad,$b,$samples);
    }
    public function testIndependentFeatureFirstGolden(): void
    {
        // Approved epoch: Jan27 JiaZi, Jan28 YiChou. Whole-date unknown has two equal-unit candidates.
        // Emotion: BRANCH_SAME .3*1.2/2 and LIUHE .4*1/2 => 19/55, not mean(.3,.4)=7/20.
        $n=(new NatalResolutionService())->resolve('2019-01-27','LOC000001',null);
        self::assertCount(2,$n['candidates']);
        $slots=(new SajuDailyEvaluator())->evaluate($n,$n,(new F())->samples('2019-01-27','Asia/Seoul'));
        foreach($slots as $slot){
            self::assertSame('19/55',(string)$slot['EMOTION']['signal']);
            self::assertSame('1/2',(string)$slot['EMOTION']['confidence']);
            self::assertSame('1/5',(string)$slot['STABILITY']['signal']);
            self::assertSame('3/4',(string)$slot['STABILITY']['confidence']);
            self::assertSame('3/5',(string)$slot['HARMONY']['signal']);
            self::assertSame('1/2',(string)$slot['HARMONY']['confidence']);
            self::assertSame('0/1',(string)$slot['ATTRACTION']['signal']);
            self::assertSame('1/1',(string)$slot['ATTRACTION']['confidence']);
        }
    }
    public function testRealPipelineExactBaselineAndDeterminism(): void
    {
        $input=$this->input();$base=(new CompatibilityCalculation())->internal($input);$d=(new D())->calculate($base['combined'],...[$base['natal'][0],$base['natal'][1],$input['date'],$input['targetTimezone']]);
        self::assertSame($base['combined']['overall']->score,$d['overall']['baselineScore']);
        $r=(new DailyCalculation())->calculate($input,str_repeat('a',32));self::assertSame(DailyProjection::project($d,str_repeat('a',32)),$r);self::assertSame($r,(new DailyCalculation())->calculate($input,str_repeat('a',32)));self::assertSame([],$r['features']);self::assertNull($r['categoryScores']['COMMUNICATION']);DailyResultValidation::validate($r);
        foreach(['birthDate','birthTime','sampleRef','candidateId','lineage','tenGods'] as $f){self::assertStringNotContainsString('"'.$f.'"',CanonicalJson::encode($r));}
    }
    public function testDailySigningBindingAndFallbackPolicies(): void
    {
        $s=$this->signer();$r=(new DailyCalculation())->calculate($this->input(),str_repeat('a',32));$token=$s->issue($r,'ko-KR',1800000000);$p=$s->verify($token,'ko-KR',1800000001);self::assertSame([],$p['evidence']);self::assertSame(DailyRelease::CONFIG,$p['configVersion']);
        $bad=$r;$bad['meta']['versions']['configVersion']='CONFIG_COMBINED_LIFETIME_V1';try{DailyResultValidation::validate($bad);self::fail('Cross config accepted');}catch(InvalidArgumentException){self::assertTrue(true);}
        $compat=(new CompatibilityCalculation())->calculate($this->input(),str_repeat('a',32));$compat['meta']['versions']['configVersion']=DailyRelease::CONFIG;try{PublicResultValidation::validate($compat);self::fail('Daily config accepted by compatibility');}catch(InvalidArgumentException){self::assertTrue(true);}
        foreach([[$token,1800000300,'INTERPRETATION_CONTEXT_EXPIRED'],[substr($token,0,-4).'aaaa',1800000000,'INVALID_INTERPRETATION_CONTEXT']] as [$t,$at,$code]){try{$s->verify($t,'ko-KR',$at);self::fail('Invalid token accepted');}catch(PublicError $e){self::assertSame($code,$e->publicCode);}}
        foreach([$this->runDaily([1,1,1,1],null),$this->runDaily([null,null,null,null])] as $d){$result=DailyProjection::project($d,str_repeat('a',32));self::assertFalse(DailyResultValidation::needsSignature($result));DailyResultValidation::validate($result);}
    }
    public function testDailyHttpValidationNoStoreAndSecretless(): void
    {
        $call=function(string $body,string $type='application/json',?Closure $calc=null,?Closure $rate=null,?InterpretationContext $signer=null){$q=new WP_REST_Request('POST',DailyEndpoint::ROUTE);$q->set_header('content-type',$type);$q->set_body($body);return (new DailyEndpoint($calc,$rate??static fn()=>null,$signer??$this->signer(),static fn()=>1800000000))->handle($q);};
        $body=json_encode($this->input());$r=$call($body);self::assertSame(200,$r->get_status());self::assertArrayHasKey('signedInterpretationContext',$r->get_data());
        $cases=[['{','application/json',400],[str_repeat('x',32769),'application/json',413],['{}','text/plain',415],['{}','application/json',422]];
        foreach([['targetTimezone','Europe/Paris',422],['date','2100-01-01',422]] as [$k,$v,$status]){$x=$this->input();$x[$k]=$v;$cases[]=[json_encode($x),'application/json',$status];}
        $x=$this->input();$x['personA']['birthLocationId']='unknown';$cases[]=[json_encode($x),'application/json',404];
        foreach($cases as [$b,$t,$status]){$r=$call($b,$t);self::assertSame($status,$r->get_status());self::assertSame('no-store',$r->get_headers()['Cache-Control']);}
        $r=$call($body,signer:new InterpretationContext('',[]));self::assertSame(503,$r->get_status());self::assertSame('no-store',$r->get_headers()['Cache-Control']);
        foreach([500,429] as $status){$r=$status===500?$call($body,calc:static fn()=>throw new RuntimeException('PRIVATE')):$call($body,rate:static fn()=>throw new PublicError(429,'RATE_LIMITED',['Retry-After'=>'7']));self::assertSame($status,$r->get_status());self::assertSame('no-store',$r->get_headers()['Cache-Control']);self::assertStringNotContainsString('PRIVATE',CanonicalJson::encode($r->get_data()));}
        foreach([$this->runDaily([1,1,1,1],null),$this->runDaily([null,null,null,null])] as $d){$r=$call($body,calc:static fn($input,$id)=>DailyProjection::project($d,$id),signer:new InterpretationContext('',[]));self::assertSame(200,$r->get_status());self::assertArrayNotHasKey('signedInterpretationContext',$r->get_data());self::assertSame('no-store',$r->get_headers()['Cache-Control']);}
    }
}
