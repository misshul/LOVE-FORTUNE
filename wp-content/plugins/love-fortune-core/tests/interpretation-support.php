<?php
declare(strict_types=1);
// Synthetic test-only fixtures. Never loaded by plugin bootstrap.
use LoveFortune\Core\Api\{CompatibilityCalculation,DailyCalculation,PeriodProjection,CanonicalJson};
use LoveFortune\Core\Application\Score\{PeriodCalendar,PeriodOrchestrationService};
use LoveFortune\Core\Application\Interpretation\{ProviderInterface,InterpretationText};
use LoveFortune\Core\Support\Rational;

function interpretationResults(): array
{
    static $results=null;if($results!==null){return $results;}
    $in=['personA'=>['birthDate'=>'2020-01-15','birthLocationId'=>'LOC000001','birthTime'=>'12:00'],'personB'=>['birthDate'=>'2000-02-04','birthLocationId'=>'LOC000001','birthTime'=>null],'relationshipType'=>'UNKNOWN','date'=>'2024-09-02','targetTimezone'=>'Asia/Tokyo','locale'=>'ko-KR'];
    $results=['Compatibility'=>(new CompatibilityCalculation())->calculate($in,str_repeat('a',32)),'Daily'=>(new DailyCalculation())->calculate($in,str_repeat('b',32))];
    foreach(['WEEK'=>'Weekly','MONTH'=>'Monthly','YEAR'=>'Yearly'] as $type=>$name){
        $dates=PeriodCalendar::membership($type,$type==='WEEK'?['weekStartDate'=>'2024-09-02']:['year'=>2027,'month'=>3]);$rows=[];
        foreach($dates as $date){$rows[$date]=['score'=>Rational::of('52.99996'),'coverage'=>Rational::of('0.8'),'resultConfidence'=>Rational::of('0.7'),'dailyStatus'=>'STABLE'];}
        $results[$name]=PeriodProjection::project((new PeriodOrchestrationService())->aggregate($type,$dates,$rows,Rational::of(50),'Asia/Tokyo'),str_repeat('c',32));
    }
    return $results;
}
function interpretationResign(string $token,Closure $change,string $key): string
{
    $parts=explode('.',$token);$p=json_decode(base64_decode(strtr($parts[1],'-_','+/')),true);$p=$change($p);
    $parts[1]=rtrim(strtr(base64_encode(CanonicalJson::encode($p)),'+/','-_'),'=');
    $message=$parts[0].'.'.$parts[1];return $message.'.'.rtrim(strtr(base64_encode(hash_hmac('sha256',$message,$key,true)),'+/','-_'),'=');
}
final class InterpretationMock implements ProviderInterface
{
    public array $calls=[];
    public function __construct(private readonly ?Closure $behavior=null) {}
    public function identity(): array{return ['provider'=>'SYNTHETIC_PROVIDER','model'=>'SYNTHETIC_MODEL'];}
    public function generate(array $dto,float $timeout,array $errors=[]): string
    {
        $this->calls[]=['dto'=>$dto,'timeout'=>$timeout,'errors'=>$errors];
        return $this->behavior===null?CanonicalJson::encode(InterpretationText::fallback($dto)):($this->behavior)($dto,$timeout,$errors,count($this->calls));
    }
}
