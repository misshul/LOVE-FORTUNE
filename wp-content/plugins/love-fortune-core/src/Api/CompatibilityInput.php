<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

use LoveFortune\Core\Engine\Saju\LocationReferenceRepository;

final class CompatibilityInput
{
    public static function parse(string $body, string $contentType, ?string $encoding): array
    {
        if($encoding!==null){throw new PublicError(415,'UNSUPPORTED_CONTENT_ENCODING');}
        if(!preg_match('/^application\/json(?:;\s*charset=utf-8)?$/i',$contentType)){throw new PublicError(415,'UNSUPPORTED_MEDIA_TYPE');}
        if(strlen($body)>32768){throw new PublicError(413,'PAYLOAD_TOO_LARGE');}
        try{$object=json_decode($body,false,32,JSON_THROW_ON_ERROR);}catch(\JsonException){throw new PublicError(400,'INVALID_REQUEST');}
        if(!$object instanceof \stdClass){throw new PublicError(400,'INVALID_REQUEST');}
        self::keys($object,['personA','personB','relationshipType','targetTimezone'],['locale']);
        $input=(array)$object;
        if(!in_array($input['relationshipType'],['COUPLE','MARRIED','DATING','CRUSH','FRIEND','UNKNOWN'],true)){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
        if(!is_string($input['targetTimezone']) || !in_array($input['targetTimezone'],\DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC),true)){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
        if(!array_key_exists('locale',$input)){$input['locale']='ko-KR';}
        if(!in_array($input['locale'],['ko-KR','ja-JP','en-US'],true)){throw new PublicError(422,'UNSUPPORTED_LOCALE');}
        $locations=new LocationReferenceRepository();
        foreach(['personA','personB'] as $side){
            $p=$input[$side];if(!$p instanceof \stdClass){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
            self::keys($p,['birthDate','birthLocationId'],['birthTime']);$p=(array)$p;
            if(!is_string($p['birthDate']) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$p['birthDate'],$m) || !checkdate((int)$m[2],(int)$m[3],(int)$m[1])){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
            if($p['birthDate']<'1900-01-01' || $p['birthDate']>'2099-12-31'){throw new PublicError(422,'UNSUPPORTED_DATE');}
            $p['birthTime']??=null;
            if($p['birthTime']!==null && (!is_string($p['birthTime']) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$p['birthTime']))){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
            if(!is_string($p['birthLocationId']) || $p['birthLocationId']===''){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
            try{$locations->get($p['birthLocationId']);}catch(\RuntimeException $e){if($e->getMessage()==='LOCATION_NOT_FOUND'){throw new PublicError(404,'REFERENCE_NOT_FOUND');}throw $e;}
            $input[$side]=$p;
        }
        return $input;
    }
    private static function keys(\stdClass $object,array $required,array $optional): void
    {
        $keys=array_keys((array)$object);
        if(array_diff($required,$keys)!==[] || array_diff($keys,[...$required,...$optional])!==[]){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
    }
}
