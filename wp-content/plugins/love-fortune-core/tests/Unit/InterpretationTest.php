<?php
declare(strict_types=1);
use LoveFortune\Core\Api\{InterpretationEndpoint,InterpretationInput,InterpretationContext,PublicError,CoreRateLimit,CanonicalJson};
use LoveFortune\Core\Application\Interpretation\{InterpretationService,InterpretationText as T,ProviderInput,ProviderFailure};
use LoveFortune\Core\Infrastructure\Interpretation\JsonGatewayProvider;
require_once dirname(__DIR__).'/interpretation-support.php';

final class InterpretationTest extends \PHPUnit\Framework\TestCase
{
    private const KEY='SYNTHETIC_INTERPRETATION_KEY_NOT_FOR_DEPLOYMENT';
    private const NOW=1800000000;
    private function signer(): InterpretationContext{return new InterpretationContext('current',['current'=>self::KEY,'previous'=>self::KEY.'old'],'previous');}
    private function token(string $type='Daily',string $locale='ko-KR'): string{return $this->signer()->issue(interpretationResults()[$type],$locale,self::NOW);}
    private function context(string $type='Daily',string $locale='ko-KR'): array{return $this->signer()->verify($this->token($type,$locale),$locale,self::NOW);}
    private function call(array|string $body,?InterpretationService $service=null,?Closure $rate=null): WP_REST_Response
    {
        $q=new WP_REST_Request('POST',InterpretationEndpoint::ROUTE);$q->set_header('content-type','application/json');$q->set_body(is_array($body)?json_encode($body,JSON_THROW_ON_ERROR):$body);
        return (new InterpretationEndpoint($service,$rate??static fn()=>null,$this->signer(),static fn()=>self::NOW))->handle($q);
    }
    public function testFiveTypesThreeLocalesAndExactDtoPrivacy(): void
    {
        foreach(array_keys(interpretationResults()) as $type){foreach(['ko-KR','ja-JP','en-US'] as $locale){
            $provider=new InterpretationMock();$r=$this->call(['signedContext'=>$this->token($type,$locale),'locale'=>$locale],new InterpretationService($provider));
            self::assertSame(200,$r->get_status());self::assertSame('no-store',$r->get_headers()['Cache-Control']);self::assertSame('AI',$r->get_data()['meta']['interpretationMode']);self::assertSame(T::VERSION,$r->get_data()['meta']['aiPromptVersion']);
            $dto=$provider->calls[0]['dto'];self::assertSame($type,$dto['resultType']);self::assertSame($locale,$dto['locale']);
            foreach(['birthDate','birthTime','birthLocationId','name','nickname','signedContext','requestId','issuedAt','expiresAt','kid','signature','candidateId','sampleRef','dependencies','lineage','relationshipType','features','meta'] as $key){self::assertStringNotContainsString('"'.$key.'"',CanonicalJson::encode($dto));}
            if($type!=='Compatibility'){self::assertSame([],$dto['evidence']);self::assertSame([],$r->get_data()['strengths']);self::assertSame([],$r->get_data()['challenges']);}
            self::assertSame([] ,T::validate(array_diff_key($r->get_data(),['meta'=>true]),$dto));
        }}
    }
    public function testAiOffFallbackReplayAndFailureUnavailable(): void
    {
        $ctx=$this->context();$service=new InterpretationService();$a=$service->interpret($ctx,'one');$b=$service->interpret($ctx,'two');
        self::assertSame('FALLBACK',$a['meta']['interpretationMode']);self::assertNull($a['meta']['provider']);self::assertNull($a['meta']['model']);unset($a['meta'],$b['meta']);self::assertSame($a,$b);
        self::assertSame(503,$this->call(['signedContext'=>$this->token()],new InterpretationService(fallback:static fn()=>null))->get_status());
    }
    public function testContextFailuresNeverCallProvider(): void
    {
        $token=$this->token();$mutations=[
            'INVALID_INTERPRETATION_PURPOSE'=>static function($p){$p['purpose']='OTHER';return $p;},
            'INTERPRETATION_CONTEXT_EXPIRED'=>static function($p){$p['issuedAt']=gmdate('Y-m-d\TH:i:s\Z',self::NOW-300);$p['expiresAt']=gmdate('Y-m-d\TH:i:s\Z',self::NOW);return $p;},
            'INTERPRETATION_LOCALE_MISMATCH'=>static function($p){$p['locale']='ja-JP';return $p;},
            'INVALID_INTERPRETATION_CONTEXT'=>static function($p){$p['configVersion']='CONFIG_PERIOD_V1';return $p;},
        ];
        foreach($mutations as $error=>$mutate){$p=new InterpretationMock();$r=$this->call(['signedContext'=>interpretationResign($token,$mutate,self::KEY)],new InterpretationService($p));self::assertSame(422,$r->get_status());self::assertSame($error,$r->get_data()['error']['code']);self::assertCount(0,$p->calls);}
        $p=new InterpretationMock();$parts=explode('.',$token);$parts[2]=str_repeat('A',43);self::assertSame(422,$this->call(['signedContext'=>implode('.',$parts)],new InterpretationService($p))->get_status());self::assertCount(0,$p->calls);
    }
    public function testVersionBindingPreviousKeyAndAmbiguousResults(): void
    {
        foreach(interpretationResults() as $type=>$result){
            $previous=new InterpretationContext('previous',['previous'=>self::KEY.'old']);$token=$previous->issue($result,'ko-KR',self::NOW);
            self::assertSame(200,$this->call(['signedContext'=>$token])->get_status());
            foreach(['scoreVersion','configVersion'] as $field){$bad=interpretationResign($token,static function($p)use($field){$p[$field]='OLD';return $p;},self::KEY.'old');self::assertSame(422,$this->call(['signedContext'=>$bad])->get_status());}
            $bad=interpretationResign($token,static function($p){$p['result']['unapprovedResultType']='Daily-range';return $p;},self::KEY.'old');self::assertSame(422,$this->call(['signedContext'=>$bad])->get_status());
        }
    }
    public function testTransportSizeClosedFieldsAndLocale(): void
    {
        $body=json_encode(['signedContext'=>$this->token()]);self::assertSame(200,$this->call(str_pad($body,65536,' '))->get_status());self::assertSame(413,$this->call(str_pad($body,65537,' '))->get_status());
        foreach([65499=>true,65500=>false] as $n=>$ok){$token=str_repeat('a',$n-46).'.b.'.str_repeat('c',43);try{InterpretationInput::parse(json_encode(['signedContext'=>$token]),'application/json',null);self::assertTrue($ok);}catch(PublicError $e){self::assertFalse($ok);self::assertSame(422,$e->status);}}
        foreach(['question','relationshipType','tone','style','length','birthDate','provider','model'] as $field){self::assertSame(400,$this->call(['signedContext'=>$this->token(),$field=>'bad'])->get_status());}
        self::assertSame(422,$this->call(['signedContext'=>$this->token(),'locale'=>'fr-FR'])->get_status());self::assertSame(422,$this->call(['signedContext'=>$this->token('Daily','ja-JP')])->get_status());
        foreach(['{','[]','null'] as $bad){self::assertSame(400,$this->call($bad)->get_status());}
        foreach([['text/plain',null],['application/json','identity'],['application/json','']] as [$media,$encoding]){try{InterpretationInput::parse($body,$media,$encoding);self::fail();}catch(PublicError $e){self::assertSame(415,$e->status);}}
    }
    public function testCompatibilitySignedSubsetAndDirectionGrounding(): void
    {
        $ctx=$this->context('Compatibility');$dto=ProviderInput::project($ctx);$count=0;
        foreach($dto['evidence'] as $f){if(!in_array($f['direction'],['POSITIVE','NEGATIVE','MIXED'],true)){continue;}$kind=$f['direction']==='NEGATIVE'?'challenges':'strengths';$out=T::fallback($dto);$out[$kind]=[['text'=>T::evidenceText($f,$dto['locale']),'evidenceRefs'=>[$f['featureId']]]];self::assertSame([],T::validate($out,$dto));$empty=$dto;$empty['evidence']=[];self::assertNotSame([],T::validate($out,$empty));$out[$kind][0]['evidenceRefs']=['ft_unsigned'];self::assertNotSame([],T::validate($out,$dto));$count++;}
        self::assertGreaterThan(0,$count);
        // Result.features is not a substitute for the signed evidence subset.
        $token=interpretationResign($this->token('Compatibility'),static function($p){$p['evidence']=[];return $p;},self::KEY);
        $verified=$this->signer()->verify($token,'ko-KR',self::NOW);self::assertNotEmpty($verified['result']['features']);self::assertSame([],ProviderInput::project($verified)['evidence']);
    }
    public function testNumericsDatesRankingsTrendAndSafetyFailClosed(): void
    {
        $dto=ProviderInput::project($this->context('Weekly','en-US'));self::assertSame('STABLE',$dto['trendStatus']);self::assertEquals(3,$dto['periodDelta']);
        foreach(['compatibility score is 100','compatibility score is 80%','Lucky date: 2090-01-01','Trend magnitude: SIGNIFICANT','They secretly love you.','You will definitely marry.','<script>alert(1)</script>','<iframe>','onclick=bad','**good**','Moon transit predicts pregnancy.','相手は必ず結婚します。'] as $bad){$out=T::fallback($dto);$out['advice']=$bad;self::assertNotSame([],T::validate($out,$dto),$bad);}
        $out=T::fallback($dto);$out['advice']='Trend magnitude: STABLE';self::assertSame([],T::validate($out,$dto));
        $rank=array_values(array_filter(T::clauses($dto),static fn($x)=>str_starts_with($x,'Days to consider')))[0];$out['advice']=$rank;self::assertSame([],T::validate($out,$dto));$out['advice']='Days to consider in ranked order: '.implode(', ',array_reverse(array_column($dto['bestDays'],'date')));self::assertNotSame([],T::validate($out,$dto));
        $year=ProviderInput::project($this->context('Yearly','en-US'));$out=T::fallback($year);$out['advice']=array_values(array_filter(T::clauses($year),static fn($x)=>str_starts_with($x,'Months to consider')))[0];self::assertSame([],T::validate($out,$year));$out['advice']=str_replace(' (month)','-01',$out['advice']);self::assertNotSame([],T::validate($out,$year));
    }
    public function testUnicodeLengthCountsAndFraming(): void
    {
        $dto=ProviderInput::project($this->context('Compatibility'));$base=T::fallback($dto);
        foreach(['summary'=>1200,'advice'=>1600] as $field=>$max){$out=$base;$n=preg_match_all('/./us',$out[$field]);$out[$field].=str_repeat(' ',$max-$n);self::assertSame([],T::validate($out,$dto));$out[$field].=' ';self::assertNotSame([],T::validate($out,$dto));}
        $f=current(array_filter($dto['evidence'],static fn($f)=>$f['direction']==='POSITIVE'));self::assertIsArray($f);$item=['text'=>T::evidenceText($f,'ko-KR'),'evidenceRefs'=>[$f['featureId']]];$item['text'].=str_repeat(' ',500-preg_match_all('/./us',$item['text']));
        foreach(['strengths','challenges'] as $kind){$d=$dto;if($kind==='challenges'){$f['direction']='NEGATIVE';$d['evidence']=[$f];$item['text']=T::evidenceText($f,'ko-KR');$item['text'].=str_repeat(' ',500-preg_match_all('/./us',$item['text']));}
            $out=$base;$out[$kind]=array_fill(0,5,$item);self::assertSame([],T::validate($out,$d));$out[$kind][]=$item;self::assertNotSame([],T::validate($out,$d));$out[$kind]=[$item];$out[$kind][0]['text'].=' ';self::assertNotSame([],T::validate($out,$d));}
        $out=$base;$out['summary']=T::copy('ko-KR')[2];self::assertSame(['LIMITATION_FRAMING'],T::validate($out,$dto));
    }
    public function testRepairAndEveryProviderFailureFallback(): void
    {
        foreach(['NETWORK','SERVER','RATE','TIMEOUT','RESPONSE'] as $kind){$p=new InterpretationMock(static fn()=>throw new ProviderFailure($kind));$r=(new InterpretationService($p))->interpret($this->context(),'id');self::assertSame('FALLBACK',$r['meta']['interpretationMode']);self::assertCount(in_array($kind,['NETWORK','SERVER'],true)?2:1,$p->calls);}
        foreach(['{','{}','[]','{"summary":"score 100","strengths":[],"challenges":[],"advice":"bad"}'] as $bad){$p=new InterpretationMock(static fn()=>$bad);$r=(new InterpretationService($p))->interpret($this->context(),'id');self::assertSame('FALLBACK',$r['meta']['interpretationMode']);self::assertCount(2,$p->calls);self::assertNotEmpty($p->calls[1]['errors']);self::assertSame($p->calls[0]['dto'],$p->calls[1]['dto']);}
        $p=new InterpretationMock(static fn($dto,$timeout,$errors,$n)=>$n===1?'{}':CanonicalJson::encode(T::fallback($dto)));$r=(new InterpretationService($p))->interpret($this->context(),'id');self::assertSame('AI',$r['meta']['interpretationMode']);self::assertCount(2,$p->calls);
    }
    public function testSharedDeadlineAndLateSuccessRejected(): void
    {
        $time=0.0;$p=new InterpretationMock(static function($dto,$timeout,$errors,$n)use(&$time){$time+=3.0;if($n===1){throw new ProviderFailure('NETWORK');}return '{}';});
        $r=(new InterpretationService($p,static function()use(&$time){return $time;}))->interpret($this->context(),'id');self::assertSame('FALLBACK',$r['meta']['interpretationMode']);self::assertSame([6.0,5.0,2.0],array_column($p->calls,'timeout'));
        $time=0.0;$p=new InterpretationMock(static function($dto)use(&$time){$time=6.1;return CanonicalJson::encode(T::fallback($dto));});$r=(new InterpretationService($p,static function()use(&$time){return $time;}))->interpret($this->context(),'id');self::assertSame('FALLBACK',$r['meta']['interpretationMode']);self::assertCount(1,$p->calls);
    }
    public function testJsonObjectsCannotMasqueradeAsArrays(): void
    {
        $ctx=$this->context('Compatibility');$dto=ProviderInput::project($ctx);$f=current(array_filter($dto['evidence'],static fn($f)=>$f['direction']==='POSITIVE'));
        $good=T::fallback($dto);$good['strengths']=[['text'=>T::evidenceText($f,'ko-KR'),'evidenceRefs'=>[$f['featureId']]]];
        foreach(['items','refs'] as $mode){$bad=$good;if($mode==='items'){$bad['strengths']=(object)$bad['strengths'];}else{$bad['strengths'][0]['evidenceRefs']=(object)$bad['strengths'][0]['evidenceRefs'];}
            $provider=new InterpretationMock(static fn()=>json_encode($bad,JSON_THROW_ON_ERROR));$r=(new InterpretationService($provider))->interpret($ctx,'id');self::assertSame('FALLBACK',$r['meta']['interpretationMode']);self::assertCount(2,$provider->calls);}
    }
    public function testRateTenAndIsolation(): void
    {
        $dir=sys_get_temp_dir().'/lf-interpretation-test-'.bin2hex(random_bytes(6));$lim=new CoreRateLimit(self::KEY,$dir,10,'Interpretation');
        try{for($i=0;$i<10;$i++){$lim->consume('198.51.100.42',self::NOW);}try{$lim->consume('198.51.100.42',self::NOW);self::fail();}catch(PublicError $e){self::assertSame(429,$e->status);self::assertSame('600',$e->headers['Retry-After']);}
            (new CoreRateLimit(self::KEY,$dir))->consume('198.51.100.42',self::NOW);$lim->consume('198.51.100.42',self::NOW+600);self::assertTrue(true);
        }finally{foreach(glob($dir.'/*.rate')?:[] as $f){unlink($f);}rmdir($dir);}
    }
    public function testProviderUnconfiguredAndCodeOwnedPrompt(): void
    {
        $old=getenv('LOVE_FORTUNE_AI_ENABLED');try{putenv('LOVE_FORTUNE_AI_ENABLED=0');self::assertNull(JsonGatewayProvider::environment());}finally{putenv($old===false?'LOVE_FORTUNE_AI_ENABLED':'LOVE_FORTUNE_AI_ENABLED='.$old);}
        $prompt=\LoveFortune\Core\Application\Interpretation\InterpretationPrompt::system();self::assertStringContainsString('AI_PROMPT_V1',$prompt);self::assertStringNotContainsString($this->token(),$prompt);
    }
}
