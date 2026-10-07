<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

final class InterpretationInput
{
    public static function parse(string $body, string $media, ?string $encoding): array
    {
        if (strlen($body)>65536) { throw new PublicError(413,'PAYLOAD_TOO_LARGE'); }
        if ($encoding!==null) { throw new PublicError(415,'UNSUPPORTED_CONTENT_ENCODING'); }
        if (!preg_match('/^application\/json(?:;\s*charset=utf-8)?$/iD',$media)) { throw new PublicError(415,'UNSUPPORTED_MEDIA_TYPE'); }
        try { $o=json_decode($body,false,8,JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new PublicError(400,'INVALID_REQUEST'); }
        if (!$o instanceof \stdClass) { throw new PublicError(400,'INVALID_REQUEST'); }
        $in=(array)$o;
        if (array_diff(array_keys($in),['signedContext','locale'])!==[] || !is_string($in['signedContext']??null)) { throw new PublicError(400,'INVALID_REQUEST'); }
        if (strlen($in['signedContext'])>65499 || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]{43}$/D',$in['signedContext'])) { throw new PublicError(422,'INVALID_INTERPRETATION_CONTEXT'); }
        if (!array_key_exists('locale',$in)) { $in['locale']='ko-KR'; }
        if (!in_array($in['locale'],['ko-KR','ja-JP','en-US'],true)) { throw new PublicError(422,'UNSUPPORTED_LOCALE'); }
        return $in;
    }
}
