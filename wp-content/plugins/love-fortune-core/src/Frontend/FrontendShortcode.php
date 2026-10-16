<?php
declare(strict_types=1);
namespace LoveFortune\Core\Frontend;
use LoveFortune\Core\Engine\Saju\{NatalReference,LocationReferenceRepository,TimezoneReferenceRepository};
final class FrontendShortcode
{
    public function register(): void
    {
        add_shortcode('love_fortune', [$this, 'render']);
        add_filter('template_include', [$this, 'template']);
        add_filter('body_class', [$this, 'bodyClasses']);
        add_filter('pre_get_document_title', [$this, 'documentTitle']);
    }
    public function isDedicatedPage(): bool
    {
        if (is_admin() || !is_page() || is_feed() || is_embed() || post_password_required()) {
            return false;
        }
        $page = get_queried_object();
        // Only an otherwise empty shortcode page opts into the service shell.
        // Mixed content, attributes and other shortcodes retain the theme template.
        $content = preg_replace('/<!--\s*\/?wp:shortcode\s*-->/', '', (string)($page->post_content ?? ''));
        return trim((string)$content) === '[love_fortune]';
    }
    public function template(string $template): string
    {
        return $this->isDedicatedPage() ? __DIR__.'/templates/service-page.php' : $template;
    }
    public function bodyClasses(array $classes): array
    {
        if ($this->isDedicatedPage()) { $classes[] = 'love-fortune-page'; }
        return $classes;
    }
    public function documentTitle(string $title): string
    {
        return $this->isDedicatedPage() ? 'LOVE FORTUNE' : $title;
    }
    public function render(): string
    {
        try {
            $data = NatalReference::load(dirname(__DIR__,2).'/config/references/locations-v1.json', LocationReferenceRepository::VERSION, LocationReferenceRepository::FILE_SHA256, 'LOCATION_VERSION_MISMATCH');
            $timezoneData = NatalReference::load(dirname(__DIR__,2).'/config/references/timezones-v1.json', TimezoneReferenceRepository::VERSION, TimezoneReferenceRepository::FILE_SHA256, 'TIMEZONE_VERSION_MISMATCH');
            $dailyZones = array_column($timezoneData['zones'], 'timezoneId');
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
<div class="lf-view-navigation" aria-label="계산 화면 선택"><button type="button" data-mode="compatibility" aria-pressed="true">궁합</button><button type="button" data-mode="daily" aria-pressed="false">날짜별 흐름</button><button type="button" data-mode="period" aria-pressed="false">기간별 흐름</button></div>
<div data-view="period-intro" hidden><h3>기간별 흐름</h3><p>선택한 한 주/달/해의 관계 흐름을 살펴보세요.</p><div class="lf-view-navigation" aria-label="기간 선택"><button type="button" data-period="weekly" aria-pressed="true">주간</button><button type="button" data-period="monthly" aria-pressed="false">월간</button><button type="button" data-period="yearly" aria-pressed="false">연간</button></div></div>
<div data-view="daily-intro" hidden><h3>날짜별 흐름</h3><p>선택한 날짜를 기준으로 두 사람의 관계 흐름을 살펴보세요.</p><p class="lf-note">관계를 돌아보는 참고 정보이며, 실제 사건이나 미래를 보장하지 않아요.</p></div>
<form autocomplete="off" class="lf-form" aria-describedby="<?= $e($id) ?>privacy" onsubmit="return false">
<div class="lf-people">
<?php foreach(['personA'=>'나','personB'=>'상대방'] as $person=>$label): $pid=$id.$person; ?>
<fieldset data-person="<?= $e($person) ?>"><legend><?= esc_html($label) ?></legend>
<label for="<?= $e($pid) ?>date">생년월일</label><input id="<?= $e($pid) ?>date" data-field="birthDate" type="date" min="1900-01-01" max="2099-12-31" required autocomplete="off" aria-describedby="<?= $e($pid) ?>date-hint">
<p id="<?= $e($pid) ?>date-hint" class="lf-hint">1900~2099년 입력 가능</p>
<div class="lf-time-group">
<label for="<?= $e($pid) ?>time">출생 시간</label><input id="<?= $e($pid) ?>time" data-field="birthTime" type="time" step="60" disabled autocomplete="off">
<label class="lf-check"><input data-field="unknown" type="checkbox" checked> 출생 시간을 몰라요</label></div>
<label for="<?= $e($pid) ?>location">출생 지역</label><select id="<?= $e($pid) ?>location" data-field="birthLocationId" required><option value="">지역을 선택해주세요</option>
<?php foreach($data['records'] as $r): ?><option value="<?= $e($r['locationId']) ?>"><?= esc_html($r['displayName']) ?></option><?php endforeach; ?>
</select></fieldset><?php endforeach; ?>
</div>
<div class="lf-options"><label>두 사람의 관계<select data-field="relationshipType"><option value="UNKNOWN">선택하지 않음</option><option value="COUPLE">연인</option><option value="MARRIED">부부</option><option value="DATING">알아가는 사이</option><option value="CRUSH">관심 있는 사이</option><option value="FRIEND">친구</option></select></label>
<label data-view="compatibility-timezone">결과 기준 시간대<select data-field="targetTimezone" aria-describedby="<?= $e($id) ?>timezone-hint"><?php $zones=\DateTimeZone::listIdentifiers(); foreach($zones as $zone): ?><option value="<?= $e($zone) ?>" <?= $zone==='Asia/Tokyo'?'selected':'' ?>><?= esc_html($zone) ?></option><?php endforeach; ?></select><span id="<?= $e($id) ?>timezone-hint" class="lf-hint">기본값은 일본 시간이에요. 원하는 시간대를 선택해주세요.</span></label></div>
<div class="lf-options" data-view="daily-inputs" hidden><label>기준 날짜<input data-field="date" type="date" min="1900-01-01" max="2099-12-31" required disabled autocomplete="off"></label><label>날짜 기준 시간대<select data-field="dailyTimezone" required disabled><?php foreach($dailyZones as $zone): ?><option value="<?= $e($zone) ?>" <?= $zone==='Asia/Tokyo'?'selected':'' ?>><?= esc_html($zone) ?></option><?php endforeach; ?></select><span class="lf-hint">기본값은 Asia/Tokyo예요. 날짜를 해석할 시간대를 직접 선택해주세요.</span></label></div>
<div data-view="period-inputs" hidden>
<div data-period-input="weekly"><label>주 시작 날짜<input data-field="weekStartDate" type="date" min="1900-01-01" max="2099-12-21" required disabled aria-describedby="<?= $e($id) ?>week-hint"><span class="lf-hint" id="<?= $e($id) ?>week-hint">월요일을 선택해주세요.</span></label></div>
<div data-period-input="monthly" class="lf-options" hidden><label>연도<input data-field="monthlyYear" type="number" min="1900" max="2099" step="1" required disabled></label><label>월<select data-field="month" required disabled><option value="">월 선택</option><?php for($month=1;$month<=12;$month++): ?><option value="<?= $month ?>"><?= $month ?>월</option><?php endfor; ?></select></label></div>
<div data-period-input="yearly" hidden><label>연도<input data-field="yearlyYear" type="number" min="1900" max="2099" step="1" required disabled></label></div>
<label>기간 기준 시간대<select data-field="periodTimezone" required disabled><?php foreach($dailyZones as $zone): ?><option value="<?= $e($zone) ?>" <?= $zone==='Asia/Tokyo'?'selected':'' ?>><?= esc_html($zone) ?></option><?php endforeach; ?></select></label>
</div>
<p id="<?= $e($id) ?>privacy" class="lf-note">입력한 출생 정보는 계산에 사용되며, 브라우저에 저장하지 않아요. 결과는 관계를 이해하는 참고로 봐주세요.</p>
<div class="lf-submit"><button type="submit" class="lf-primary" disabled>궁합 보기</button></div>
</form>
<p data-view="loading" role="status" aria-live="polite"></p><p data-view="error" id="<?= $e($id) ?>error" role="alert" tabindex="-1" hidden></p>
<section data-view="result" tabindex="-1" aria-label="궁합 결과" hidden><div class="lf-result-heading"><h3>궁합 결과</h3><button type="button" data-action="reset" hidden>입력 수정하기</button></div><p data-view="daily-reference" hidden></p><div class="lf-overall"><div><strong data-view="score"></strong><span data-view="score-unit" class="lf-score-unit"> / 100</span></div><span data-view="status"></span></div><p data-view="daily-delta" class="lf-delta" hidden></p><p data-view="warnings"></p><div class="lf-categories" data-view="categories"></div><button type="button" data-action="interpret" class="lf-primary" hidden>해석 보기</button>
<section data-view="interpretation" tabindex="-1" hidden></section>
<p data-view="interpretation-error" role="alert" tabindex="-1" hidden></p>
<details><summary>상세 정보</summary><p data-view="details"></p><p>지표는 계산에 반영된 정보의 범위를 나타내며 성공 확률이나 과학적 정확도가 아닙니다.</p></details></section>
</section>
<?php return $style.(string)ob_get_clean();
    }
}
