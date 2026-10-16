<?php
declare(strict_types=1);
if (!function_exists('add_shortcode')) {
 function add_shortcode($name,$callback){$GLOBALS['lf_shortcodes'][$name]=$callback;}
 function plugins_url($path='',$file=''){return 'https://example.test/sub/wp-content/plugins/love-fortune-core'.$path;}
 function wp_enqueue_style(...$args){$GLOBALS['lf_styles'][]=$args;}
 function wp_enqueue_script(...$args){$GLOBALS['lf_scripts'][]=$args;}
 function wp_print_styles($handles){}
 function wp_unique_id($prefix=''){static $i=0;return $prefix.++$i;}
 function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
 function esc_html($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
 function rest_url($path=''){return 'https://example.test/sub/wp-json/'.$path;}
 function is_admin(){return $GLOBALS['lf_page_context']['admin']??false;}
 function is_page(){return $GLOBALS['lf_page_context']['page']??false;}
 function is_feed(){return $GLOBALS['lf_page_context']['feed']??false;}
 function is_embed(){return $GLOBALS['lf_page_context']['embed']??false;}
 function post_password_required(){return $GLOBALS['lf_page_context']['password']??false;}
 function get_queried_object(){return (object)['post_content'=>$GLOBALS['lf_page_context']['content']??''];}
}
final class FrontendTest extends \PHPUnit\Framework\TestCase
{
 protected function tearDown():void{unset($GLOBALS['lf_page_context']);}
 public function testDedicatedTemplateIsLimitedToStandalonePublicPages():void{
  $ui=new \LoveFortune\Core\Frontend\FrontendShortcode();$ui->register();
  foreach(['template_include','body_class','pre_get_document_title'] as $hook)self::assertIsCallable(end($GLOBALS['public_filters'][$hook]));
  foreach(['[love_fortune]',"<!-- wp:shortcode -->\n[love_fortune]\n<!-- /wp:shortcode -->"] as $content){
   $GLOBALS['lf_page_context']=['page'=>true,'content'=>$content];
   self::assertTrue($ui->isDedicatedPage());self::assertFileExists($ui->template('theme.php'));
   self::assertSame(['existing','love-fortune-page'],$ui->bodyClasses(['existing']));self::assertSame('LOVE FORTUNE',$ui->documentTitle('Test title'));
  }
  foreach(['','Other content','<p>Keep me</p>[love_fortune]','[love_fortune][other]','[love_fortune mode="x"]'] as $content){
   $GLOBALS['lf_page_context']=['page'=>true,'content'=>$content];
   self::assertSame('theme.php',$ui->template('theme.php'));self::assertSame(['existing'],$ui->bodyClasses(['existing']));self::assertSame('Original',$ui->documentTitle('Original'));
  }
  foreach(['admin','feed','embed','password'] as $excluded){
   $GLOBALS['lf_page_context']=['page'=>true,'content'=>'[love_fortune]',$excluded=>true];self::assertFalse($ui->isDedicatedPage());
  }
  $GLOBALS['lf_page_context']=['page'=>false,'content'=>'[love_fortune]'];self::assertFalse($ui->isDedicatedPage());
 }
 public function testPolishMarkupAndAccessibleCopy():void{
  $html=(new \LoveFortune\Core\Frontend\FrontendShortcode())->render();
  foreach(['1900~2099년 입력 가능','출생 시간을 몰라요','결과를 계산할 기준 시간대','브라우저에 저장하지 않아요.','data-action="reset" hidden>입력 수정하기','data-view="score-unit"','aria-describedby='] as $copy)self::assertStringContainsString($copy,$html);
  self::assertLessThan(strpos($html,'<details data-view='),strpos($html,'data-view="interpretation"'));
  self::assertLessThan(strpos($html,'data-view="interpretation"'),strpos($html,'data-action="interpret"'));
  self::assertStringNotContainsString('<details open',$html);
 }
 public function testShortcodeAssetsAndFrozenOptions():void{
  $GLOBALS['lf_styles']=[];$GLOBALS['lf_scripts']=[];
  $ui=new \LoveFortune\Core\Frontend\FrontendShortcode();$ui->register();
  self::assertIsCallable($GLOBALS['lf_shortcodes']['love_fortune']);self::assertSame([],$GLOBALS['lf_styles']);
  $html=$ui->render();self::assertCount(1,$GLOBALS['lf_styles']);self::assertCount(1,$GLOBALS['lf_scripts']);
  self::assertSame(258,substr_count($html,'value="LOC'));self::assertStringNotContainsString('LOC000005',$html);self::assertStringNotContainsString('LOC000006',$html);
  self::assertStringContainsString('https://example.test/sub/wp-json/love-fortune/v1/',$html);
  self::assertSame(2,substr_count($html,'data-person='));self::assertSame(3,substr_count($html,'min="1900-01-01" max="2099-12-31"'));
  self::assertStringNotContainsString('longitude',$html);self::assertStringNotContainsString('signedContext',$html);
 }
 public function testNoSuccessfulFormFieldsOrPersistenceAndUniqueIds():void{
  $ui=new \LoveFortune\Core\Frontend\FrontendShortcode();$a=$ui->render();$b=$ui->render();
  self::assertDoesNotMatchRegularExpression('/<(?:input|select)[^>]*\sname=/', $a);
  self::assertStringContainsString('autocomplete="off"',$a);self::assertStringContainsString('type="submit" class="lf-primary" disabled',$a);
  preg_match('/id="([^"]+)date"/',$a,$first);self::assertStringNotContainsString($first[0],$b);
 }
 public function testPeriodInputsAreExplicitAndUseFrozenTimezones():void{
  $html=(new \LoveFortune\Core\Frontend\FrontendShortcode())->render();
  preg_match('/<select data-field="periodTimezone"[^>]*>(.*?)<\/select>/s',$html,$select);
  preg_match_all('/<option value="([^"]+)"/',$select[1],$options);
  $reference=json_decode(file_get_contents(__DIR__.'/../../config/references/timezones-v1.json'),true,512,JSON_THROW_ON_ERROR);
  self::assertSame(array_column($reference['zones'],'timezoneId'),$options[1]);
  self::assertCount(23,$options[1]);
  self::assertStringContainsString('value="Asia/Tokyo" selected',$select[1]);
  self::assertStringContainsString('data-period="weekly" aria-pressed="true"',$html);
  self::assertStringContainsString('max="2099-12-21"',$html);
  self::assertSame(2,substr_count($html,'type="number" min="1900" max="2099" step="1" required disabled'));
  preg_match('/<select data-field="month"[^>]*>(.*?)<\/select>/s',$html,$months);
  self::assertSame(13,substr_count($months[1],'<option'));
 }
 public function testFinalPolishGroupsAndCommonCopy():void{
  $html=(new \LoveFortune\Core\Frontend\FrontendShortcode())->render();
  foreach(['두 사람의 출생 정보로 기본 궁합과 선택한 날짜·기간의 관계 흐름을 살펴보세요.','두 사람의 정보','살펴볼 날짜·기간','태어난 지역','결과를 계산할 기준 시간대','입력 정보와 결과는 브라우저에 저장하지 않아요.','궁합 해석 보기','<summary>계산 정보</summary>','lf-period-navigation'] as $copy)self::assertStringContainsString($copy,$html);
  self::assertSame(1,substr_count($html,'관계를 돌아보는 참고 정보이며, 실제 사건이나 미래를 보장하지 않아요.'));
  self::assertSame(1,substr_count($html,'화면을 바꿔도 입력한 정보는 유지돼요. 결과는 다시 계산해주세요.'));
  self::assertStringContainsString('data-view="target-inputs"',$html);
  self::assertStringContainsString('data-view="shared-inputs"',$html);
  self::assertStringContainsString('<div data-view="details"></div>',$html);
 }
 public function testDailyUsesOnlyFrozenCanonicalTimezonesAndExplicitDate():void{
  $html=(new \LoveFortune\Core\Frontend\FrontendShortcode())->render();
  preg_match('/<select data-field="dailyTimezone"[^>]*>(.*?)<\/select>/s',$html,$select);
  preg_match_all('/<option value="([^"]+)"/',$select[1],$options);
  $reference=json_decode(file_get_contents(__DIR__.'/../../config/references/timezones-v1.json'),true,512,JSON_THROW_ON_ERROR);
  self::assertSame(array_column($reference['zones'],'timezoneId'),$options[1]);self::assertCount(23,$options[1]);
  self::assertStringContainsString('value="Asia/Tokyo" selected',$select[1]);
  self::assertStringContainsString('data-mode="compatibility" aria-pressed="true"',$html);
  self::assertStringContainsString('data-mode="daily" aria-pressed="false"',$html);
  preg_match('/<input data-field="date"[^>]*>/',$html,$date);
  self::assertStringNotContainsString('value=',$date[0]);self::assertStringContainsString('required disabled',$date[0]);
  self::assertStringNotContainsString('role="tab"',$html);
 }
}
