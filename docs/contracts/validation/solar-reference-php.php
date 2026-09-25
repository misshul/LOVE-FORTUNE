<?php
declare(strict_types=1);
// Validation-only; no WordPress bootstrap, persistence, network or production routing.
$root=$argv[1];$plugin=$argv[2];require $plugin.'/autoload.php';
use LoveFortune\Core\Support\Rational as R;
use LoveFortune\Core\Support\DecimalInteger as D;
function readJson(string $p):array{return json_decode(file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
function sec(string $s):int{return (new DateTimeImmutable($s,new DateTimeZone('UTC')))->getTimestamp();}
function ceilExact(R $r):int{[$n,$d]=explode('/',(string)$r);$negative=str_starts_with($n,'-');[$q,$rem]=D::divide(ltrim($n,'-'),$d);if(!$negative&&$rem!=='0')$q=D::add($q,'1');return (int)(($negative?'-':'').$q);}
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$a=readJson($root.'/solar-terms.json');$delta=readJson($root.'/delta-t-s2020.json');$drift=readJson($root.'/historical-drift.json');$leap=readJson($root.'/leap-seconds.json');$million=R::of(1000000);$offset=R::of('32.184');$rows=[];$previous=null;
foreach($a['events'] as $e){
 $t=R::of(sec($e['normalizedTT']));$solutions=[];$y=(int)substr($e['normalizedTT'],0,4);
 if($y<1961){$s=$t->subtract(R::of($delta['values'][$y-1899]['deltaTSeconds']));if($s->compare(R::of(sec('1961-01-01')))<0)$solutions[]=$s;}
 elseif($y<1972){foreach($drift['intervals'] as $row){
  $rate=R::of($row['dailyDrift']);$c=R::of(40587)->subtract(R::of(rtrim($row['referenceMJD'], '.')))->multiply($rate)->add(R::of($row['baseOffset']))->add($offset);
  $s=$t->subtract($c)->divide(R::of(1)->add($rate->divide(R::of(86400))));
  if($s->compare(R::of(sec($row['effectiveStart'])))>=0&&$s->compare(R::of(sec($row['effectiveEnd'])))<0)$solutions[]=$s;
 }}else{foreach($leap['intervals'] as $row){$s=$t->subtract($offset)->subtract(R::of($row['taiMinusUtcSeconds']));if($s->compare(R::of(sec($row['effectiveDate'])))>=0&&$s->compare(R::of(sec($row['effectiveEnd'])))<0)$solutions[]=$s;}}
 check(count($solutions)===1,'Nonunique conversion');$s=$solutions[0];check((string)$s===$e['exactServiceCoordinateSeconds'],'Exact coordinate mismatch');$us=ceilExact($s->multiply($million));check((string)$us===$e['serviceBoundaryUs'],'Boundary mismatch');check($previous===null||$us>$previous,'Ordering');$previous=$us;
 foreach([-1,0,1] as $off)check(($us+$off<$us?'BEFORE':'AFTER')===($off<0?'BEFORE':'AFTER'),'Predicate');
 $rows[]=$e['eventId'].' '.$us;
}
foreach([['100.2',101],['100',100],['-100.2',-100],['99.999999',100],['100.000001',101],['-100.000001',-100],['-99.999999',-99]] as [$x,$expected])check(ceilExact(R::of($x))===$expected,'Ceil mismatch');
echo json_encode(['result'=>'PASS','events'=>count($rows),'coordinateDigest'=>hash('sha256',implode("\n",$rows)."\n"),'phpVersion'=>PHP_VERSION,'phpTimezoneDataVersion'=>timezone_version_get()],JSON_PRETTY_PRINT),"\n";
