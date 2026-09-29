<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

use LoveFortune\Core\Support\Rational as R;

/** Approved visible-token and Day-pair relations only. */
final class SajuRelationDetector
{
    public function __construct(private readonly SajuFeatureCatalog $catalog = new SajuFeatureCatalog()) {}

    public function dayRelation(int $a, int $b, bool $branch = false): array
    {
        $t=$this->catalog->tables(); $kind=$branch?'branch':'stem'; $limit=$branch?12:10;
        if ($a<0 || $b<0 || $a >= $limit || $b >= $limit) { throw new \InvalidArgumentException('UNSUPPORTED_PILLAR_VALUE'); }
        foreach ($branch?['liuhe'=>['BRANCH_COMBINATION','LIUHE'],'clash'=>['BRANCH_CLASH','CLASH']]:['stemCombinations'=>['STEM_COMBINATION','COMB']] as $table=>$owner) {
            foreach ($t[$table] as [$x,$y]) {
                $symbols=$t[$branch?'branches':'stems'];
                if (($symbols[$a]===$x && $symbols[$b]===$y) || ($symbols[$a]===$y && $symbols[$b]===$x)) { return $owner; }
            }
        }
        $x=array_search($t[$kind.'Elements'][$a],$t['elements'],true); $y=array_search($t[$kind.'Elements'][$b],$t['elements'],true);
        $distance=abs($x-$y);
        $relation=$distance===0?'SAME':(in_array($distance,[1,4],true)?'GENERATION':'CONTROL');
        return $branch?['DAY_BRANCH_RELATION','B'.$relation]:[match($relation){'SAME'=>'DAY_MASTER_RELATION','GENERATION'=>'ELEMENT_SUPPORT','CONTROL'=>'ELEMENT_CONTROL'},$relation];
    }

    public function tenGod(int $self, int $other): string
    {
        if ($self<0 || $self>9 || $other<0 || $other>9) { throw new \InvalidArgumentException('UNSUPPORTED_PILLAR_VALUE'); }
        $delta=(intdiv($other,2)-intdiv($self,2)+5)%5;
        $relation=['SAME','PRODUCES','CONTROLS','CONTROLLED_BY','PRODUCED_BY'][$delta];
        return $this->catalog->tables()['tenGods'][$relation][($self%2)==($other%2)?0:1];
    }

    public function band(string $ruleId, R $value): ?string
    {
        if (!in_array($ruleId,['ELEMENT_COMPLEMENT','YIN_YANG_BALANCE'],true)
            || $value->compare(R::of(0))<0 || $value->compare(R::of($ruleId==='ELEMENT_COMPLEMENT'?'1/2':1))>0) {
            throw new \InvalidArgumentException('UNSUPPORTED_PILLAR_VALUE');
        }
        foreach ($this->catalog->rule($ruleId)['variants'] as $v) {
            $b=$v['band']; $high=$value->compare(R::of((string)$b['maximum']));
            if ($value->compare(R::of((string)$b['minimum']))>=0 && ($high<0 || ($high===0 && $b['maximumInclusive']))) { return $v['variant']; }
        }
        return null;
    }

    public function wholeChart(array $a, array $b): array
    {
        $pa=[];$pb=[];$joint=[];
        foreach ($this->catalog->tables()['elements'] as $e) {
            $pa[]=R::of($a['elementCounts'][$e])->divide(R::of($a['knownTokenCount']));
            $pb[]=R::of($b['elementCounts'][$e])->divide(R::of($b['knownTokenCount']));
        }
        foreach ($pa as $i=>$p) { $joint[]=$p->add($pb[$i])->divide(R::of(2)); }
        $balance=static function(array $p): R {
            $sum=R::of(0); foreach($p as $v){$sum=$sum->add($v->subtract(R::of('1/5'))->abs());}
            return R::of(1)->subtract($sum->divide(R::of('8/5')));
        };
        $gain=$balance($joint)->subtract($balance($pa)->add($balance($pb))->divide(R::of(2)));
        $yang=$a['polarityCounts']['YANG']+$b['polarityCounts']['YANG']; $yin=$a['polarityCounts']['YIN']+$b['polarityCounts']['YIN'];
        $yy=R::of(1)->subtract(R::of(abs($yang-$yin))->divide(R::of($yang+$yin)));
        return ['distributionA'=>$pa,'distributionB'=>$pb,'jointDistribution'=>$joint,'complementGain'=>$gain,'yinYangBalance'=>$yy,
            'baseConfidence'=>R::of($a['knownTokenCount']+$b['knownTokenCount'])->divide(R::of(16)),
            'ELEMENT_COMPLEMENT'=>$this->band('ELEMENT_COMPLEMENT',$gain),'YIN_YANG_BALANCE'=>$this->band('YIN_YANG_BALANCE',$yy)];
    }
}
