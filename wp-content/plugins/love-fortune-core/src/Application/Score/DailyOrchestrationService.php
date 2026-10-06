<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Score;

use LoveFortune\Core\Domain\Score\{CategoryResult,DailyAggregator};
use LoveFortune\Core\Engine\Saju\{FrozenDailySampleResolver,SajuDailyEvaluator,SajuDayPillarService,TimezoneReferenceRepository};
use LoveFortune\Core\Support\Rational as R;

final class DailyOrchestrationService
{
    // Immutable reference readers are reused only by this request-local service instance.
    private ?FrozenDailySampleResolver $resolver=null;
    private ?SajuDailyEvaluator $evaluator=null;
    public const VERSION='SAJU_DAILY_ORCHESTRATION_V1';
    public const WEIGHTS=['ATTRACTION'=>'0.12','EMOTION'=>'0.17','COMMUNICATION'=>'0.14','PASSION'=>'0.10','STABILITY'=>'0.15','HARMONY'=>'0.12','SUPPORT'=>'0.08'];
    public static function dependencies(): array
    {
        return ['combinedVersion'=>CombinedLifetimeService::VERSION,'dailyCatalogVersion'=>'1.0.0',
            'dailyCatalogChecksum'=>SajuDailyEvaluator::CATALOG_SHA256,'dailyReference'=>SajuDayPillarService::DAILY_REFERENCE,
            'timezoneReferenceVersion'=>TimezoneReferenceRepository::VERSION,'timezoneChecksum'=>TimezoneReferenceRepository::FILE_SHA256,
            'samples'=>FrozenDailySampleResolver::TIMES,'meanWeight'=>'3/4','peakWeight'=>'1/4','scale'=>'18',
            'categoryWeights'=>self::WEIGHTS,'sourceDenominator'=>'0.88','statusThresholds'=>['-5','-2','2','5'],
            'coverage'=>'DO01_PERIOD_PAIR_AVAILABILITY','confidence'=>'DO02_DAILY_ONLY','fallback'=>'DO05_BASELINE_NULL_FIRST',
            'candidates'=>'DO09_FEATURE_FIRST_PRESENT_OVER_TOTAL'];
    }
    public static function status(R $delta): string
    {
        $d=R::of($delta->halfUp4());
        return $d->compare(R::of(-5))<=0?'VERY_LOW':($d->compare(R::of(-2))<=0?'LOW':($d->compare(R::of(2))<0?'STABLE':($d->compare(R::of(5))<0?'GOOD':'VERY_GOOD')));
    }
    private function row(CategoryResult $baseline,array $slots,R $coverage,bool $eligible=true): array
    {
        $aggregate=(new DailyAggregator())->aggregate($slots); $available=$aggregate['sampleCount']>0;
        $signal=$available?$aggregate['signal']:null;
        $delta=$available&&$baseline->score!==null?$signal->multiply(R::of(18)):R::of(0);
        return ['baselineScore'=>$baseline->score,'signal'=>$signal,'delta'=>$delta,
            'score'=>$baseline->score===null?null:($available?$baseline->score->add($delta)->clamp(R::of(0),R::of(100)):$baseline->score),
            'coverage'=>$coverage,'resultConfidence'=>$baseline->score===null?R::of(0):$aggregate['confidence'],
            'dailyStatus'=>$baseline->score===null?'INSUFFICIENT_DATA':($available?self::status($delta):'INSUFFICIENT_PERIOD_DATA'),
            'availability'=>!$eligible?'STRUCTURALLY_INELIGIBLE_FOR_DAILY':($available?'COMPUTABLE':'UNAVAILABLE')];
    }
    /** Internal typed baseline and four pair category sample maps; never a rounded public response. */
    public function aggregate(array $combined,array $slots,string $date,string $timezone): array
    {
        if (($combined['combinedVersion']??null)!==CombinedLifetimeService::VERSION
            || LifetimeSourceEnvelope::canonical($combined['dependencies']??[])!==LifetimeSourceEnvelope::canonical(CombinedLifetimeService::DEPENDENCIES)
            || !($combined['overall']??null) instanceof CategoryResult
            || count($slots)!==4 || !array_is_list($slots)) { throw new \InvalidArgumentException('DAILY_DEPENDENCY_MISMATCH'); }
        \LoveFortune\Core\Engine\Saju\NatalCivilTime::input($date,'00:00');
        $timezone=($this->resolver??=new FrozenDailySampleResolver())->canonicalize($timezone);
        if (array_diff(LifetimeSourceEnvelope::CATEGORIES,array_keys($combined['categories']??[]))!==[] || count($combined['categories'])!==8) { throw new \InvalidArgumentException('DAILY_DEPENDENCY_MISMATCH'); }
        $overall=[]; $coverage=R::of(0);
        foreach ($slots as $slot) {
            if (count($slot)!==7 || array_diff(array_keys(self::WEIGHTS),array_keys($slot))!==[]) { throw new \InvalidArgumentException('INVALID_DAILY_SAMPLES'); }
            $sum=$conf=$weight=R::of(0);
            foreach (self::WEIGHTS as $cat=>$w) {
                $r=$slot[$cat]; if ($r===null) { continue; }
                // Reuse exact range validation before arithmetic, including zero signals.
                (new DailyAggregator())->aggregate([$r,null,null,null]);
                $w=R::of($w); $weight=$weight->add($w); $sum=$sum->add($w->multiply($r['signal'])); $conf=$conf->add($w->multiply($r['confidence']));
            }
            $coverage=$coverage->add($weight->divide(R::of('0.88')));
            $overall[]=$weight->compare(R::of(0))===0?null:['source'=>'SAJU','signal'=>$sum->divide(R::of('0.88')),'confidence'=>$conf->divide($weight)];
        }
        $categories=[];
        foreach (LifetimeSourceEnvelope::CATEGORIES as $cat) {
            $eligible=isset(self::WEIGHTS[$cat]); $samples=$eligible?array_column($slots,$cat):[null,null,null,null];
            $categories[$cat]=$this->row($combined['categories'][$cat],$samples,R::of(count(array_filter($samples,static fn($v)=>$v!==null)))->divide(R::of(4)),$eligible);
        }
        return ['dailyVersion'=>self::VERSION,'dependencies'=>self::dependencies(),'date'=>$date,'targetTimezone'=>$timezone,
            'overall'=>$this->row($combined['overall'],$overall,$coverage->divide(R::of(4))),
            'categories'=>$categories,'warnings'=>[],'contexts'=>$combined['contexts']??[]];
    }
    public function calculate(array $combined,array $a,array $b,string $date,string $timezone): array
    {
        $resolver=$this->resolver??=new FrozenDailySampleResolver(); $samples=$resolver->samples($date,$timezone);
        return $this->aggregate($combined,($this->evaluator??=new SajuDailyEvaluator())->evaluate($a,$b,$samples),$date,$timezone);
    }
}
