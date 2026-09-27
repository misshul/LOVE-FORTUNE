<?php
// Validation CLI only; no WordPress load or production implementation.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
$root = $argv[1];
$rows = json_decode(file_get_contents($root . '/longitude-goldens.json'), true, 512, JSON_THROW_ON_ERROR);
$boundaries = json_decode(file_get_contents($root . '/goldens.json'), true, 512, JSON_THROW_ON_ERROR)['longitudeBoundaries'];
function parseLongitude(string $s): int {
    if (!preg_match('/^-?\d+(?:\.\d{1,6})?$/D', $s)) { throw new InvalidArgumentException('INVALID_LONGITUDE'); }
    $p = explode('.', ltrim($s, '-'));
    if (strlen($p[0]) > 3) { throw new InvalidArgumentException('INVALID_LONGITUDE'); }
    $v = ((int)$p[0] * 1000000 + (int)str_pad($p[1] ?? '', 6, '0')) * (str_starts_with($s, '-') ? -1 : 1);
    if (abs($v) > 180000000) { throw new InvalidArgumentException('INVALID_LONGITUDE'); }
    return $v;
}
foreach (array_merge($rows, $boundaries) as $r) {
    $v = parseLongitude($r['longitudeDecimal']);
    if (isset($r['longitudeMicrodegrees']) && $v !== $r['longitudeMicrodegrees']) { throw new RuntimeException('PARSE_MISMATCH'); }
    $n = 4 * ($v - 127500000);
    $minutes = ($n < 0 ? -1 : 1) * intdiv(abs($n) + 500000, 1000000);
    if ($minutes !== $r['correctionMinutes']) { throw new RuntimeException('HALF_UP_MISMATCH'); }
}
foreach (['180.000001', '-180.000001', '127.1234567', 'NaN', '1e2', ''] as $bad) {
    try { parseLongitude($bad); } catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('INVALID_ACCEPTED');
}
echo json_encode(['result'=>'PASS','productionCases'=>count($rows),'boundaryCases'=>count($boundaries),'negativeCases'=>6,'differences'=>0]), PHP_EOL;
