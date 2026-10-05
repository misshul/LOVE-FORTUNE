<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

/** Canonical UTF-8 JSON. Decimal output is expanded, never exponent notation. */
final class CanonicalJson
{
    public static function encode(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $list=is_array($value) && array_is_list($value);$rows=(array)$value;
            if (!$list) { ksort($rows,SORT_STRING); }
            $out=[];foreach($rows as $key=>$item){$out[]=($list?'':self::encode((string)$key).':').self::encode($item);}
            return ($list?'[':'{').implode(',',$out).($list?']':'}');
        }
        if (is_float($value)) {
            if (!is_finite($value)) { throw new \InvalidArgumentException('Non-finite JSON'); }
            if ($value==0) { return '0'; }
            $s=json_encode($value,JSON_THROW_ON_ERROR);
            if (stripos($s,'e')!==false) {
                [$mantissa,$exponent]=preg_split('/e/i',$s);$negative=str_starts_with($mantissa,'-');
                $mantissa=ltrim($mantissa,'-');$point=strpos($mantissa,'.');$point=$point===false?strlen($mantissa):$point;
                $digits=str_replace('.','',$mantissa);$point+=(int)$exponent;
                $s=$point<=0?'0.'.str_repeat('0',-$point).$digits:($point>=strlen($digits)?$digits.str_repeat('0',$point-strlen($digits)):substr($digits,0,$point).'.'.substr($digits,$point));
                if($negative){$s='-'.$s;}
            }
            return str_contains($s,'.')?rtrim(rtrim($s,'0'),'.'):$s;
        }
        if (is_string($value) && preg_match('/[^\x00-\x7f]/',$value)) {
            if (!class_exists(\Normalizer::class)) { throw new \RuntimeException('NFC support unavailable'); }
            $value=\Normalizer::normalize($value,\Normalizer::FORM_C);
        }
        return json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }
}
