<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;
use LoveFortune\Core\Support\Rational as R;

final class SajuCoverageProjection
{
    public static function group(array $counts, R $weight): array
    {
        $e=$counts['eligibleCount']; $c=$counts['computableCount'];
        $ratio=$e===0?null:R::of($c)->divide(R::of($e));
        return ['candidateAvailabilityRatio'=>$ratio,'coverageEligibleWeight'=>$e===0?R::of(0):$weight,
            'coverageAvailableWeight'=>$ratio===null?R::of(0):$weight->multiply($ratio)];
    }

    public static function categories(array $groups): array
    {
        $out=[];
        foreach ($groups as $g) {
            $k=$g['category'];$out[$k]??=['eligibleWeight'=>R::of(0),'availableWeight'=>R::of(0)];
            $out[$k]['eligibleWeight']=$out[$k]['eligibleWeight']->add($g['coverageEligibleWeight']);
            $out[$k]['availableWeight']=$out[$k]['availableWeight']->add($g['coverageAvailableWeight']);
        }
        ksort($out,SORT_STRING);
        foreach ($out as &$v) { $v['coverage']=$v['eligibleWeight']->isZero()?R::of(0):$v['availableWeight']->divide($v['eligibleWeight']); } unset($v);
        return $out;
    }
}
