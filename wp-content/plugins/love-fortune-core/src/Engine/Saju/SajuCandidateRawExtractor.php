<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

/** Consumes trusted internal FP06 arrays, never birth input or public DTOs. */
final class SajuCandidateRawExtractor
{
    public function __construct(private readonly SajuFeatureCatalog $catalog = new SajuFeatureCatalog()) {}

    public function extract(array $c): array
    {
        $fail = static function (): never { throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE'); };
        if (!is_string($c['candidateId'] ?? null) || !preg_match('/^C(?:0|[1-9][0-9]*)$/D', $c['candidateId'])) { $fail(); }
        $y = $c['yearPillar'] ?? []; $m = $c['monthPillar'] ?? []; $d = $c['dayPillar']['pillar'] ?? []; $h = $c['hourPillar'] ?? [];
        foreach ([$y,$m,$d,$h] as $p) { if (!is_array($p)) { $fail(); } }
        foreach ([[$y,'formulaVersion',YearPillarCalculator::FORMULA_VERSION], [$m,'formulaVersion',MonthPillarCalculator::FORMULA_VERSION],
            [$h,'hourPillarVersion',HourPillarCalculator::VERSION], [$h,'dayPillarVersion',DayPillarCalculator::IDENTIFIER]] as [$p,$key,$v]) {
            if (($p[$key] ?? null) !== $v) { throw new \InvalidArgumentException('REFERENCE_VERSION_MISMATCH'); }
        }
        foreach ([$y,$m] as $p) {
            foreach (['solarTermReferenceVersion'=>SolarTermReference::VERSION,'timeScaleBridgeVersion'=>SolarTermReference::BRIDGE,'artifactChecksum'=>SolarTermReference::CHECKSUM] as $key=>$v) {
                if (($p[$key]??null)!==$v) { throw new \InvalidArgumentException('REFERENCE_VERSION_MISMATCH'); }
            }
        }
        if (!is_bool($h['known'] ?? null) || ($c['provenance']['correctionApplications'] ?? null) !== 1
            || ($c['provenance']['correctionVersion'] ?? null) !== 'KOREAN_LONGITUDE_2330'
            || ($m['yearStemIndex'] ?? null) !== ($y['stemIndex'] ?? null)
            || ($m['sajuYear'] ?? null) !== ($y['sajuYear'] ?? null)) { $fail(); }
        if ($h['known']) {
            if (($c['kind'] ?? null) !== 'POINT' || ($h['dayStemIndexUsed'] ?? null) !== ($d['stemIndex'] ?? null)
                || ($h['calculationDateUsed'] ?? null) !== ($c['dayPillar']['calculationDate'] ?? null)) { $fail(); }
        } else {
            if (($c['kind'] ?? null) !== 'INTERVAL') { $fail(); }
            foreach (['hourIndex','stemIndex','branchIndex','stem','branch','ganzhi','dayStemIndexUsed','calculationDateUsed'] as $key) {
                if (!array_key_exists($key,$h) || $h[$key] !== null) { $fail(); }
            }
        }
        $pillars = ['YEAR'=>$y,'MONTH'=>$m,'DAY'=>$d]; if ($h['known']) { $pillars['HOUR']=$h; }
        $t = $this->catalog->tables(); $counts = array_fill_keys($t['elements'],0); $polarity = ['YANG'=>0,'YIN'=>0]; $tokens=[];
        foreach ($pillars as $role=>$p) {
            foreach (['stem'=>10,'branch'=>12] as $type=>$limit) {
                $i=$p[$type.'Index']??null; $symbol=$p[$type]??null;
                if (!is_int($i) || $i<0 || $i >= $limit || !is_string($symbol) || $t[$type==='branch'?'branches':'stems'][$i]!==$symbol) {
                    throw new \InvalidArgumentException('UNSUPPORTED_PILLAR_VALUE');
                }
                $element=$t[$type.'Elements'][$i]; $yinYang=$type==='branch'?SajuBranchPolarity::of($i,$symbol):($i%2===0?'YANG':'YIN');
                $tokens[]=['pillar'=>$role,'type'=>$type,'index'=>$i,'symbol'=>$symbol,'element'=>$element,'polarity'=>$yinYang];
                $counts[$element]++;$polarity[$yinYang]++;
            }
            if (($p['stemIndex']%2)!==($p['branchIndex']%2) || ($p['ganzhi']??null)!==$p['stem'].$p['branch']) { $fail(); }
        }
        if ($h['known'] && $h['stemIndex'] !== (2 * ($d['stemIndex'] % 5) + $h['branchIndex']) % 10) { $fail(); }
        foreach ([$y,$d] as $p) {
            $i=$p['cycleIndex']??null;
            if (!is_int($i) || $i<0 || $i>=60 || $p['stemIndex']!==$i%10 || $p['branchIndex']!==$i%12) { $fail(); }
        }
        $month=$m['monthIndex']??null;
        if (!is_int($y['sajuYear']??null) || (($y['sajuYear']-1984)%60+60)%60!==$y['cycleIndex']
            || !is_int($month) || $month<0 || $month>11 || $m['branchIndex']!==($month+2)%12
            || $m['stemIndex']!==(2*($y['stemIndex']%5)+2+$month)%10
            || ($h['timeBasis']??null)!=='ADJUSTED_CIVIL_TIME'
            || ($h['boundaryConvention']??null)!=='ZI_2300_TWO_HOUR_HALF_OPEN_V1'
            || ($h['known'] && ($h['hourIndex']??null)!==$h['branchIndex'])) { $fail(); }
        return ['candidateId'=>$c['candidateId'],'hourKnown'=>$h['known'],'tokens'=>$tokens,'elementCounts'=>$counts,
            'polarityCounts'=>$polarity,'knownTokenCount'=>count($tokens),'dayStem'=>$d['stemIndex'],'dayBranch'=>$d['branchIndex'],
            'dayStemElement'=>$t['stemElements'][$d['stemIndex']],'dayBranchElement'=>$t['branchElements'][$d['branchIndex']]];
    }
}
