<?php
declare(strict_types=1);

use LoveFortune\Core\Api\{CanonicalJson,CompatibilityCalculation,CompatibilityEndpoint,CompatibilityInput,CompatibilityProjection,InterpretationContext,PublicError,PublicRelease,PublicResultValidation,CoreRateLimit};
use LoveFortune\Core\Application\Score\{CombinedLifetimeService,LifetimeSourceEnvelope as E};
use LoveFortune\Core\Domain\Score\CategoryResult as C;
use LoveFortune\Core\Engine\Saju\{NatalResolutionService,SajuFeatureExtractionService,SajuLifetimeScoringService};
use LoveFortune\Core\Engine\Zodiac\{ZodiacDateResolver,ZodiacPairResolver,ZodiacScorer};
use LoveFortune\Core\Support\Rational as R;

final class PublicCombinedApiTest extends \PHPUnit\Framework\TestCase
{
    private const KEY='SYNTHETIC_PUBLIC_KEY_NEVER_FOR_DEPLOYMENT_0001';
    private const NOW=1800000000;
    private function input(): array{return ['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001','birthTime'=>null],'relationshipType'=>'UNKNOWN','targetTimezone'=>'Asia/Tokyo','locale'=>'ko-KR'];}
    private function signer(): InterpretationContext{return new InterpretationContext('current',['current'=>self::KEY,'previous'=>self::KEY.'old'],'previous');}
    private function request(?string $body=null): WP_REST_Request{$r=new WP_REST_Request();$r->set_header('content-type','application/json');$r->set_body($body??json_encode($this->input(),JSON_THROW_ON_ERROR));return $r;}
    private function endpoint(?Closure $calc=null,?Closure $rate=null,?InterpretationContext $signer=null): CompatibilityEndpoint{return new CompatibilityEndpoint($calc,$rate??static fn()=>null,$signer??$this->signer(),static fn()=>self::NOW);}
    private function publicResult(): array{return (new CompatibilityCalculation())->calculate($this->input(),str_repeat('a',32));}

    public function testRealPipelineProjectionAndDeterminism(): void
    {
        $cases=[['2020-01-15','LOC000001','12:00'],['2020-01-15','LOC000001',null],['2020-11-01','LOC000003','01:30'],['2000-02-04','LOC000001',null],['2000-03-21','LOC000001','00:00']];
        $multi=0;$single=0;
        foreach($cases as [$date,$location,$time]){
            $input=$this->input();$input['personA']=['birthDate'=>$date,'birthLocationId'=>$location,'birthTime'=>$time];
            $input['personB']['birthTime']=$time===null?null:'12:00';
            $a=(new NatalResolutionService())->resolve($date,$location,$time);$b=(new NatalResolutionService())->resolve('2000-02-04','LOC000001',$input['personB']['birthTime']);
            $ex=(new SajuFeatureExtractionService())->extract($a,$b);$s=(new SajuLifetimeScoringService())->score($ex);
            $signs=[(new ZodiacDateResolver())->resolve($date)['sign'],(new ZodiacDateResolver())->resolve('2000-02-04')['sign']];
            $z=new ZodiacScorer();$context=(new ZodiacPairResolver())->resolve(...$signs);
            $combined=(new CombinedLifetimeService())->score([E::fromSaju($s),E::fromEligible('ZODIAC',E::IDENTITIES['ZODIAC'],$z->score(...$signs),$context)]);
            $actual=(new CompatibilityCalculation())->calculate($input,str_repeat('b',32));PublicResultValidation::validate($actual);
            self::assertSame((float)$combined['overall']->score->halfUp4(),$actual['overallScore']);
            self::assertSame((float)$combined['overall']->coverage->halfUp4(),$actual['coverage']);
            self::assertSame((float)$combined['overall']->confidence->halfUp4(),$actual['resultConfidence']);
            self::assertSame($combined['overall']->status(),$actual['status']);
            foreach($combined['categories'] as $cat=>$r){self::assertSame(CompatibilityProjection::row($r),$actual['categories'][$cat]);}
            self::assertSame(PublicRelease::versions(),$actual['meta']['versions']);self::assertNull($actual['categories']['COMMUNICATION']['score']);
            $ids=array_column($actual['features'],'featureId');
            foreach($ex['features'] as $f){if(isset($f['identityVersion'])){$multi++;self::assertNotContains($f['featureId'],$ids);}elseif(in_array($f['featureId'],$ids,true)){$single++;}}
            $wire=CanonicalJson::encode($actual);foreach(['variantContributions','pairRefs','candidateId','tenGods','birthDate','birthLocationId','overallPreCoverageConfidence'] as $forbidden){self::assertStringNotContainsString('"'.$forbidden.'"',$wire);}
            self::assertSame($actual,(new CompatibilityCalculation())->calculate($input,str_repeat('b',32)));
        }
        self::assertGreaterThan(0,$multi);self::assertGreaterThan(0,$single);
    }
    public function testNumericHttpSigningAndNoStore(): void
    {
        $response=$this->endpoint()->handle($this->request());self::assertSame(200,$response->get_status());self::assertSame('no-store',$response->get_headers()['Cache-Control']);
        $r=$response->get_data();$p=$this->signer()->verify($r['signedInterpretationContext'],'ko-KR',self::NOW+1);unset($r['signedInterpretationContext']);
        self::assertSame(CanonicalJson::encode($r),CanonicalJson::encode($p['result']));self::assertSame($p['result']['features'],$p['evidence']);
    }
    public function testInsufficientAndSigningFailure(): void
    {
        $calc=function(array $input,string $id):array{
            $combined=(new CombinedLifetimeService())->score([E::fromEligible('SAJU',E::IDENTITIES['SAJU'],array_fill_keys(E::ELIGIBLE['SAJU'],C::unavailable(R::of(1)))),E::fromEligible('ZODIAC',E::IDENTITIES['ZODIAC'],array_fill_keys(E::ELIGIBLE['ZODIAC'],C::unavailable()))]);
            return (new CompatibilityProjection())->project($combined,[],$id);
        };
        $r=$this->endpoint($calc)->handle($this->request());self::assertSame(200,$r->get_status());self::assertNull($r->get_data()['overallScore']);self::assertArrayNotHasKey('signedInterpretationContext',$r->get_data());self::assertGreaterThan(0,$r->get_data()['coverage']);
        $r=$this->endpoint(signer:new InterpretationContext('',[]))->handle($this->request());self::assertSame(503,$r->get_status());self::assertSame('SERVICE_UNAVAILABLE',$r->get_data()['error']['code']);self::assertSame('no-store',$r->get_headers()['Cache-Control']);
    }
    public function testHttpFailuresDoNotExposeDetails(): void
    {
        $cases=[['{',400,'INVALID_REQUEST'],[str_repeat('x',32769),413,'PAYLOAD_TOO_LARGE']];
        $input=$this->input();$input['personA']['birthLocationId']='UNKNOWN';$cases[]=[json_encode($input),404,'REFERENCE_NOT_FOUND'];
        $input=$this->input();$input['personA']['birthDate']='2000-02-30';$cases[]=[json_encode($input),422,'INVALID_REQUEST_SEMANTICS'];
        $input=$this->input();$input['personA']['name']='PRIVATE';$cases[]=[json_encode($input),422,'INVALID_REQUEST_SEMANTICS'];
        $input=$this->input();$input['locale']=null;$cases[]=[json_encode($input),422,'UNSUPPORTED_LOCALE'];
        foreach($cases as [$body,$status,$code]){$r=$this->endpoint()->handle($this->request($body));self::assertSame($status,$r->get_status());self::assertSame($code,$r->get_data()['error']['code']);self::assertSame('no-store',$r->get_headers()['Cache-Control']);}
        foreach(['content-type'=>'text/plain','content-encoding'=>'identity'] as $key=>$value){$req=$this->request();$req->set_header($key,$value);self::assertSame(415,$this->endpoint()->handle($req)->get_status());}
        $req=$this->request();$req->set_header('content-encoding','');self::assertSame(415,$this->endpoint()->handle($req)->get_status());
        self::assertSame(415,$this->endpoint()->handle(new WP_REST_Request())->get_status());
        $r=$this->endpoint(static fn()=>throw new RuntimeException('FEATURE_CATALOG_MISMATCH PRIVATE'))->handle($this->request());self::assertSame(500,$r->get_status());self::assertSame('CALCULATION_FAILED',$r->get_data()['error']['code']);self::assertStringNotContainsString('PRIVATE',CanonicalJson::encode($r->get_data()));
        $r=$this->endpoint(rate:static fn()=>throw new PublicError(429,'RATE_LIMITED',['Retry-After'=>'7']))->handle($this->request());self::assertSame(429,$r->get_status());self::assertSame('7',$r->get_headers()['Retry-After']);self::assertSame('no-store',$r->get_headers()['Cache-Control']);
    }
    private function resign(array $p,string $kid='current',?string $key=null): string
    {
        $b=static fn($v)=>rtrim(strtr(base64_encode(CanonicalJson::encode($v)),'+/','-_'),'=');$m=$b(['alg'=>'HS256','typ'=>'LFIC','kid'=>$kid,'v'=>1]).'.'.$b($p);
        return $m.'.'.rtrim(strtr(base64_encode(hash_hmac('sha256',$m,$key??self::KEY,true)),'+/','-_'),'=');
    }
    public function testHardCutoverTamperingAndKeyRotation(): void
    {
        $s=$this->signer();$token=$s->issue($this->publicResult(),'ko-KR',self::NOW);$p=$s->verify($token,'ko-KR',self::NOW);
        self::assertSame($p,$s->verify($this->resign($p,'previous',self::KEY.'old'),'ko-KR',self::NOW));
        foreach(['scoreVersion'=>'SCORE_ZODIAC_V1','configVersion'=>'synthetic-config-v1'] as $field=>$value){$bad=$p;$bad[$field]=$value;$bad['result']['meta']['versions'][$field]=$value;foreach(['current'=>self::KEY,'previous'=>self::KEY.'old'] as $kid=>$key){try{$s->verify($this->resign($bad,$kid,$key),'ko-KR',self::NOW);self::fail('Old semantics accepted');}catch(PublicError $e){self::assertSame('INVALID_INTERPRETATION_CONTEXT',$e->publicCode);}}}
        $bad=$p;$bad['purpose']='OTHER';try{$s->verify($this->resign($bad),'ko-KR',self::NOW);self::fail('Wrong purpose accepted');}catch(PublicError $e){self::assertSame('INVALID_INTERPRETATION_PURPOSE',$e->publicCode);}
        foreach([[$token,'ko-KR',self::NOW+300,'INTERPRETATION_CONTEXT_EXPIRED'],[$token,'ja-JP',self::NOW,'INTERPRETATION_LOCALE_MISMATCH'],[substr($token,0,-4).'aaaa','ko-KR',self::NOW,'INVALID_INTERPRETATION_CONTEXT']] as [$t,$locale,$now,$code]){try{$s->verify($t,$locale,$now);self::fail('Invalid token accepted');}catch(PublicError $e){self::assertSame($code,$e->publicCode);}}
        foreach(['birthDate','candidateId','variantContributions'] as $key){$bad=$p;$bad['result'][$key]='PRIVATE';try{$s->verify($this->resign($bad),'ko-KR',self::NOW);self::fail('Private field accepted');}catch(PublicError $e){self::assertSame('INVALID_INTERPRETATION_CONTEXT',$e->publicCode);}}
    }
    public function testRateWindowAtomicStorageAndNoRawAddress(): void
    {
        $dir=sys_get_temp_dir().'/lf-api-test-'.bin2hex(random_bytes(8));mkdir($dir,0700);$rate=new CoreRateLimit(self::KEY,$dir);
        try{for($i=0;$i<60;$i++){$rate->consume('192.0.2.10',self::NOW);}try{$rate->consume('::ffff:192.0.2.10',self::NOW);self::fail('Rate exceeded');}catch(PublicError $e){self::assertSame(429,$e->status);self::assertSame('600',$e->headers['Retry-After']);}
            $files=glob($dir.'/*.rate');self::assertCount(1,$files);self::assertSame('60',file_get_contents($files[0]));self::assertStringNotContainsString('192.0.2.10',$files[0]);$rate->consume('192.0.2.10',self::NOW+600);
        }finally{foreach(glob($dir.'/*')?:[] as $file){unlink($file);}rmdir($dir);}
    }
    public function testCanonicalWirePrecision(): void
    {
        self::assertSame('{"a":0,"b":0.0000001,"c":100}',CanonicalJson::encode(['c'=>100.0,'a'=>-0.0,'b'=>1e-7]));
        $r=new C(R::of('44.99995'),R::of('0.12345'),R::of('0.01235'));$p=CompatibilityProjection::row($r);
        self::assertSame(['score'=>45.0,'coverage'=>0.1235,'resultConfidence'=>0.0124,'status'=>'BALANCED'],$p);
        self::assertTrue(CompatibilityEndpoint::allowsOrigin('http://localhost:8080','http://localhost:8080/path'));
        self::assertFalse(CompatibilityEndpoint::allowsOrigin('https://untrusted.invalid','http://localhost:8080'));
        self::assertFalse(CompatibilityEndpoint::allowsOrigin('null','http://localhost:8080'));
    }
}
