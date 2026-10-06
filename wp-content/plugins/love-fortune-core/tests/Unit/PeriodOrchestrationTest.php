<?php
declare(strict_types=1);
use LoveFortune\Core\Application\Score\{PeriodCalendar as C,PeriodOrchestrationService as P};
use LoveFortune\Core\Domain\Score\PeriodStatistics as S;
use LoveFortune\Core\Support\Rational as R;
use LoveFortune\Core\Api\{PeriodProjection,PeriodResultValidation as V,PeriodInput,InterpretationContext,PublicError,CanonicalJson};

final class PeriodOrchestrationTest extends \PHPUnit\Framework\TestCase
{
    private function period(array $scores,string $baseline='50',string $type='WEEK',array $confidence=[],array $coverage=[],array $fallback=[]): array
    {
        $dates=C::membership($type,$type==='WEEK'?['weekStartDate'=>'2024-09-02']:['year'=>2024,'month'=>2]);$rows=[];
        foreach($dates as $i=>$date){$rows[$date]=['score'=>isset($scores[$i])?R::of($scores[$i]):null,'coverage'=>R::of($coverage[$i]??'1'),'resultConfidence'=>R::of($confidence[$i]??'0.8'),'dailyStatus'=>in_array($i,$fallback,true)?'INSUFFICIENT_PERIOD_DATA':'STABLE'];}
        return (new P())->aggregate($type,$dates,$rows,R::of($baseline),'Asia/Tokyo');
    }
    public function testCalendarMembershipAndBoundaries(): void
    {
        foreach([[2023,2,28],[2024,2,29],[2024,4,30],[2024,1,31]] as [$y,$m,$n]){self::assertCount($n,C::membership('MONTH',['year'=>$y,'month'=>$m]));}
        self::assertCount(12,C::membership('YEAR',['year'=>2024]));self::assertSame('2025-01-05',C::membership('WEEK',['weekStartDate'=>'2024-12-30'])[6]);
        foreach([['2024-01-02','2024-01-01','INVALID_DATE_RANGE'],['2024-01-01','2024-02-01','DATE_RANGE_TOO_LARGE']] as [$a,$b,$error]){try{C::dates($a,$b,31);self::fail('Invalid range accepted');}catch(InvalidArgumentException $e){self::assertSame($error,$e->getMessage());}}
        $this->expectException(InvalidArgumentException::class);C::membership('WEEK',['weekStartDate'=>'2024-09-03']);
    }
    public function testApprovedMeanNoSecondPeakAndClamp(): void
    {
        foreach([[array_fill(0,7,69),'60','69/1','9/1'],[[50,50,50,50,50,50,68],'50','368/7','18/7'],[[95,95,95,95,95,95,100],'95','670/7','5/7']] as [$values,$base,$score,$delta]){$r=$this->period($values,$base);self::assertSame($score,(string)$r['score']);self::assertSame($delta,(string)$r['periodDelta']);self::assertSame('4/5',(string)$r['resultConfidence']);self::assertSame('1/1',(string)$r['coverage']);V::validate(PeriodProjection::project($r,str_repeat('a',32)));}
    }
    public function testPartialFallbackCoverageAndEmpty(): void
    {
        foreach([[[1,1,1,1,1,0,0],'5/7'],[[1,1,1,1,1,'3/4','1/4'],'6/7']] as [$coverage,$expected]){self::assertSame($expected,(string)$this->period(array_fill(0,7,69),'60','WEEK',[],$coverage,[5,6])['coverage']);}
        $r=$this->period(array_fill(0,7,69),'60','WEEK',[],[1,1,1,1,1,'0.5','0.25'],[5,6]);self::assertSame('69/1',(string)$r['score']);self::assertSame('23/28',(string)$r['coverage']);self::assertSame('4/5',(string)$r['resultConfidence']);self::assertTrue(V::needsSignature(PeriodProjection::project($r,str_repeat('a',32))));
        $r=$this->period([]);self::assertNull($r['score']);self::assertSame('1/1',(string)$r['coverage']);self::assertSame('0/1',(string)$r['resultConfidence']);self::assertNull($r['variance']);self::assertSame(['best'=>[],'caution'=>[]],$r['rankings']);$wire=PeriodProjection::project($r,str_repeat('a',32));V::validate($wire);self::assertFalse(V::needsSignature($wire));
        $r=$this->period([69]);self::assertSame('0/1',(string)$r['variance']);self::assertNull($r['slope']);self::assertCount(1,$r['rankings']['best']);self::assertSame([],$r['rankings']['caution']);
    }
    public function testTrendBeforeWireAndCanonicalStatus(): void
    {
        foreach([['2.99996','STABLE'],['7.99996','NOTICEABLE'],['3','NOTICEABLE'],['8','SIGNIFICANT']] as [$v,$trend]){foreach([1,-1] as $sign){$delta=R::of($v)->multiply(R::of($sign));$r=$this->period([(string)R::of(50)->add($delta)]);self::assertSame($trend,$r['trendStatus']);$wire=PeriodProjection::project($r,str_repeat('a',32));V::validate($wire);self::assertSame((float)$delta->halfUp4(),$wire['periodDelta']);}}
        self::assertSame('BALANCED',S::status(R::of('44.99996')));self::assertSame('GOOD',S::status(R::of('59.99996')));
    }
    public function testExactVolatilityAndSlopeWithCalendarGaps(): void
    {
        foreach([['0','0.0000'],['2','1.4142'],['2/3','0.8165'],['2500','50.0000'],['1/400000000','0.0001'],['1/400000001','0.0000']] as [$v,$golden]){self::assertSame($golden,S::sqrtHalfUp4(R::of($v)));}
        self::assertSame('2/3',(string)S::variance([R::of(0),R::of(1),R::of(2)]));
        $r=$this->period([0=>50,6=>68]);self::assertSame('3/1',(string)$r['slope']);self::assertSame('81/1',(string)$r['variance']);
    }
    public function testYearlyEqualMonthsAndRankingNonOverlap(): void
    {
        $r=$this->period([0,100],'50','YEAR',['0.2','0.8'],array_fill(0,12,'0.5'));self::assertSame('50/1',(string)$r['score']);self::assertSame('1/2',(string)$r['coverage']);self::assertSame('1/2',(string)$r['resultConfidence']);self::assertSame('2024-02-01',$r['rankings']['best'][0]['date']);self::assertSame([],$r['rankings']['caution']);
        $r=$this->period([69,69,69,69,69,69,69],'60','WEEK',['0.8','0.9','0.9']);self::assertSame(['2024-09-03','2024-09-04'],array_column($r['rankings']['best'],'date'));self::assertSame(['2024-09-02','2024-09-05'],array_column($r['rankings']['caution'],'date'));
        $r=$this->period(['69.00001','69.00002']);self::assertSame('2024-09-03',$r['rankings']['best'][0]['date']);
    }
    public function testSigningAndValidMacWrongConfigRejected(): void
    {
        $key='SYNTHETIC_PERIOD_KEY_NOT_FOR_PRODUCTION_0001';$s=new InterpretationContext('period',['period'=>$key]);
        foreach(['WEEK','MONTH','YEAR'] as $type){$wire=PeriodProjection::project($this->period([69],'60',$type),str_repeat('a',32));$t=$s->issue($wire,'ko-KR',1800000000);$payload=$s->verify($t,'ko-KR',1800000001);self::assertSame('CONFIG_PERIOD_V1',$payload['configVersion']);self::assertSame([],$payload['evidence']);
            foreach(['CONFIG_DAILY_V1','CONFIG_COMBINED_LIFETIME_V1'] as $badConfig){$p=$payload;$p['configVersion']=$p['result']['meta']['versions']['configVersion']=$badConfig;$parts=explode('.',$t);$parts[1]=rtrim(strtr(base64_encode(CanonicalJson::encode($p)),'+/','-_'),'=');$msg=$parts[0].'.'.$parts[1];$bad=$msg.'.'.rtrim(strtr(base64_encode(hash_hmac('sha256',$msg,$key,true)),'+/','-_'),'=');try{$s->verify($bad,'ko-KR',1800000001);self::fail('Cross-config accepted');}catch(PublicError $e){self::assertSame('INVALID_INTERPRETATION_CONTEXT',$e->publicCode);}}
            foreach([[$t,1800000300,'INTERPRETATION_CONTEXT_EXPIRED'],[substr($t,0,-4).'aaaa',1800000001,'INVALID_INTERPRETATION_CONTEXT']] as [$token,$at,$error]){try{$s->verify($token,'ko-KR',$at);self::fail('Bad token accepted');}catch(PublicError $e){self::assertSame($error,$e->publicCode);}}
        }
    }
    public function testInvalidChildFailsWholePeriod(): void
    {
        $dates=C::membership('WEEK',['weekStartDate'=>'2024-09-02']);$rows=array_fill_keys($dates,['score'=>R::of(60),'coverage'=>R::of(1),'resultConfidence'=>R::of(1),'dailyStatus'=>'ERROR']);$this->expectException(InvalidArgumentException::class);(new P())->aggregate('WEEK',$dates,$rows,R::of(50),'Asia/Tokyo');
    }
}
