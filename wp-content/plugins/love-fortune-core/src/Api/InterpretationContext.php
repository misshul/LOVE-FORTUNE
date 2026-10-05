<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

/** No provider calls, token database, user cache or birth data. */
final class InterpretationContext
{
    public function __construct(private readonly string $currentId,private readonly array $keys,private readonly ?string $previousId=null) {}
    public static function environment(): self
    {
        // JSON object with one current and optionally one previous key. Never log values.
        $id=(string)getenv('LOVE_FORTUNE_SIGNING_KID');$previous=getenv('LOVE_FORTUNE_SIGNING_PREVIOUS_KID');
        try{$keys=json_decode((string)getenv('LOVE_FORTUNE_SIGNING_KEYS'),true,8,JSON_THROW_ON_ERROR);}catch(\Throwable){$keys=[];}
        return new self($id,is_array($keys)?$keys:[],$previous===false||$previous===''?null:$previous);
    }
    private static function validateResult(array $r): void
    {
        if(array_key_exists('dailyScore',$r)){DailyResultValidation::validate($r);}else{PublicResultValidation::validate($r);}
    }
    private static function signable(array $r): bool
    {
        return array_key_exists('dailyScore',$r)?DailyResultValidation::needsSignature($r):($r['overallScore']??null)!==null;
    }
    private function key(string $id): string
    {
        if($id==='' || !in_array($id,[$this->currentId,$this->previousId],true) || !is_string($this->keys[$id]??null) || strlen($this->keys[$id])<32){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        return $this->keys[$id];
    }
    private static function encode(mixed $v): string{return rtrim(strtr(base64_encode(CanonicalJson::encode($v)),'+/','-_'),'=');}
    public function issue(array $result,string $locale,int $now): string
    {
        if(!self::signable($result) || isset($result['signedInterpretationContext'])){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
        self::validateResult($result);
        $key=$this->key($this->currentId);$versions=$result['meta']['versions'];
        $engines=$versions;unset($engines['scoreVersion'],$engines['configVersion']);
        $payload=['contextVersion'=>'ZODIAC_CONTEXT_V1','purpose'=>'INTERPRETATION','locale'=>$locale,
            'issuedAt'=>gmdate('Y-m-d\TH:i:s\Z',$now),'expiresAt'=>gmdate('Y-m-d\TH:i:s\Z',$now+300),
            'engineVersions'=>$engines,'scoreVersion'=>PublicRelease::SCORE,'configVersion'=>$versions['configVersion'],
            'result'=>$result,'evidence'=>$result['features']];
        if(isset($result['zodiacContext'])){$payload['zodiacContext']=$result['zodiacContext'];}
        $message=self::encode(['alg'=>'HS256','typ'=>'LFIC','kid'=>$this->currentId,'v'=>1]).'.'.self::encode($payload);
        return $message.'.'.rtrim(strtr(base64_encode(hash_hmac('sha256',$message,$key,true)),'+/','-_'),'=');
    }
    public function verify(string $token,string $locale,int $now): array
    {
        try{
            if(strlen($token)>65536){throw new \RuntimeException();}
            $parts=explode('.',$token);if(count($parts)!==3){throw new \RuntimeException();}
            foreach($parts as $part){if(!preg_match('/^[A-Za-z0-9_-]+$/D',$part)){throw new \RuntimeException();}}
            $decode=static fn(string $s):mixed=>json_decode(base64_decode(strtr($s,'-_','+/'),true),true,32,JSON_THROW_ON_ERROR);
            $h=$decode($parts[0]);$p=$decode($parts[1]);
            if(!is_array($h)||!is_array($p)||self::encode($h)!==$parts[0]||self::encode($p)!==$parts[1]){throw new \RuntimeException();}
            $expected=['alg'=>'HS256','typ'=>'LFIC','kid'=>$h['kid']??null,'v'=>1];
            if(CanonicalJson::encode($expected)!==CanonicalJson::encode($h)||!is_string($h['kid'])){throw new \RuntimeException();}
            $mac=hash_hmac('sha256',$parts[0].'.'.$parts[1],$this->key($h['kid']),true);
            $given=base64_decode(strtr($parts[2],'-_','+/'),true);
            if(!is_string($given)||!hash_equals($mac,$given)||rtrim(strtr(base64_encode($given),'+/','-_'),'=')!==$parts[2]){throw new \RuntimeException();}
            if(($p['purpose']??null)!=='INTERPRETATION'){throw new PublicError(422,'INVALID_INTERPRETATION_PURPOSE');}
            if(($p['locale']??null)!==$locale){throw new PublicError(422,'INTERPRETATION_LOCALE_MISMATCH');}
            foreach(['issuedAt','expiresAt'] as $field){if(!is_string($p[$field]??null)||!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D',$p[$field])){throw new \RuntimeException();}}
            $issued=strtotime($p['issuedAt']);$expires=strtotime($p['expiresAt']);
            if($issued===false||$expires===false||gmdate('Y-m-d\TH:i:s\Z',$issued)!==$p['issuedAt']||gmdate('Y-m-d\TH:i:s\Z',$expires)!==$p['expiresAt']||$expires-$issued!==300||$now<$issued){throw new \RuntimeException();}
            if($now>=$expires){throw new PublicError(422,'INTERPRETATION_CONTEXT_EXPIRED');}
            if(($p['contextVersion']??null)!=='ZODIAC_CONTEXT_V1'||($p['scoreVersion']??null)!==PublicRelease::SCORE||!in_array($p['configVersion']??null,[PublicRelease::CONFIG,DailyRelease::CONFIG],true)){throw new \RuntimeException();}
            $allowed=['contextVersion','purpose','locale','issuedAt','expiresAt','engineVersions','scoreVersion','configVersion','result','evidence','zodiacContext'];
            if(array_diff(array_keys($p),$allowed)!==[]){throw new \RuntimeException();}
            self::validateResult($p['result']);
            if($p['configVersion']!==$p['result']['meta']['versions']['configVersion']){throw new \RuntimeException();}
            if(!self::signable($p['result'])){throw new \RuntimeException();}
            if(CanonicalJson::encode(($p['engineVersions']??[])+['scoreVersion'=>$p['scoreVersion'],'configVersion'=>$p['configVersion']])!==CanonicalJson::encode($p['result']['meta']['versions'])){throw new \RuntimeException();}
            if(CanonicalJson::encode($p['zodiacContext']??null)!==CanonicalJson::encode($p['result']['zodiacContext']??null)){throw new \RuntimeException();}
            if(!is_array($p['evidence'])||!array_is_list($p['evidence'])){throw new \RuntimeException();}
            $features=array_column($p['result']['features'],null,'featureId');
            foreach($p['evidence'] as $f){if(!is_array($f)||CanonicalJson::encode($features[$f['featureId']??'']??null)!==CanonicalJson::encode($f)){throw new \RuntimeException();}}
            return $p;
        }catch(PublicError $e){if($e->status===422){throw $e;}throw new PublicError(422,'INVALID_INTERPRETATION_CONTEXT');}
        catch(\Throwable){throw new PublicError(422,'INVALID_INTERPRETATION_CONTEXT');}
    }
}
