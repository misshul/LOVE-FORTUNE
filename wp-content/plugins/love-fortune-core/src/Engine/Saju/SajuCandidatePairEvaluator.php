<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

final class SajuCandidatePairEvaluator
{
    public function __construct(private readonly SajuRelationDetector $detector = new SajuRelationDetector()) {}

    public function evaluate(array $a,array $b): array
    {
        [$stemRule,$stemVariant]=$this->detector->dayRelation($a['dayStem'],$b['dayStem']);
        [$branchRule,$branchVariant]=$this->detector->dayRelation($a['dayBranch'],$b['dayBranch'],true);
        $whole=$this->detector->wholeChart($a,$b);
        return ['pairRef'=>'A:'.$a['candidateId'].'|B:'.$b['candidateId'],
            'variants'=>[$stemRule=>$stemVariant,$branchRule=>$branchVariant,'ELEMENT_COMPLEMENT'=>$whole['ELEMENT_COMPLEMENT'],'YIN_YANG_BALANCE'=>$whole['YIN_YANG_BALANCE']],
            'wholeChart'=>$whole,'tenGodsA'=>$this->detector->tenGod($a['dayStem'],$b['dayStem']),
            'tenGodsB'=>$this->detector->tenGod($b['dayStem'],$a['dayStem'])];
    }
}
