<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Interpretation;

/** Approved V2 presentation only: no score/status/confidence calculation. */
final class CompatibilityFallback
{
    public const ORDER=['ATTRACTION','EMOTION','PASSION','STABILITY','HARMONY','SUPPORT','LONG_TERM'];
    public static function templates(string $locale): array
    {
        return match($locale){
            'ko-KR'=>[
                'statuses'=>array_combine(['CAUTION','BALANCED','GOOD','VERY_GOOD','EXCELLENT'],[
                    '차이와 기대를 차분히 확인해 볼 구간입니다.',
                    '균형을 살피며 서로에게 편안한 방식을 찾아볼 구간입니다.',
                    '관계의 조화를 활용해 볼 구간입니다.',
                    '조화로운 경향을 일상의 협력으로 이어가 볼 구간입니다.',
                    '높게 나타난 조화의 경향을 참고하되 실제 경험도 함께 살펴보세요.']),
                'labels'=>array_combine(self::ORDER,['관심과 접근 방식','감정 교류','활동 에너지','일상의 일관성','선호의 조율','서로 돕는 방식','장기적인 방향']),
                'high'=>'확인 가능한 항목 중 {label} 항목이 상대적으로 높게 나타납니다.',
                'low'=>'{label} 항목에서는 서로의 기대와 편안한 방식을 확인해 보세요.',
                'complete'=>'계산 신뢰 지표는 실제 관계의 결과를 보장하지 않습니다.',
                'missing'=>'평가되지 않은 항목이 있어 확인 가능한 결과를 중심으로 참고해 주세요.',
            ],
            'ja-JP'=>[
                'statuses'=>array_combine(['CAUTION','BALANCED','GOOD','VERY_GOOD','EXCELLENT'],[
                    '違いや期待を落ち着いて確かめたい範囲です。',
                    'バランスを見ながら、互いに無理のない関わり方を探す範囲です。',
                    '関係の調和を生かすことを考えられる範囲です。',
                    '調和の傾向を日々の協力につなげることを考えられる範囲です。',
                    '高く表れた調和の傾向を参考にしつつ、実際の経験も大切にしてください。']),
                'labels'=>array_combine(self::ORDER,['関心と近づき方','感情の交流','活動のエネルギー','日常の一貫性','好みのすり合わせ','支え合い方','長期的な方向性']),
                'high'=>'評価できる項目の中では、{label}の項目が相対的に高く表れています。',
                'low'=>'{label}の項目では、互いの期待や無理のない関わり方を確かめてみてください。',
                'complete'=>'計算上の信頼指標は、実際の関係の結果を保証するものではありません。',
                'missing'=>'評価されていない項目があるため、確認できる結果を中心に参考にしてください。',
            ],
            'en-US'=>[
                'statuses'=>array_combine(['CAUTION','BALANCED','GOOD','VERY_GOOD','EXCELLENT'],[
                    'This range invites a careful check of differences and expectations.',
                    'This range invites attention to balance and a comfortable way of relating.',
                    'This range suggests opportunities to draw on harmony in the relationship.',
                    'This range suggests exploring how harmonious tendencies can support everyday cooperation.',
                    'Consider the strongly indicated harmony alongside your actual experiences.']),
                'labels'=>array_combine(self::ORDER,['interest and approach','emotional exchange','activity and energy','everyday consistency','coordination of preferences','mutual support','long-term direction']),
                'high'=>'Among the available results, the area of {label} is relatively higher.',
                'low'=>"For {label}, consider checking each other's expectations and what feels comfortable.",
                'complete'=>'Calculation confidence indicators do not guarantee real-world relationship outcomes.',
                'missing'=>'Some areas were not evaluated, so use the available results as a limited reference.',
            ],
        };
    }
    public static function content(array $dto): array
    {
        $c=InterpretationText::copy($dto['locale']);$t=self::templates($dto['locale']);
        // No new insufficient-status wording: use the existing neutral summary.
        $status=$dto['status']??null;
        if($status==='INSUFFICIENT_DATA'){$sentence=$c[2];}
        elseif(is_string($status)&&isset($t['statuses'][$status])){$sentence=$t['statuses'][$status];}
        else{throw new \UnexpectedValueException('TEMPLATE_SELECTION');}
        $valid=[];
        foreach(self::ORDER as $category){
            $row=$dto['categories'][$category]??[];$score=$row['score']??null;
            if($score===null||($row['status']??null)==='INSUFFICIENT_DATA'){continue;}
            if((!is_int($score)&&!is_float($score))||!is_finite((float)$score)){throw new \UnexpectedValueException('TEMPLATE_SELECTION');}
            $valid[$category]=$score;
        }
        $lines=[$c[0],$sentence];$advice=$c[1];
        if(count($valid)>=2&&min($valid)<max($valid)){
            // Strict improvements retain the first canonical category on ties.
            $high=$low=array_key_first($valid);
            foreach($valid as $category=>$score){if($score>$valid[$high]){$high=$category;}if($score<$valid[$low]){$low=$category;}}
            $lines[]=str_replace('{label}',$t['labels'][$high],$t['high']);
            $advice=str_replace('{label}',$t['labels'][$low],$t['low']);
        }
        $lines[]=$t[count($valid)<count(self::ORDER)?'missing':'complete'];
        return ['summary'=>implode("\n",$lines),'strengths'=>[],'challenges'=>[],'advice'=>$advice];
    }
}
