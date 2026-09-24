<?php
declare(strict_types=1);

// CLI-only deterministic cross-runtime output. Contains synthetic fixtures only.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/autoload.php';

use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Domain\Score\DailyAggregator;
use LoveFortune\Core\Domain\Score\ExactRanking;
use LoveFortune\Core\Domain\Score\LifetimeBlender;
use LoveFortune\Core\Engine\Zodiac\ZodiacCatalog;
use LoveFortune\Core\Engine\Zodiac\ZodiacDateResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacScorer;
use LoveFortune\Core\Support\Rational as R;

function zodiacPack(CategoryResult $row): array
{
    return ['score' => $row->score === null ? null : (string) $row->score, 'cov' => (string) $row->coverage,
        'q' => (string) $row->confidence, 'api' => $row->score?->halfUp4(), 'status' => $row->status()];
}

function zodiacCross(): array
{
    $fixture = json_decode(file_get_contents(__DIR__ . '/fixtures/zodiac-golden.json'), true, 512, JSON_THROW_ON_ERROR);
    $out = ['cases' => [], 'daily' => [], 'round' => [], 'compare' => [], 'ranks' => [], 'precision' => [], 'pairs' => [], 'features' => []];
    foreach ($fixture['cases'] as $case) {
        $saju = array_map(static fn ($r) => new CategoryResult($r['score'] === null ? null : R::of($r['score']), R::of($r['coverage']), R::of($r['confidence'])), $case['saju']);
        $zodiac = $case['zodiacAvailable'] ? (new ZodiacScorer())->score(...$case['signs']) : [];
        $combined = (new LifetimeBlender())->blend($saju, $zodiac);
        $overall = zodiacPack($combined['overall']);
        $overall['literalQ'] = (string) $combined['overallPreCoverageConfidence'];
        $out['cases'][] = ['categories' => array_values(array_map('zodiacPack', $combined['categories'])), 'overall' => $overall];
    }
    $daily = new DailyAggregator();
    foreach ($fixture['daily'] as $case) {
        $slots = array_map(static fn ($r) => ['source' => 'SAJU', 'signal' => R::of($r['signal']), 'confidence' => R::of($r['confidence'])], $case['slots']);
        $row = $daily->aggregate($slots);
        $delta = R::of(18)->multiply($row['signal']);
        $score = $daily->score(R::of($case['baseline']), $row['signal']);
        $out['daily'][] = ['signal' => (string) $row['signal'], 'confidence' => (string) $row['confidence'], 'delta' => (string) $delta, 'score' => (string) $score, 'api' => $score->halfUp4()];
    }
    foreach ($fixture['round'] as $case) { $out['round'][] = R::of($case['input'])->halfUp4(); }
    foreach ($fixture['compare'] as $case) { $out['compare'][] = R::of($case['input'][0])->compare(R::of($case['input'][1])); }
    foreach ($fixture['ranks'] as $case) {
        $rows = array_map(static fn ($r) => ['date' => $r['date'], 'score' => R::of($r['score']), 'confidence' => R::of($r['confidence'])], $case['input']);
        $rank = new ExactRanking();
        $out['ranks'][] = ['best' => array_column($rank->rank($rows), 'date'), 'caution' => array_column($rank->rank($rows, true), 'date')];
    }
    foreach ($fixture['precision'] as $case) {
        $row = $daily->aggregate(array_map(static fn ($v) => ['source' => 'SAJU', 'signal' => R::of($v), 'confidence' => R::of(1)], $case['slots']));
        $out['precision'][] = ['signal' => (string) $row['signal'], 'peakIndex' => $row['peakIndex']];
    }
    $resolver = new ZodiacDateResolver();
    $hash = hash_init('sha256');
    $date = new DateTimeImmutable('1900-01-01', new DateTimeZone('UTC'));
    $count = 0;
    while ($date->format('Y-m-d') <= '2099-12-31') {
        $text = $date->format('Y-m-d');
        hash_update($hash, ($count > 0 ? "\n" : '') . $text . ':' . $resolver->resolve($text)['sign']);
        ++$count;
        $date = $date->modify('+1 day');
    }
    $out['dates'] = ['count' => $count, 'sha256' => hash_final($hash)];
    foreach (ZodiacCatalog::configuration()['catalog']['referenceTables']['signs'] as $a) {
        foreach (ZodiacCatalog::configuration()['catalog']['referenceTables']['signs'] as $b) {
            $out['pairs'][] = (new ZodiacPairResolver())->resolve($a['sign'], $b['sign']);
            $out['features'][] = (new ZodiacScorer())->features($a['sign'], $b['sign']);
        }
    }
    return $out;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    echo json_encode(zodiacCross(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}
