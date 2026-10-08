<?php
declare(strict_types=1);
namespace LoveFortune\Core\Infrastructure\Rate;
use LoveFortune\Core\Api\PublicError;

/** Private local infrastructure only. No user content or fallback directory. */
class PrivateRateStorage
{
    public const MAX_COUNTERS=1024;
    public function __construct(public readonly string $directory) {}
    protected function fail(): never { throw new PublicError(503,'SERVICE_UNAVAILABLE'); }
    public function validate(): void
    {
        $p=$this->directory;
        if($p===''||!str_starts_with($p,'/')||str_contains($p,'://')||str_contains($p,"\0")||preg_match('~(?:^|/)\.{1,2}(?:/|$)~',$p)||str_contains($p,'//')){$this->fail();}
        $part='';foreach(explode('/',trim($p,'/')) as $segment){$part.='/'.$segment;clearstatcache(true,$part);if(is_link($part)){$this->fail();}}
        if(!is_dir($p)){$this->fail();}$s=lstat($p);
        if($s===false||($s['mode']&0777)!==0700||!is_readable($p)||!is_writable($p)){$this->fail();}
    }
    public static function configured(string $bucket): self
    {
        if(!in_array($bucket,['core','range','week','month','year','interpretation'],true)){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        $root=(string)getenv('LOVE_FORTUNE_RATE_ROOT');$site=(string)getenv('LOVE_FORTUNE_RATE_SITE');
        if(!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D',$site)){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        (new self($root))->validate();
        foreach([defined('ABSPATH')?(string)constant('ABSPATH'):'',(string)($_SERVER['DOCUMENT_ROOT']??'')] as $public){
            if($public!==''&&($resolved=realpath($public))!==false&&($root===$resolved||str_starts_with($root,rtrim($resolved,'/').'/'))){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        }
        foreach([$root.'/'.$site,$root.'/'.$site.'/'.$bucket] as $dir){if(!file_exists($dir)&&!is_link($dir)){@mkdir($dir,0700);}(new self($dir))->validate();}
        return new self($root.'/'.$site.'/'.$bucket);
    }
    public function open(string $name,bool $create=true)
    {
        if($name!=='storage.lock'&&!preg_match('/^[0-9]{1,12}-[a-f0-9]{64}\.rate$/D',$name)){$this->fail();}
        $path=$this->directory.'/'.$name;clearstatcache(true,$path);
        if(is_link($path)){$this->fail();}
        if(file_exists($path)){$before=lstat($path);if($before===false||($before['mode']&0170000)!==0100000){$this->fail();}}
        if(!file_exists($path)){
            if(!$create){return null;}$old=umask(0077);try{$h=@fopen($path,'x+b');}finally{umask($old);}
            if($h===false){clearstatcache(true,$path);$before=@lstat($path);if($before===false||is_link($path)||($before['mode']&0170000)!==0100000){$this->fail();}$h=@fopen($path,'r+b');if($h===false){$this->fail();}}
        }else{$h=@fopen($path,'r+b');if($h===false){$this->fail();}}
        clearstatcache(true,$path);$s=lstat($path);$f=fstat($h);$d=lstat($this->directory);
        if($s===false||$f===false||$d===false||($s['mode']&0170000)!==0100000||($s['mode']&0777)!==0600||$s['nlink']!==1||$s['uid']!==$d['uid']||$s['ino']!==$f['ino']||$s['dev']!==$f['dev']){fclose($h);$this->fail();}
        return $h;
    }
    protected function acquire($h): bool
    {
        $end=hrtime(true)+250000000;
        do{if(@flock($h,LOCK_EX|LOCK_NB)){return true;}usleep(1000);}while(hrtime(true)<$end);
        return false;
    }
    public function locked(callable $work): mixed
    {
        $this->validate();$h=$this->open('storage.lock');
        try{if(!$this->acquire($h)){$this->fail();}return $work();}finally{@flock($h,LOCK_UN);fclose($h);}
    }
    public function counterLocked($h,callable $work): mixed
    {
        try{if(!$this->acquire($h)){$this->fail();}return $work();}finally{@flock($h,LOCK_UN);fclose($h);}
    }
    public function count($h): int
    {
        rewind($h);$value=stream_get_contents($h,32);
        if($value===false||!preg_match('/^(?:0|[1-9][0-9]{0,8})$/D',$value)){$this->fail();}return (int)$value;
    }
    public function write($h,int $count): void
    {
        $v=(string)$count;rewind($h);
        if(!@ftruncate($h,0)||@fwrite($h,$v)!==strlen($v)||!@fflush($h)){$this->fail();}
    }
    public function entries(): array
    {
        $out=[];
        foreach(new \DirectoryIterator($this->directory) as $f){if($f->isDot()||$f->getFilename()==='storage.lock'){continue;}
            if(count($out)>=self::MAX_COUNTERS){$this->fail();}
            $n=$f->getFilename();if(!preg_match('/^([0-9]{1,12})-[a-f0-9]{64}\.rate$/D',$n,$m)||$f->isLink()||!$f->isFile()||((int)$m[1])%600!==0){$this->fail();}$out[]=$n;
        }return $out;
    }
    public function remove(string $name,$h): void
    {
        $p=$this->directory.'/'.$name;clearstatcache(true,$p);$s=lstat($p);$f=fstat($h);
        if($s===false||$f===false||is_link($p)||$s['ino']!==$f['ino']||$s['dev']!==$f['dev']||!@unlink($p)){$this->fail();}
    }
}
