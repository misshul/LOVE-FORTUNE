<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Score;
use LoveFortune\Core\Engine\Saju\NatalCivilTime;

/** Gregorian date labels only; UTC is an arithmetic carrier, never a timezone authority. */
final class PeriodCalendar
{
    public static function dates(string $start,string $end,int $maximum=366): array
    {
        $a=NatalCivilTime::input($start,'00:00');$b=NatalCivilTime::input($end,'00:00');
        if($a>$b){throw new \InvalidArgumentException('INVALID_DATE_RANGE');}
        $count=intdiv($b-$a,86400000000)+1;
        if($count>$maximum){throw new \InvalidArgumentException('DATE_RANGE_TOO_LARGE');}
        $out=[];for($i=0;$i<$count;$i++){$out[]=NatalCivilTime::datetime($a+$i*86400000000)->format('Y-m-d');}return $out;
    }
    public static function membership(string $type,array $input): array
    {
        if($type==='RANGE'){return self::dates($input['startDate'],$input['endDate'],31);}
        if($type==='WEEK'){
            $day=NatalCivilTime::datetime(NatalCivilTime::input($input['weekStartDate'],'00:00'));
            if($day->format('N')!=='1'){throw new \InvalidArgumentException('INVALID_REQUEST_SEMANTICS');}
            return self::dates($day->format('Y-m-d'),$day->modify('+6 days')->format('Y-m-d'),7);
        }
        $year=$input['year']??null;
        if(!is_int($year)||$year<1900||$year>2099){throw new \InvalidArgumentException('UNSUPPORTED_DATE');}
        if($type==='YEAR'){return array_map(static fn($m)=>sprintf('%04d-%02d-01',$year,$m),range(1,12));}
        $month=$input['month']??null;
        if($type!=='MONTH'||!is_int($month)||$month<1||$month>12){throw new \InvalidArgumentException('INVALID_REQUEST_SEMANTICS');}
        $start=sprintf('%04d-%02d-01',$year,$month);
        $d=NatalCivilTime::datetime(NatalCivilTime::input($start,'00:00'));
        return self::dates($start,$d->modify('last day of this month')->format('Y-m-d'),31);
    }
}
