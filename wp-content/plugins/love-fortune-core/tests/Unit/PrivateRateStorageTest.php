<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use LoveFortune\Core\Api\{CoreRateLimit,PublicError};
use LoveFortune\Core\Infrastructure\Rate\{PrivateRateStorage as S,RateCleanup};

final class PrivateRateStorageTest extends TestCase
{
    private string $dir;
    private const NOW=1800000000;
    protected function setUp(): void {$this->dir=sys_get_temp_dir().'/lf-private-test-'.bin2hex(random_bytes(8));mkdir($this->dir,0700);}
    protected function tearDown(): void
    {
        $walk=static function(string $dir)use(&$walk):void{chmod($dir,0700);foreach(new DirectoryIterator($dir) as $f){if($f->isDot()){continue;}$p=$f->getPathname();if($f->isDir()&&!$f->isLink()){$walk($p);}else{unlink($p);}}rmdir($dir);};$walk($this->dir);
    }
    private function rate(): CoreRateLimit {return new CoreRateLimit(str_repeat('synthetic',8),$this->dir);}
    private function file(): string {return glob($this->dir.'/*.rate')[0];}
    private function blocked(callable $fn): void {try{$fn();self::fail('Unsafe operation accepted');}catch(PublicError $e){self::assertSame(503,$e->status);}}
    public function testPermissionsAndPrivateContent(): void
    {
        $this->rate()->consume('192.0.2.1',self::NOW);$f=$this->file();
        self::assertSame(0600,fileperms($f)&0777);self::assertSame('1',file_get_contents($f));self::assertStringNotContainsString('192.0.2.1',$f);
    }
    public function testIdleExpiryWithoutWriter(): void
    {
        $this->rate()->consume('192.0.2.1',self::NOW);$c=new RateCleanup();$s=new S($this->dir);
        self::assertSame(['deleted'=>0,'active'=>1],$c->run($s,self::NOW+659));
        self::assertSame(['deleted'=>1,'active'=>0],$c->run($s,self::NOW+660));self::assertSame([],glob($this->dir.'/*.rate'));
    }
    public function testDelayedCleanupStillBeforeDeadline(): void
    {
        $this->rate()->consume('192.0.2.1',self::NOW);self::assertSame(1,(new RateCleanup())->run(new S($this->dir),self::NOW+3000)['deleted']);
    }
    public function testInvalidPaths(): void
    {
        foreach(['','relative','/tmp/../tmp','php://memory',$this->dir.'/missing'] as $p){$this->blocked(fn()=>(new S($p))->validate());}
    }
    public function testRootSymlink(): void
    {
        symlink($this->dir,$this->dir.'/alias');$this->blocked(fn()=>(new S($this->dir.'/alias'))->validate());
    }
    public function testNonDirectoryRoot(): void
    {
        file_put_contents($this->dir.'/plain','');$this->blocked(fn()=>(new S($this->dir.'/plain'))->validate());
    }
    public function testUnsafeRootPermissions(): void
    {
        chmod($this->dir,0755);clearstatcache();$this->blocked(fn()=>$this->rate()->consume('192.0.2.1',self::NOW));
        chmod($this->dir,0500);clearstatcache();$this->blocked(fn()=>(new S($this->dir))->validate());
    }
    public function testCounterSymlinkAndUnsafeFileType(): void
    {
        $this->rate()->consume('192.0.2.1',self::NOW);$f=$this->file();unlink($f);symlink('/dev/null',$f);
        $this->blocked(fn()=>$this->rate()->consume('192.0.2.1',self::NOW));$this->blocked(fn()=>(new RateCleanup())->run(new S($this->dir),self::NOW+660));
        unlink($f);mkdir($f,0700);$this->blocked(fn()=>$this->rate()->consume('192.0.2.1',self::NOW));
    }
    public function testMalformedCounterDoesNotResetQuota(): void
    {
        $this->rate()->consume('192.0.2.1',self::NOW);file_put_contents($this->file(),'bad');
        $this->blocked(fn()=>$this->rate()->consume('192.0.2.1',self::NOW));$this->blocked(fn()=>(new RateCleanup())->run(new S($this->dir),self::NOW+660));self::assertSame('bad',file_get_contents($this->file()));
    }
    public function testWriterAndCleanerRespectCounterLock(): void
    {
        $this->rate()->consume('192.0.2.1',self::NOW);$h=fopen($this->file(),'r+b');flock($h,LOCK_EX);
        try{$this->blocked(fn()=>$this->rate()->consume('192.0.2.1',self::NOW));$this->blocked(fn()=>(new RateCleanup())->run(new S($this->dir),self::NOW+660));}finally{flock($h,LOCK_UN);fclose($h);}
        self::assertSame('1',file_get_contents($this->file()));self::assertSame(1,(new RateCleanup())->run(new S($this->dir),self::NOW+660)['deleted']);
    }
    public function testBucketLockContention(): void
    {
        $s=new S($this->dir);$h=$s->open('storage.lock');flock($h,LOCK_EX);
        try{$this->blocked(fn()=>$this->rate()->consume('192.0.2.1',self::NOW));}finally{flock($h,LOCK_UN);fclose($h);}
    }
    public function testLockFailure(): void
    {
        $s=new class($this->dir) extends S {protected function acquire($h): bool{return false;}};
        $this->blocked(fn()=>$s->locked(fn()=>self::fail('Lock bypass')));
    }
    public function testWriteFailure(): void
    {
        file_put_contents($this->dir.'/readonly','x');$h=fopen($this->dir.'/readonly','rb');
        try{$this->blocked(fn()=>(new S($this->dir))->write($h,1));}finally{fclose($h);}
    }
    public function testUnrelatedFileNeverDeleted(): void
    {
        file_put_contents($this->dir.'/unrelated','preserve');$this->blocked(fn()=>(new RateCleanup())->run(new S($this->dir),self::NOW));self::assertSame('preserve',file_get_contents($this->dir.'/unrelated'));
    }
    public function testCapacityBound(): void
    {
        for($i=0;$i<S::MAX_COUNTERS;$i++){file_put_contents($this->dir.'/'.self::NOW.'-'.hash('sha256',(string)$i).'.rate','1');}
        $this->blocked(fn()=>$this->rate()->consume('192.0.2.1',self::NOW));self::assertCount(S::MAX_COUNTERS,(new S($this->dir))->entries());
    }
    public function testLocalMultiProcessAtomicityAndCleaner(): void
    {
        $procs=[];
        foreach(['write','write','clean'] as $mode){$pipes=[];$p=proc_open([PHP_BINARY,dirname(__DIR__).'/rate-worker.php',$this->dir,$mode],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);self::assertIsResource($p);fclose($pipes[0]);$procs[]=[$p,$pipes,$mode];}
        $ok=0;$limited=0;
        foreach($procs as [$p,$pipes,$mode]){$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);self::assertSame(0,proc_close($p),$err);$r=json_decode($out,true,8,JSON_THROW_ON_ERROR);if($mode==='write'){$ok+=$r['ok'];$limited+=$r['limited'];}}
        self::assertSame(60,$ok);self::assertSame(20,$limited);self::assertSame('60',file_get_contents($this->file()));
    }
    public function testExplicitConfigurationIsolationAndPublicPathRejection(): void
    {
        $oldRoot=getenv('LOVE_FORTUNE_RATE_ROOT');$oldSite=getenv('LOVE_FORTUNE_RATE_SITE');$oldPublic=$_SERVER['DOCUMENT_ROOT']??null;
        try{
            putenv('LOVE_FORTUNE_RATE_ROOT');putenv('LOVE_FORTUNE_RATE_SITE');$this->blocked(fn()=>S::configured('core'));
            putenv('LOVE_FORTUNE_RATE_ROOT='.$this->dir);putenv('LOVE_FORTUNE_RATE_SITE=site-a');
            $_SERVER['DOCUMENT_ROOT']=$this->dir;$this->blocked(fn()=>S::configured('core'));unset($_SERVER['DOCUMENT_ROOT']);
            $a=S::configured('core');putenv('LOVE_FORTUNE_RATE_SITE=site-b');$b=S::configured('core');self::assertNotSame($a->directory,$b->directory);
            putenv('LOVE_FORTUNE_RATE_SITE=../escape');$this->blocked(fn()=>S::configured('core'));
        }finally{putenv($oldRoot===false?'LOVE_FORTUNE_RATE_ROOT':'LOVE_FORTUNE_RATE_ROOT='.$oldRoot);putenv($oldSite===false?'LOVE_FORTUNE_RATE_SITE':'LOVE_FORTUNE_RATE_SITE='.$oldSite);if($oldPublic===null){unset($_SERVER['DOCUMENT_ROOT']);}else{$_SERVER['DOCUMENT_ROOT']=$oldPublic;}}
    }
    public function testIndependentCliCleanup(): void
    {
        $env=getenv();$env['LOVE_FORTUNE_RATE_ROOT']=$this->dir;$env['LOVE_FORTUNE_RATE_SITE']='cli-test';
        mkdir($this->dir.'/cli-test',0700);mkdir($this->dir.'/cli-test/core',0700);
        $rate=new CoreRateLimit(str_repeat('synthetic',8),$this->dir.'/cli-test/core');$rate->consume('192.0.2.1',intdiv(time(),600)*600-1200);
        $p=proc_open([PHP_BINARY,dirname(__DIR__,2).'/bin/rate-cleanup.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);self::assertIsResource($p);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);self::assertSame(0,proc_close($p),$err);
        self::assertSame(['result'=>'RATE_CLEANUP_PASS','deleted'=>1,'active'=>0],json_decode($out,true));self::assertStringNotContainsString('192.0.2.1',$out);
    }
}
