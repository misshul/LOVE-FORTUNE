<?php
declare(strict_types=1);
namespace LoveFortune\Core\Domain\Score;

use LoveFortune\Core\Support\Rational as R;

/** Exact category kernel. Call after catalog/identity validation; never used by Daily. */
final class SajuCategoryScorer
{
    public const GUARDRAIL_VERSION = '2.0.0';

    public function score(array $features, R $coverage): array
    {
        $zero=R::of(0);$one=R::of(1);$fifty=R::of(50);
        if($coverage->compare($zero)<0 || $coverage->compare($one)>0){throw new \InvalidArgumentException('INVALID_FEATURE_EVIDENCE');}
        $availableWeight=$weightedConfidence=$w0=$numerator=$zero;$usable=[];$seen=[];
        foreach($features as $f){
            if(!is_array($f) || !is_string($f['featureId']??null) || isset($seen[$f['featureId']])
                || !($f['signedValue']??null) instanceof R || !($f['confidence']??null) instanceof R
                || !($f['preConfidenceWeight']??null) instanceof R){throw new \InvalidArgumentException('INVALID_FEATURE_EVIDENCE');}
            $seen[$f['featureId']]=true;$v=$f['signedValue'];$c=$f['confidence'];$p=$f['preConfidenceWeight'];
            if($v->abs()->compare($one)>0 || $c->compare($zero)<0 || $c->compare($one)>0 || $p->compare($zero)<0){throw new \InvalidArgumentException('INVALID_FEATURE_EVIDENCE');}
            $availableWeight=$availableWeight->add($p);$weightedConfidence=$weightedConfidence->add($p->multiply($c));
            if($c->isZero() || $p->isZero()){continue;}
            $e=$p->multiply($c);$w0=$w0->add($e);$numerator=$numerator->add($e->multiply($v));
            $usable[$f['featureId']]=['signedValue'=>$v,'effectiveWeight'=>$e];
        }
        ksort($usable,SORT_STRING);
        if($usable===[]){
            return ['result'=>CategoryResult::unavailable($coverage),'diagnostics'=>[
                'W0'=>$zero,'diagnosticRawScore'=>null,'guardedScore'=>null,'stabilizedScore'=>null,
                'categoryResultConfidence'=>$zero,'usableFeatureCount'=>0,'evidenceRefs'=>[],
                'features'=>[],'neutralMass'=>$zero]];
        }
        if($coverage->isZero()){throw new \InvalidArgumentException('INVALID_FEATURE_EVIDENCE');}
        $adjustedNumerator=$adjustedSum=$zero;$details=[];
        foreach($usable as $id=>$f){
            $e=$f['effectiveWeight'];$v=$f['signedValue'];$impact=$fifty->multiply($e)->multiply($v->abs())->divide($w0);
            $adjusted=$impact->compare(R::of(20))>0?R::of('2/5')->multiply($w0)->divide($v->abs()):$e;
            $after=$fifty->multiply($adjusted)->multiply($v->abs())->divide($w0);
            if($adjusted->compare($e)>0 || $after->compare(R::of(20))>0){throw new \RuntimeException('SCORING_ORCHESTRATION_FAILED');}
            $adjustedSum=$adjustedSum->add($adjusted);$adjustedNumerator=$adjustedNumerator->add($adjusted->multiply($v));
            $details[$id]=['effectiveWeight'=>$e,'adjustedWeight'=>$adjusted,'impactPoints'=>$impact,'guardedImpactPoints'=>$after,'capped'=>$adjusted->compare($e)<0];
        }
        $raw=$fifty->add($fifty->multiply($numerator)->divide($w0));
        $guarded=$fifty->add($fifty->multiply($adjustedNumerator)->divide($w0));
        $stabilized=$fifty->add($guarded->subtract($fifty)->multiply($coverage));
        $pre=$weightedConfidence->divide($availableWeight);
        return ['result'=>new CategoryResult($stabilized,$coverage,$coverage->multiply($pre)),
            'diagnostics'=>['W0'=>$w0,'diagnosticRawScore'=>$raw,'guardedScore'=>$guarded,'stabilizedScore'=>$stabilized,
                'categoryResultConfidence'=>$pre,'usableFeatureCount'=>count($usable),'evidenceRefs'=>array_keys($usable),
                'features'=>$details,'neutralMass'=>$w0->subtract($adjustedSum)]];
    }
}
