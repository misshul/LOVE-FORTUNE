<?php
declare(strict_types=1);
namespace LoveFortune\Core\Engine\Saju;

/** Internal row validation; malformed evidence never silently becomes unavailable. */
final class SajuEvidenceLedger
{
    public const STATES=['MATCHED','NOT_MATCHED','UNAVAILABLE','DEFERRED','ERROR'];

    public static function counts(array $rows): array
    {
        $counts=array_fill_keys(self::STATES,0); $seen=[];
        foreach ($rows as $row) {
            if (!is_array($row) || !in_array($row['state']??null,self::STATES,true) || $row['state']==='ERROR') {
                throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR');
            }
            $ref=$row['pairRef']??null;
            if (!is_string($ref) || !preg_match('/^A:C(?:0|[1-9][0-9]*)\|B:C(?:0|[1-9][0-9]*)$/D',$ref) || isset($seen[$ref])) {
                throw new \InvalidArgumentException('INVALID_CANDIDATE_LINEAGE');
            }
            $seen[$ref]=true;
            if (($row['state']==='MATCHED') !== is_string($row['ruleVariant']??null)
                || array_key_exists('signedValue',$row)) { throw new \InvalidArgumentException('FEATURE_EVIDENCE_ERROR'); }
            $counts[$row['state']]++;
        }
        $c=$counts['MATCHED']+$counts['NOT_MATCHED'];
        return ['eligibleCount'=>$c+$counts['UNAVAILABLE'],'computableCount'=>$c,'totalCount'=>$c,'presentCount'=>$counts['MATCHED'],'deferredCount'=>$counts['DEFERRED']];
    }
}
