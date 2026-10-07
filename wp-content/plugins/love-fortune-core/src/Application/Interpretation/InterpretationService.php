<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Interpretation;

use LoveFortune\Core\Api\PublicError;

final class InterpretationService
{
    public function __construct(private readonly ?ProviderInterface $provider=null,private readonly ?\Closure $clock=null,private readonly ?\Closure $fallback=null) {}
    private function now(): float { return $this->clock===null?hrtime(true)/1e9:($this->clock)(); }
    public function interpret(array $verified,string $requestId): array
    {
        $deadline=$this->now()+8.0;$dto=ProviderInput::project($verified);$errors=[];$retried=false;$repaired=false;$content=null;$mode='FALLBACK';$identity=['provider'=>null,'model'=>null];
        if($this->provider!==null){
            try{
                $id=$this->provider->identity();
                if(!is_string($id['provider']??null)||trim($id['provider'])===''||!is_string($id['model']??null)||trim($id['model'])===''){throw new ProviderFailure('CONFIG');}
                while(($remaining=$deadline-$this->now())>0){
                    $timeout=min(6.0,$remaining);$started=$this->now();
                    try{$raw=$this->provider->generate($dto,$timeout,$errors);}
                    catch(ProviderFailure $e){if($e->retryable()&&!$retried&&$this->now()<$deadline){$retried=true;continue;}break;}
                    if($this->now()-$started>$timeout||$this->now()>=$deadline){break;}
                    try{$parsed=json_decode($raw,false,16,JSON_THROW_ON_ERROR);$candidate=$parsed instanceof \stdClass?json_decode($raw,true,16,JSON_THROW_ON_ERROR):null;
                        // Preserve JSON array-vs-object distinction before associative decoding.
                        if(!$parsed instanceof \stdClass||!is_array($parsed->strengths??null)||!is_array($parsed->challenges??null)){$candidate=null;}
                        else{foreach([...$parsed->strengths,...$parsed->challenges] as $item){if(!$item instanceof \stdClass||!is_array($item->evidenceRefs??null)){$candidate=null;break;}}}
                        $errors=InterpretationText::validate($candidate,$dto);
                    }catch(\Throwable){$errors=['INVALID_JSON'];}
                    if($errors===[]){$content=$candidate;$mode='AI';$identity=['provider'=>$id['provider'],'model'=>$id['model']];break;}
                    if($repaired){break;}$repaired=true;
                }
            }catch(\Throwable){ /* Fail closed; do not log or expose provider exceptions. */ }
        }
        if($content===null){
            try{$content=$this->fallback===null?InterpretationText::fallback($dto):($this->fallback)($dto);
                if(InterpretationText::validate($content,$dto)!==[]){throw new \RuntimeException();}
            }catch(\Throwable){throw new PublicError(503,'INTERPRETATION_UNAVAILABLE');}
        }
        return ['meta'=>['requestId'=>$requestId,'versions'=>$verified['result']['meta']['versions'],'aiPromptVersion'=>InterpretationText::VERSION,
            'provider'=>$identity['provider'],'model'=>$identity['model'],'interpretationMode'=>$mode]]+$content;
    }
}
