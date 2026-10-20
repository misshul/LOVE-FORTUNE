<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use LoveFortune\Core\Support\{RuntimeConfig as C, PrivateJsonConfig as J, RuntimeConfigException};
use LoveFortune\Core\Api\{InterpretationContext, CompatibilityEndpoint, InterpretationEndpoint, CoreRateLimit, PublicError};
use LoveFortune\Core\Infrastructure\Interpretation\JsonGatewayProvider;
use LoveFortune\Core\Infrastructure\Rate\PrivateRateStorage;
require_once dirname(__DIR__).'/interpretation-support.php';

final class RuntimeConfigTest extends TestCase
{
    private string $dir;
    private string $file;
    private array $env = [];
    protected function setUp(): void
    {
        foreach ([...C::KEYS, 'LOVE_FORTUNE_CONFIG_FILE'] as $key) { $this->env[$key]=getenv($key);putenv($key); }
        C::reset();
        $this->dir=sys_get_temp_dir().'/lf-config-'.bin2hex(random_bytes(8));
        mkdir($this->dir,0700);$this->file=$this->dir.'/config.json';
    }
    protected function tearDown(): void
    {
        foreach($this->env as $key=>$value){putenv($value===false?$key:$key.'='.$value);}
        C::reset();
        $walk=static function($p)use(&$walk):void{
            if(is_dir($p)&&!is_link($p)){chmod($p,0700);foreach(new DirectoryIterator($p) as $f){if(!$f->isDot()){$walk($f->getPathname());}}rmdir($p);}
            else{unlink($p);}
        };$walk($this->dir);
    }
    private function write(array $values): void
    {
        file_put_contents($this->file,json_encode((object)$values,JSON_THROW_ON_ERROR));chmod($this->file,0600);
        putenv('LOVE_FORTUNE_CONFIG_FILE='.$this->file);C::reset();
    }
    private function rejected(callable $fn): void
    {
        try{$fn();self::fail('Unsafe config accepted');}
        catch(RuntimeConfigException $e){self::assertSame('LOVE_FORTUNE_CONFIG_INVALID',$e->getMessage());self::assertNull($e->getPrevious());}
    }
    public function testEnvOnlyAndExplicitValues(): void
    {
        self::assertFalse(C::get('LOVE_FORTUNE_RATE_SITE'));
        foreach(['env-site','','0'] as $value){putenv('LOVE_FORTUNE_RATE_SITE='.$value);self::assertSame($value,C::get('LOVE_FORTUNE_RATE_SITE'));}
    }
    public function testFallbackOverrideAndSnapshot(): void
    {
        $this->write(['LOVE_FORTUNE_RATE_SITE'=>'private-site']);
        self::assertSame('private-site',C::get('LOVE_FORTUNE_RATE_SITE'));
        foreach(['env-site','','0'] as $value){putenv('LOVE_FORTUNE_RATE_SITE='.$value);self::assertSame($value,C::get('LOVE_FORTUNE_RATE_SITE'));}
        putenv('LOVE_FORTUNE_RATE_SITE');
        file_put_contents($this->file,'{}');
        self::assertSame('private-site',C::get('LOVE_FORTUNE_RATE_SITE'));
        C::reset();self::assertFalse(C::get('LOVE_FORTUNE_RATE_SITE'));
    }
    public function testAllAllowedStringsAndEscapes(): void
    {
        $values=array_fill_keys(C::KEYS,"synthetic-\"\\\n한글");
        $this->write($values);
        foreach($values as $k=>$v){self::assertSame($v,C::get($k));}
    }
    public function testJsonRejections(): void
    {
        $key='LOVE_FORTUNE_RATE_SITE';
        foreach(['','{','[]','null','{"unknown":"SENTINEL"}',
            '{"'.$key.'":1}','{"'.$key.'":true}','{"'.$key.'":null}',
            '{"'.$key.'":[]}','{"'.$key.'":{}}',
            '{"'.$key.'":"a","'.$key.'":"b"}',
            '{"'.$key.'":"a","\\u004cOVE_FORTUNE_RATE_SITE":"b"}',
            '{"'.$key.'":"'.str_repeat('x',J::MAX_BYTES).'"}'] as $text){
            file_put_contents($this->file,$text);chmod($this->file,0600);
            $this->rejected(fn()=>(new J())->load($this->file));
        }
    }
    public function testUnsafeFilesAndPaths(): void
    {
        $this->write([]);
        foreach(['relative','php://memory',$this->dir.'/../config.json',$this->dir.'/missing',$this->dir] as $path){
            $this->rejected(fn()=>(new J())->load($path));
        }
        symlink($this->file,$this->dir.'/link');
        $this->rejected(fn()=>(new J())->load($this->dir.'/link'));
        foreach([0000,0644] as $mode){chmod($this->file,$mode);$this->rejected(fn()=>(new J())->load($this->file));}
        chmod($this->file,0600);chmod($this->dir,0755);
        $this->rejected(fn()=>(new J())->load($this->file));chmod($this->dir,0700);
        $old=$_SERVER['DOCUMENT_ROOT']??null;
        try{$_SERVER['DOCUMENT_ROOT']=$this->dir;$this->rejected(fn()=>(new J())->load($this->file));}
        finally{if($old===null){unset($_SERVER['DOCUMENT_ROOT']);}else{$_SERVER['DOCUMENT_ROOT']=$old;}}
    }
    public function testBrokenSelectedFileEvenWithEnv(): void
    {
        putenv('LOVE_FORTUNE_CONFIG_FILE='.$this->file);
        putenv('LOVE_FORTUNE_RATE_SITE=env-site');
        $this->rejected(fn()=>C::get('LOVE_FORTUNE_RATE_SITE'));
    }
    public function testExceptionsAndPhpLogsDoNotExposeSentinels(): void
    {
        file_put_contents($this->file,'{"unknown":"SENTINEL_CONFIG_SECRET"}');chmod($this->file,0600);
        $log=$this->dir.'/diagnostic.log';
        $script='ini_set("zend.exception_ignore_args","0");ini_set("log_errors","1");ini_set("error_log",'.var_export($log,true).');'
            .'require '.var_export(dirname(__DIR__,2).'/autoload.php',true).';'
            .'try{(new \LoveFortune\Core\Support\PrivateJsonConfig())->load('.var_export($this->file,true).');}'
            .'catch(Throwable $e){echo (string)$e;}';
        [$code,$out,$err]=$this->child($script);
        self::assertSame(0,$code);self::assertSame('',$err);
        self::assertStringContainsString('LOVE_FORTUNE_CONFIG_INVALID',$out);
        $all=$out.$err.(is_file($log)?file_get_contents($log):'');
        self::assertStringNotContainsString('SENTINEL_CONFIG_SECRET',$all);
        self::assertStringNotContainsString($this->dir,$all);
    }
    private function child(string $script): array
    {
        $p=proc_open([PHP_BINARY,'-r',$script],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);
        fclose($pipes[1]);fclose($pipes[2]);return [proc_close($p),$out,$err];
    }
    public function testConstantPathEqualConflictAndCleanup(): void
    {
        $root=$this->dir.'/rate';mkdir($root,0700);
        $this->write(['LOVE_FORTUNE_RATE_ROOT'=>$root,'LOVE_FORTUNE_RATE_SITE'=>'synthetic']);
        $autoload=var_export(dirname(__DIR__,2).'/autoload.php',true);
        $base='define("LOVE_FORTUNE_CONFIG_FILE",'.var_export($this->file,true).');require '.$autoload.';';
        $probe=$base.'try{echo \LoveFortune\Core\Support\RuntimeConfig::get("LOVE_FORTUNE_RATE_SITE");}catch(Throwable $e){fwrite(STDERR,$e->getMessage());exit(1);}';
        self::assertSame([0,'synthetic',''],$this->child($probe));
        putenv('LOVE_FORTUNE_CONFIG_FILE');
        self::assertSame([0,'synthetic',''],$this->child($probe));
        putenv('LOVE_FORTUNE_CONFIG_FILE='.$this->dir.'/different');
        self::assertSame([1,'','LOVE_FORTUNE_CONFIG_INVALID'],$this->child($probe));
        putenv('LOVE_FORTUNE_CONFIG_FILE');
        $entry=var_export(dirname(__DIR__,2).'/bin/rate-cleanup.php',true);
        $script='define("LOVE_FORTUNE_CONFIG_FILE",'.var_export($this->file,true).');require '.$entry.';';
        [$code,$out,$err]=$this->child($script);
        self::assertSame(0,$code);self::assertSame('',$err);
        self::assertSame('RATE_CLEANUP_PASS',json_decode($out,true)['result']);
        file_put_contents($root.'/synthetic/core/0-'.str_repeat('a',64).'.rate','1');
        chmod($root.'/synthetic/core/0-'.str_repeat('a',64).'.rate',0600);
        [$code,$out]=$this->child($script);
        self::assertSame(0,$code);self::assertSame(1,json_decode($out,true)['deleted']);
        unlink($this->file);
        self::assertSame([1,'',"RATE_CLEANUP_FAILED\n"],$this->child($script));
    }
    public function testSigningRotationAndExpiry(): void
    {
        $key='SYNTHETIC_CONFIG_KEY_NOT_FOR_DEPLOYMENT';
        $this->write(['LOVE_FORTUNE_SIGNING_KID'=>'new','LOVE_FORTUNE_SIGNING_PREVIOUS_KID'=>'old',
            'LOVE_FORTUNE_SIGNING_KEYS'=>json_encode(['new'=>$key,'old'=>$key.'old'])]);
        $s=InterpretationContext::environment();$r=interpretationResults()['Daily'];$now=1800000000;
        $token=$s->issue($r,'ko-KR',$now);
        self::assertSame(\LoveFortune\Core\Api\CanonicalJson::encode($r),\LoveFortune\Core\Api\CanonicalJson::encode($s->verify($token,'ko-KR',$now)['result']));
        $previous=(new InterpretationContext('old',['old'=>$key.'old']))->issue($r,'ko-KR',$now);
        self::assertSame(\LoveFortune\Core\Api\CanonicalJson::encode($r),\LoveFortune\Core\Api\CanonicalJson::encode($s->verify($previous,'ko-KR',$now+299)['result']));
        try{$s->verify($token,'ko-KR',$now+300);self::fail('Expired token accepted');}
        catch(PublicError $e){self::assertSame(422,$e->status);}
    }
    public function testRateConsumerAndMissingValues(): void
    {
        $root=$this->dir.'/rate';mkdir($root,0700);
        $this->write(['LOVE_FORTUNE_RATE_ROOT'=>$root,'LOVE_FORTUNE_RATE_SITE'=>'test','LOVE_FORTUNE_RATE_SECRET'=>str_repeat('synthetic',8)]);
        $store=PrivateRateStorage::configured('core');
        $rate=new CoreRateLimit(C::get('LOVE_FORTUNE_RATE_SECRET'),$store->directory,1);
        $rate->consume('192.0.2.1',1800000000);
        try{$rate->consume('192.0.2.1',1800000000);self::fail('Rate bypass');}
        catch(PublicError $e){self::assertSame(429,$e->status);}
        $this->write([]);
        try{PrivateRateStorage::configured('core');self::fail('Missing config accepted');}
        catch(PublicError $e){self::assertSame(503,$e->status);}
    }
    public function testAiFallbackAndPrivateProvider(): void
    {
        $this->write(['LOVE_FORTUNE_AI_ENABLED'=>'0']);self::assertNull(JsonGatewayProvider::environment());
        $this->write(['LOVE_FORTUNE_AI_ENABLED'=>'1']);self::assertNull(JsonGatewayProvider::environment());
        $this->write(['LOVE_FORTUNE_AI_ENABLED'=>'1','LOVE_FORTUNE_AI_ENDPOINT'=>'https://example.invalid/gateway',
            'LOVE_FORTUNE_AI_KEY'=>'synthetic','LOVE_FORTUNE_AI_PROVIDER'=>'mock','LOVE_FORTUNE_AI_MODEL'=>'mock']);
        self::assertSame(['provider'=>'mock','model'=>'mock'],JsonGatewayProvider::environment()->identity());
        putenv('LOVE_FORTUNE_AI_ENABLED=0');self::assertNull(JsonGatewayProvider::environment());
    }
    public function testPublicErrorRedaction(): void
    {
        putenv('LOVE_FORTUNE_CONFIG_FILE='.$this->dir.'/SENTINEL_PRIVATE_PATH');
        $q=new WP_REST_Request('POST',CompatibilityEndpoint::ROUTE);$q->set_body('{}');
        $r=(new CompatibilityEndpoint())->handle($q);
        self::assertSame(503,$r->get_status());
        self::assertStringNotContainsString($this->dir,json_encode($r->get_data()));
        self::assertStringNotContainsString('SENTINEL',json_encode($r->get_data()));
        $q=new WP_REST_Request('POST',InterpretationEndpoint::ROUTE);$q->set_header('content-type','application/json');
        $q->set_body(json_encode(['signedContext'=>'a.b.'.str_repeat('c',43),'locale'=>'ko-KR']));
        $r=(new InterpretationEndpoint())->handle($q);
        self::assertSame(503,$r->get_status());
        self::assertStringNotContainsString($this->dir,json_encode($r->get_data()));
    }
    public function testPrivateConfigurationThroughBothEndpointConsumers(): void
    {
        $root=$this->dir.'/rate';mkdir($root,0700);
        $this->write(['LOVE_FORTUNE_RATE_ROOT'=>$root,'LOVE_FORTUNE_RATE_SITE'=>'test',
            'LOVE_FORTUNE_RATE_SECRET'=>str_repeat('synthetic',8),'LOVE_FORTUNE_SIGNING_KID'=>'test',
            'LOVE_FORTUNE_SIGNING_KEYS'=>json_encode(['test'=>str_repeat('synthetic',8)]),'LOVE_FORTUNE_AI_ENABLED'=>'0']);
        $old=$_SERVER['REMOTE_ADDR']??null;
        try{
            $_SERVER['REMOTE_ADDR']='192.0.2.1';
            $q=new WP_REST_Request('POST',CompatibilityEndpoint::ROUTE);$q->set_header('content-type','application/json');
            $q->set_body(json_encode(['personA'=>['birthDate'=>'2000-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],
                'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001','birthTime'=>null],
                'relationshipType'=>'UNKNOWN','targetTimezone'=>'Asia/Tokyo','locale'=>'ko-KR']));
            $r=(new CompatibilityEndpoint(clock:static fn()=>1800000000))->handle($q);
            self::assertSame(200,$r->get_status());
            $q=new WP_REST_Request('POST',InterpretationEndpoint::ROUTE);$q->set_header('content-type','application/json');
            $q->set_body(json_encode(['signedContext'=>$r->get_data()['signedInterpretationContext'],'locale'=>'ko-KR']));
            $endpoint=new InterpretationEndpoint(clock:static fn()=>1800000000);
            for($i=0;$i<10;$i++){$response=$endpoint->handle($q);self::assertSame(200,$response->get_status());}
            self::assertSame('FALLBACK',$response->get_data()['meta']['interpretationMode']);
            self::assertSame(429,$endpoint->handle($q)->get_status());
            self::assertStringNotContainsString(str_repeat('synthetic',8),json_encode($response->get_data()));
        }finally{if($old===null){unset($_SERVER['REMOTE_ADDR']);}else{$_SERVER['REMOTE_ADDR']=$old;}}
    }
}
