<?php
declare(strict_types=1);
namespace LoveFortune\Core\Frontend;
use LoveFortune\Core\Engine\Saju\{NatalReference,LocationReferenceRepository};
final class FrontendShortcode
{
    public function register(): void { add_shortcode('love_fortune', [$this, 'render']); }
    public function render(): string
    {
        try {
            $data = NatalReference::load(dirname(__DIR__,2).'/config/references/locations-v1.json', LocationReferenceRepository::VERSION, LocationReferenceRepository::FILE_SHA256, 'LOCATION_VERSION_MISMATCH');
        } catch (\Throwable) { return '<p role="alert">지역 정보를 불러올 수 없습니다. 잠시 후 다시 시도해주세요.</p>'; }
        $base=plugins_url('',dirname(__DIR__,2).'/love-fortune-core.php');
        wp_enqueue_style('love-fortune-frontend',$base.'/assets/css/frontend.css',[],(string)filemtime(dirname(__DIR__,2).'/assets/css/frontend.css'));
        wp_enqueue_script('love-fortune-frontend',$base.'/assets/js/frontend.js',[],(string)filemtime(dirname(__DIR__,2).'/assets/js/frontend.js'),true);
        // Shortcodes render after wp_head in common themes. Print this handle once at render time.
        ob_start(); wp_print_styles(['love-fortune-frontend']); $style=(string)ob_get_clean();
        $id=wp_unique_id('lf-'); $e=static fn(string $s):string=>esc_attr($s);
        ob_start(); ?>
<section class="love-fortune-app" lang="ko" data-api="<?= $e(rest_url('love-fortune/v1/')) ?>">
<header><p class="lf-eyebrow">LOVE FORTUNE · 두 사람의 이야기</p><h2>우리 사이, 어떤 조화일까요?</h2><p>두 사람의 생년월일과 출생 정보를 입력하면 궁합 결과를 확인할 수 있습니다.</p></header>
<noscript>궁합 계산에는 JavaScript가 필요합니다. 입력 정보는 전송되지 않았습니다.</noscript>
<form autocomplete="off" class="lf-form" aria-describedby="<?= $e($id) ?>privacy" onsubmit="return false">
<div class="lf-people">
<?php foreach(['personA'=>'나','personB'=>'상대방'] as $person=>$label): $pid=$id.$person; ?>
<fieldset data-person="<?= $e($person) ?>"><legend><?= esc_html($label) ?></legend>
<label for="<?= $e($pid) ?>date">생년월일</label><input id="<?= $e($pid) ?>date" data-field="birthDate" type="date" min="1900-01-01" max="2099-12-31" required autocomplete="off">
<label class="lf-check"><input data-field="unknown" type="checkbox" checked> 출생 시간을 모름</label>
<label for="<?= $e($pid) ?>time">출생 시간</label><input id="<?= $e($pid) ?>time" data-field="birthTime" type="time" step="60" disabled autocomplete="off">
<label for="<?= $e($pid) ?>location">출생 지역</label><select id="<?= $e($pid) ?>location" data-field="birthLocationId" required><option value="">지역을 선택해주세요</option>
<?php foreach($data['records'] as $r): ?><option value="<?= $e($r['locationId']) ?>"><?= esc_html($r['displayName']) ?></option><?php endforeach; ?>
</select></fieldset><?php endforeach; ?>
</div>
<div class="lf-options"><label>두 사람의 관계<select data-field="relationshipType"><option value="UNKNOWN">선택하지 않음</option><option value="COUPLE">연인</option><option value="MARRIED">부부</option><option value="DATING">알아가는 사이</option><option value="CRUSH">관심 있는 사이</option><option value="FRIEND">친구</option></select></label>
<label>대상 시간대<select data-field="targetTimezone"><?php $zones=\DateTimeZone::listIdentifiers(); foreach($zones as $zone): ?><option value="<?= $e($zone) ?>" <?= $zone==='Asia/Tokyo'?'selected':'' ?>><?= esc_html($zone) ?></option><?php endforeach; ?></select></label></div>
<p id="<?= $e($id) ?>privacy" class="lf-note">입력은 계산 요청에만 사용하며 이 화면은 출생 정보를 브라우저 저장소에 저장하지 않습니다. 결과는 관계를 이해하는 참고로 활용해주세요.</p>
<button type="submit" class="lf-primary" disabled>궁합 보기</button><button type="button" data-action="reset">다시 계산하기</button>
</form>
<p data-view="loading" role="status" aria-live="polite"></p><p data-view="error" id="<?= $e($id) ?>error" role="alert" tabindex="-1" hidden></p>
<section data-view="result" tabindex="-1" hidden><h3>궁합 결과</h3><div class="lf-overall"><strong data-view="score"></strong><span data-view="status"></span></div><p data-view="warnings"></p><div class="lf-categories" data-view="categories"></div><details><summary>상세 정보</summary><p data-view="details"></p><p>지표는 계산에 반영된 정보의 범위를 나타내며 성공 확률이나 과학적 정확도가 아닙니다.</p></details><button type="button" data-action="interpret" class="lf-primary" hidden>해석 보기</button></section>
<section data-view="interpretation" tabindex="-1" hidden></section>
</section>
<?php return $style.(string)ob_get_clean();
    }
}
