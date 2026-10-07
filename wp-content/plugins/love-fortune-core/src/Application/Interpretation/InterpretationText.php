<?php
declare(strict_types=1);
namespace LoveFortune\Core\Application\Interpretation;

use LoveFortune\Core\Api\CanonicalJson;

/** V1 fail-closed controlled language. Unsupported prose is repaired or falls back.
 * This is an acceptance grammar, not a claim that regex proves arbitrary prose safe.
 * Each numeric/date clause is generated from a typed signed field, never a global number bag.
 */
final class InterpretationText
{
    public const VERSION='AI_PROMPT_V1';
    public static function copy(string $locale): array
    {
        return match($locale){
            'ko-KR'=>['이 결과는 관계를 돌아보기 위한 경향 안내이며 확정적인 예측이 아닙니다.','서로의 생각을 확인하고 편안한 속도로 대화해 보세요.','검증된 결과를 참고하여 관계를 돌아볼 수 있습니다.','서명된 결과','참고할 날짜 순서','주의 깊게 살펴볼 날짜 순서','참고할 월 순서','주의 깊게 살펴볼 월 순서','월','해당 범주의 근거는 긍정적 경향을 나타냅니다.','해당 범주의 근거는 주의할 경향을 나타냅니다.','해당 범주의 근거에는 긍정적 경향과 주의할 경향이 함께 있습니다.'],
            'ja-JP'=>['この結果は関係を振り返るための傾向の案内であり、確定的な予測ではありません。','互いの考えを確かめ、無理のないペースで対話してみてください。','検証済みの結果を参考に関係を振り返ることができます。','署名済みの結果','参考にする日の順序','慎重に振り返る日の順序','参考にする月の順序','慎重に振り返る月の順序','月','この項目の根拠は前向きな傾向を示しています。','この項目の根拠は注意すべき傾向を示しています。','この項目の根拠には前向きな傾向と注意すべき傾向が共存しています。'],
            'en-US'=>['This result offers tendencies for reflection, not a deterministic prediction.','Check in with each other and communicate at a comfortable pace.','You can use the verified result to reflect on the relationship.','Signed result','Days to consider in ranked order','Days to reflect on carefully in ranked order','Months to consider in ranked order','Months to reflect on carefully in ranked order','month','The evidence for this category indicates a positive tendency.','The evidence for this category indicates a challenging tendency.','The evidence for this category includes both positive and challenging tendencies.'],
        };
    }
    public static function label(string $field,string $locale): string
    {
        $labels=[
            'overallScore'=>['궁합 점수','相性スコア','Compatibility score'], 'lifetimeScore'=>['기본 궁합 점수','基本相性スコア','Lifetime score'],
            'dailyScore'=>['일간 점수','日間スコア','Daily score'],'weeklyScore'=>['주간 점수','週間スコア','Weekly score'],
            'monthlyScore'=>['월간 점수','月間スコア','Monthly score'],'yearlyScore'=>['연간 점수','年間スコア','Yearly score'],
            'dailyDelta'=>['일간 변화량','日間変化量','Daily delta'],'periodDelta'=>['기간 변화량','期間変化量','Period delta'],
            'coverage'=>['데이터 평가 가용성','データ評価の可用性','Data evaluation availability'],
            'resultConfidence'=>['계약상 결과 신뢰도','契約上の結果信頼度','Contract-defined result confidence'],
            'volatility'=>['변동성','変動性','Volatility'],'yearlyVolatility'=>['연간 변동성','年間変動性','Yearly volatility'],
            'slope'=>['기울기','傾き','Slope'],'status'=>['상태','状態','Status'],'dailyStatus'=>['일간 상태','日間状態','Daily status'],
            'trendStatus'=>['변화 수준','変化の程度','Trend magnitude'],'trendDirection'=>['변화 방향','変化の方向','Trend direction'],
            'date'=>['대상 날짜','対象日','Target date'],
        ];
        return $labels[$field][array_search($locale,['ko-KR','ja-JP','en-US'],true)];
    }
    public static function clauses(array $dto): array
    {
        $c=self::copy($dto['locale']);$lines=[$c[0],$c[1],$c[2]];
        foreach(['overallScore','lifetimeScore','dailyScore','weeklyScore','monthlyScore','yearlyScore','dailyDelta','periodDelta','coverage','resultConfidence','volatility','yearlyVolatility','slope','status','dailyStatus','trendStatus','trendDirection','date'] as $field){
            if(!array_key_exists($field,$dto)||$dto[$field]===null){continue;}
            $v=$dto[$field];$value=is_string($v)?$v:CanonicalJson::encode($v);
            $lines[]=self::label($field,$dto['locale']).': '.$value;
        }
        foreach(['bestDays'=>4,'cautionDays'=>5,'bestMonths'=>6,'cautionMonths'=>7] as $field=>$index){
            if(empty($dto[$field])){continue;}
            $dates=array_map(static fn($r)=>$dto['resultType']==='Yearly'?substr($r['date'],0,7).' ('.$c[8].')':$r['date'],$dto[$field]);
            $lines[]=$c[$index].': '.implode(', ',$dates);
        }
        return $lines;
    }
    public static function evidenceText(array $feature,string $locale): string
    {
        $c=self::copy($locale);
        $index=match($feature['direction']){'POSITIVE'=>9,'NEGATIVE'=>10,'MIXED'=>11,default=>throw new \InvalidArgumentException('EVIDENCE')};
        // Category/subject are closed validated identifiers, not raw provider/user text.
        return $feature['subject'].' / '.$feature['category'].': '.$c[$index];
    }
    public static function fallback(array $dto): array
    {
        $c=self::copy($dto['locale']);$lines=self::clauses($dto);
        // Include one result-bound clause when available, without manufacturing a score.
        return ['summary'=>$c[0]."\n".($lines[3]??$c[2]),'strengths'=>[],'challenges'=>[],'advice'=>$c[1]];
    }
    public static function validate(mixed $content,array $dto): array
    {
        if(!is_array($content)||array_is_list($content)||count($content)!==4||array_diff(['summary','strengths','challenges','advice'],array_keys($content))!==[]){return ['SHAPE'];}
        $allowed=self::clauses($dto);$allText=[];
        foreach(['summary'=>1200,'advice'=>1600] as $key=>$max){
            if(!self::text($content[$key],$max)){return ['TEXT_LIMIT_OR_MARKUP'];}
            foreach(explode("\n",$content[$key]) as $line){if(!in_array(trim($line),$allowed,true)){return ['UNSUPPORTED_OR_UNGROUNDED_CONTENT'];}}
            $allText[]=$content[$key];
        }
        $evidence=array_column($dto['evidence'],null,'featureId');
        foreach(['strengths','challenges'] as $kind){
            if(!is_array($content[$kind])||!array_is_list($content[$kind])||count($content[$kind])>5){return ['ITEM_COUNT'];}
            foreach($content[$kind] as $item){
                if(!is_array($item)||count($item)!==2||!array_key_exists('text',$item)||!array_key_exists('evidenceRefs',$item)||!self::text($item['text'],500)){return ['ITEM_SHAPE_OR_TEXT'];}
                $refs=$item['evidenceRefs'];if(!is_array($refs)||!array_is_list($refs)||$refs===[]){return ['EVIDENCE'];}
                $seen=[];
                foreach($refs as $ref){
                    if(!is_string($ref)||isset($seen[$ref])||!isset($evidence[$ref])){return ['EVIDENCE'];}$seen[$ref]=true;$f=$evidence[$ref];
                    if(!in_array($f['direction'],[$kind==='strengths'?'POSITIVE':'NEGATIVE','MIXED'],true)||trim($item['text'])!==self::evidenceText($f,$dto['locale'])){return ['EVIDENCE_GROUNDING'];}
                }
            }
        }
        if(!in_array(self::copy($dto['locale'])[0],array_map('trim',explode("\n",implode("\n",$allText))),true)){return ['LIMITATION_FRAMING'];}
        return [];
    }
    private static function text(mixed $v,int $max): bool
    {
        if(!is_string($v)||!preg_match('//u',$v)||trim($v)===''){return false;}
        $n=preg_match_all('/./us',$v);
        return $n!==false&&$n<=$max&&!preg_match('/[<>\x00-\x08\x0B-\x1F\x7F]|on[a-z]+\s*=|```/iu',$v);
    }
}
