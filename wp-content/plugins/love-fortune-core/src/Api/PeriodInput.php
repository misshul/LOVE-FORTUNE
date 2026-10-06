<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;
use LoveFortune\Core\Application\Score\PeriodCalendar;
final class PeriodInput
{
    public static function parse(string $kind,string $body,string $type,?string $encoding): array
    {
        if($encoding!==null){throw new PublicError(415,'UNSUPPORTED_CONTENT_ENCODING');}
        if(!preg_match('/^application\/json(?:;\s*charset=utf-8)?$/i',$type)){throw new PublicError(415,'UNSUPPORTED_MEDIA_TYPE');}
        if(strlen($body)>32768){throw new PublicError(413,'PAYLOAD_TOO_LARGE');}
        try{$o=json_decode($body,false,32,JSON_THROW_ON_ERROR);}catch(\JsonException){throw new PublicError(400,'INVALID_REQUEST');}
        if(!$o instanceof \stdClass){throw new PublicError(400,'INVALID_REQUEST');}$a=(array)$o;
        $keys=match($kind){'RANGE'=>['startDate','endDate'],'WEEK'=>['weekStartDate'],'MONTH'=>['year','month'],'YEAR'=>['year']};
        $required=['personA','personB','relationshipType','targetTimezone',...$keys];
        if(array_diff($required,array_keys($a))||array_diff(array_keys($a),[...$required,'locale'])){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}
        foreach($keys as $k){if(in_array($k,['year','month'],true)?!is_int($a[$k]):!is_string($a[$k])){throw new PublicError(422,'INVALID_REQUEST_SEMANTICS');}}
        try{$dates=PeriodCalendar::membership($kind,$a);}catch(\InvalidArgumentException $e){throw new PublicError(422,in_array($e->getMessage(),['UNSUPPORTED_DATE','INVALID_DATE_RANGE','DATE_RANGE_TOO_LARGE'],true)?$e->getMessage():'INVALID_REQUEST_SEMANTICS');}
        $daily=$a;foreach($keys as $k){unset($daily[$k]);}$daily['date']=$dates[0];
        $normalized=CompatibilityInput::parse(json_encode($daily,JSON_THROW_ON_ERROR),'application/json',null,true);unset($normalized['date']);
        foreach($keys as $k){$normalized[$k]=$a[$k];}return $normalized;
    }
}
