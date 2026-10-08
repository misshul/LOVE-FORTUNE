<?php
declare(strict_types=1);
// Synthetic CLI-only filesystem isolation. Never included by production bootstrap.
if(PHP_SAPI!=='cli'){exit;}
$rateTestRoot=sys_get_temp_dir().'/lf-rate-suite-'.bin2hex(random_bytes(8));
mkdir($rateTestRoot,0700);
putenv('LOVE_FORTUNE_RATE_ROOT='.$rateTestRoot);putenv('LOVE_FORTUNE_RATE_SITE=synthetic');
register_shutdown_function(static function()use($rateTestRoot):void{
    $walk=static function(string $p)use(&$walk):void{foreach(new DirectoryIterator($p) as $f){if($f->isDot()){continue;}$n=$f->getPathname();if($f->isDir()&&!$f->isLink()){$walk($n);}else{unlink($n);}}rmdir($p);};
    $walk($rateTestRoot);
});
