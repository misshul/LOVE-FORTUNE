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
}
final class FrontendTest extends \PHPUnit\Framework\TestCase
{
 public function testShortcodeAssetsAndFrozenOptions():void{
  $GLOBALS['lf_styles']=[];$GLOBALS['lf_scripts']=[];
  $ui=new \LoveFortune\Core\Frontend\FrontendShortcode();$ui->register();
  self::assertIsCallable($GLOBALS['lf_shortcodes']['love_fortune']);self::assertSame([],$GLOBALS['lf_styles']);
  $html=$ui->render();self::assertCount(1,$GLOBALS['lf_styles']);self::assertCount(1,$GLOBALS['lf_scripts']);
  self::assertSame(258,substr_count($html,'value="LOC'));self::assertStringNotContainsString('LOC000005',$html);self::assertStringNotContainsString('LOC000006',$html);
  self::assertStringContainsString('https://example.test/sub/wp-json/love-fortune/v1/',$html);
  self::assertSame(2,substr_count($html,'data-person='));self::assertSame(2,substr_count($html,'min="1900-01-01" max="2099-12-31"'));
  self::assertStringNotContainsString('longitude',$html);self::assertStringNotContainsString('signedContext',$html);
 }
 public function testNoSuccessfulFormFieldsOrPersistenceAndUniqueIds():void{
  $ui=new \LoveFortune\Core\Frontend\FrontendShortcode();$a=$ui->render();$b=$ui->render();
  self::assertDoesNotMatchRegularExpression('/<(?:input|select)[^>]*\sname=/', $a);
  self::assertStringContainsString('autocomplete="off"',$a);self::assertStringContainsString('type="submit" class="lf-primary" disabled',$a);
  preg_match('/id="([^"]+)date"/',$a,$first);self::assertStringNotContainsString($first[0],$b);
 }
}
