<?php
declare(strict_types=1);
use LoveFortune\Core\Application\Interpretation\{InterpretationText as T,CompatibilityFallback as F,InterpretationService,ProviderInput,ProviderFailure};
require_once dirname(__DIR__).'/interpretation-support.php';

final class FallbackV2Test extends \PHPUnit\Framework\TestCase
{
    private function dto(array $scores=[],string $status='GOOD',string $locale='en-US'): array
    {
        $rows=[];
        foreach(['ATTRACTION','EMOTION','PASSION','STABILITY','HARMONY','SUPPORT','LONG_TERM','COMMUNICATION'] as $key){
            $score=$scores[$key]??null;$rows[$key]=['score'=>$score,'status'=>$score===null?'INSUFFICIENT_DATA':'GOOD','coverage'=>1,'resultConfidence'=>1];
        }
        return ['resultType'=>'Compatibility','locale'=>$locale,'aiPromptVersion'=>'AI_PROMPT_V2','status'=>$status,'overallScore'=>60,'categories'=>$rows,'coverage'=>1,'resultConfidence'=>1,'evidence'=>[]];
    }
    public function testIndependentTwelveApprovedGoldenOutputs(): void
    {
        $gold=json_decode(file_get_contents(dirname(__DIR__).'/fixtures/fallback-v2.json'),true,512,JSON_THROW_ON_ERROR);
        foreach($gold['expected'] as $locale=>$cases){foreach($cases as $id=>$expected){
            $case=$gold['cases'][$id];$dto=$this->dto($case['scores'],$case['status'],$locale);
            self::assertSame($expected,T::fallback($dto),$locale.'/'.$id);
            self::assertSame([],T::validateFallback($expected,$dto));
            self::assertSame([],T::validate($expected,$dto));
            $bad=$expected;$bad['summary'].="\n";self::assertNotSame([],T::validateFallback($bad,$dto));
        }}
    }
    public function testCanonicalTiesPermutationAndChangingExtremes(): void
    {
        $dto=$this->dto(['ATTRACTION'=>80,'EMOTION'=>80,'PASSION'=>20,'STABILITY'=>20]);$out=T::fallback($dto);
        self::assertStringContainsString('area of interest and approach',$out['summary']);
        self::assertStringStartsWith('For activity and energy,',$out['advice']);
        $dto['categories']=array_reverse($dto['categories'],true);self::assertSame($out,T::fallback($dto));
        $dto['categories']['EMOTION']['score']=81;$new=T::fallback($dto);
        self::assertStringContainsString('area of emotional exchange',$new['summary']);self::assertSame($out['advice'],$new['advice']);
        $dto['categories']['STABILITY']['score']=19;self::assertStringStartsWith('For everyday consistency,',T::fallback($dto)['advice']);
    }
    public function testZeroOneAllTiedAndCommunicationExcluded(): void
    {
        foreach([[],['EMOTION'=>50],['EMOTION'=>50,'PASSION'=>50]] as $scores){
            $dto=$this->dto($scores);$out=T::fallback($dto);
            self::assertStringNotContainsString('relatively',$out['summary']);self::assertSame(T::copy('en-US')[1],$out['advice']);
            foreach([0,100] as $score){$dto['categories']['COMMUNICATION']=['score'=>$score,'status'=>'GOOD'];self::assertSame($out,T::fallback($dto));}
        }
        $dto=$this->dto(['ATTRACTION'=>75,'EMOTION'=>25]);$out=T::fallback($dto);
        foreach([0,100] as $score){$dto['categories']['COMMUNICATION']=['score'=>$score,'status'=>'GOOD'];self::assertSame($out,T::fallback($dto));}
    }
    public function testMissingAndConfidenceCannotChangeRanks(): void
    {
        $dto=$this->dto(array_fill_keys(F::ORDER,50));$dto['categories']['PASSION']['score']=80;$base=T::fallback($dto);
        self::assertStringContainsString('Calculation confidence indicators',$base['summary']);
        foreach([0,0.001,0.5,1] as $value){$dto['coverage']=$dto['resultConfidence']=$value;foreach($dto['categories'] as &$r){$r['coverage']=$r['resultConfidence']=$value;}unset($r);self::assertSame($base,T::fallback($dto));}
        $dto['categories']['EMOTION']=['score'=>0,'status'=>'INSUFFICIENT_DATA'];$out=T::fallback($dto);
        self::assertStringContainsString('Some areas were not evaluated',$out['summary']);self::assertStringStartsWith('For interest and approach,',$out['advice']);
        $dto['categories']['EMOTION']=['score'=>null,'status'=>'GOOD'];self::assertSame($out,T::fallback($dto));
    }
    public function testStatusIsUsedDirectlyIncludingBoundaryScores(): void
    {
        $expected=[
            'CAUTION'=>'This range invites a careful check of differences and expectations.',
            'BALANCED'=>'This range invites attention to balance and a comfortable way of relating.',
            'GOOD'=>'This range suggests opportunities to draw on harmony in the relationship.',
            'VERY_GOOD'=>'This range suggests exploring how harmonious tendencies can support everyday cooperation.',
            'EXCELLENT'=>'Consider the strongly indicated harmony alongside your actual experiences.',
        ];
        foreach($expected as $status=>$text){$dto=$this->dto([], $status);$base=T::fallback($dto);self::assertSame($text,explode("\n",$base['summary'])[1]);
            foreach([null,0,44.9999,45,59.9999,60,74.9999,75,84.9999,85,100] as $score){$dto['overallScore']=$score;self::assertSame($base,T::fallback($dto));}
        }
        $dto=$this->dto([],'INSUFFICIENT_DATA');self::assertStringContainsString(T::copy('en-US')[2],T::fallback($dto)['summary']);
    }
    public function testInputDependentValidatorRejectsWrongLocaleStatusCategoryAndMissing(): void
    {
        $dto=$this->dto(['EMOTION'=>20,'PASSION'=>70],'CAUTION');$base=T::fallback($dto);
        foreach([
            $this->dto(['EMOTION'=>20,'PASSION'=>70],'GOOD'),
            $this->dto(['EMOTION'=>70,'PASSION'=>20],'CAUTION'),
            $this->dto(['EMOTION'=>20,'PASSION'=>70],'CAUTION','ja-JP'),
            $this->dto(array_fill_keys(F::ORDER,50),'CAUTION'),
        ] as $other){self::assertNotSame([],T::validate(T::fallback($other),$dto));}
        foreach(['strengths','challenges'] as $key){$out=$base;$out[$key]=[['text'=>'x','evidenceRefs'=>['ft_test']]];self::assertSame(['V2_EMPTY_ARRAYS'],T::validate($out,$dto));}
        foreach(['<b>safe</b>','They secretly love you.','100% success','onclick=x'] as $text){$out=$base;$out['advice']=$text;self::assertNotSame([],T::validate($out,$dto));}
        $out=$base;$out['advice']='Compatibility score: 60';self::assertSame([],T::validate($out,$dto));$out['advice']='Compatibility score: 61';self::assertNotSame([],T::validate($out,$dto));
    }
    public function testWorstCaseAllLocalesAndSafeTemplateFailure(): void
    {
        foreach(['ko-KR','ja-JP','en-US'] as $locale){
            foreach(['CAUTION','BALANCED','GOOD','VERY_GOOD','EXCELLENT'] as $status){foreach(F::ORDER as $high){foreach(F::ORDER as $low){if($high===$low){continue;}
                foreach([false,true] as $missing){$scores=$missing?[]:array_fill_keys(F::ORDER,50);$scores[$high]=80;$scores[$low]=20;$dto=$this->dto($scores,$status,$locale);$out=T::fallback($dto);
                    self::assertLessThan(1200,preg_match_all('/./us',$out['summary']));self::assertLessThan(1600,preg_match_all('/./us',$out['advice']));
                    self::assertSame(trim($out['summary']),$out['summary']);self::assertStringNotContainsString("\n\n",$out['summary']);self::assertSame([],T::validateFallback($out,$dto));
                }
            }}}
            $dto=$this->dto([],'UNEXPECTED',$locale);$expected=['summary'=>T::copy($locale)[0]."\n".T::copy($locale)[2],'strengths'=>[],'challenges'=>[],'advice'=>T::copy($locale)[1]];
            self::assertSame($expected,T::fallback($dto));self::assertSame([],T::validateFallback($expected,$dto));
        }
    }
    public function testOtherResultTypesPreserveLegacyFallbackContent(): void
    {
        foreach(['Daily'=>['dailyScore'=>52.5],'Weekly'=>['weeklyScore'=>53],'Monthly'=>['monthlyScore'=>54],'Yearly'=>['yearlyScore'=>55]] as $type=>$fields){
            foreach(['ko-KR','ja-JP','en-US'] as $locale){$dto=['resultType'=>$type,'locale'=>$locale,'evidence'=>[]]+$fields;
                $out=T::fallback($dto);$key=array_key_first($fields);
                self::assertSame(['summary'=>T::copy($locale)[0]."\n".T::label($key,$locale).': '.$fields[$key],'strengths'=>[],'challenges'=>[],'advice'=>T::copy($locale)[1]],$out);
                $dto['categories']=$this->dto(['EMOTION'=>10,'PASSION'=>99])['categories'];self::assertSame($out,T::fallback($dto));self::assertSame([],T::validateFallback($out,$dto));
            }
        }
    }
    public function testCompatibilityAiOffFailureAndContentReplay(): void
    {
        $ctx=['result'=>interpretationResults()['Compatibility'],'locale'=>'en-US','evidence'=>[]];$dto=ProviderInput::project($ctx);$expected=T::fallback($dto);
        foreach([null,new InterpretationMock(static fn()=>throw new ProviderFailure('TIMEOUT')),new InterpretationMock(static fn()=>'{}')] as $provider){
            $out=(new InterpretationService($provider))->interpret($ctx,'one');self::assertSame('FALLBACK',$out['meta']['interpretationMode']);self::assertSame('AI_PROMPT_V2',$out['meta']['aiPromptVersion']);unset($out['meta']);self::assertSame($expected,$out);
        }
        $out=(new InterpretationService())->interpret($ctx,'two');unset($out['meta']);self::assertSame($expected,$out);
    }
    public function testRemainingLocaleStatusAndLabelLiterals(): void
    {
        $statuses=[
            'ko-KR'=>['차이와 기대를 차분히 확인해 볼 구간입니다.','균형을 살피며 서로에게 편안한 방식을 찾아볼 구간입니다.','관계의 조화를 활용해 볼 구간입니다.','조화로운 경향을 일상의 협력으로 이어가 볼 구간입니다.','높게 나타난 조화의 경향을 참고하되 실제 경험도 함께 살펴보세요.'],
            'ja-JP'=>['違いや期待を落ち着いて確かめたい範囲です。','バランスを見ながら、互いに無理のない関わり方を探す範囲です。','関係の調和を生かすことを考えられる範囲です。','調和の傾向を日々の協力につなげることを考えられる範囲です。','高く表れた調和の傾向を参考にしつつ、実際の経験も大切にしてください。'],
        ];
        foreach($statuses as $locale=>$sentences){foreach(['CAUTION','BALANCED','GOOD','VERY_GOOD','EXCELLENT'] as $i=>$status){self::assertSame($sentences[$i],explode("\n",T::fallback($this->dto([],$status,$locale))['summary'])[1]);}}
        $labels=[
            'ko-KR'=>['관심과 접근 방식','감정 교류','활동 에너지','일상의 일관성','선호의 조율','서로 돕는 방식','장기적인 방향'],
            'ja-JP'=>['関心と近づき方','感情の交流','活動のエネルギー','日常の一貫性','好みのすり合わせ','支え合い方','長期的な方向性'],
            'en-US'=>['interest and approach','emotional exchange','activity and energy','everyday consistency','coordination of preferences','mutual support','long-term direction'],
        ];
        foreach($labels as $locale=>$names){self::assertSame($names,array_values(F::templates($locale)['labels']));}
        $limits=['ko-KR'=>'계산 신뢰 지표는 실제 관계의 결과를 보장하지 않습니다.','ja-JP'=>'計算上の信頼指標は、実際の関係の結果を保証するものではありません。','en-US'=>'Calculation confidence indicators do not guarantee real-world relationship outcomes.'];
        foreach($limits as $locale=>$expected){self::assertStringEndsWith($expected,T::fallback($this->dto(array_fill_keys(F::ORDER,50),'GOOD',$locale))['summary']);}
    }
}
