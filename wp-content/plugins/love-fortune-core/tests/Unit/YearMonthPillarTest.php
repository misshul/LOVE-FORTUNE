<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use LoveFortune\Core\Engine\Saju\SolarTermReference;
use LoveFortune\Core\Engine\Saju\YearPillarCalculator;
use LoveFortune\Core\Engine\Saju\MonthPillarCalculator;
use LoveFortune\Core\PluginFactory;
use PHPUnit\Framework\TestCase;

final class YearMonthPillarTest extends TestCase
{
    private const ARTIFACT = __DIR__ . '/../../config/references/solar-terms-v1.json';

    private function fixture(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../fixtures/year-month-golden.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function events(): array
    {
        return json_decode(file_get_contents(self::ARTIFACT), true, 512, JSON_THROW_ON_ERROR)['events'];
    }

    public function testApprovedYearAndMonthGoldenBoundaries(): void
    {
        $ref = new SolarTermReference();
        $year = new YearPillarCalculator($ref);
        $month = new MonthPillarCalculator($ref);
        foreach ($this->fixture()['years'] as [$y, $b, $before, $after, $index]) {
            foreach ([-1, 0, 1] as $offset) {
                $result = $year->calculate((string) ((int) $b + $offset));
                self::assertSame($offset < 0 ? $before : $after, $result['ganzhi']);
                self::assertSame($offset < 0 ? $y - 1 : $y, $result['sajuYear']);
                self::assertSame($offset < 0 ? ($index + 59) % 60 : $index, $result['cycleIndex']);
                self::assertSame($result['cycleIndex'] % 10, $result['stemIndex']);
                self::assertSame($result['cycleIndex'] % 12, $result['branchIndex']);
                self::assertSame(SolarTermReference::VERSION, $result['solarTermReferenceVersion']);
                self::assertSame(SolarTermReference::BRIDGE, $result['timeScaleBridgeVersion']);
                self::assertSame(SolarTermReference::CHECKSUM, $result['artifactChecksum']);
            }
        }
        foreach ($this->fixture()['months2000'] as [$term, $b, $before, $after, $index]) {
            foreach ([-1, 0, 1] as $offset) {
                $result = $month->calculate((string) ((int) $b + $offset));
                self::assertSame($offset < 0 ? $before : $after, $result['ganzhi']);
                self::assertSame($offset < 0 ? ($index + 11) % 12 : $index, $result['monthIndex']);
                if ($offset >= 0) {
                    self::assertSame('SOLAR_2000_' . $term, $result['startJieEventId']);
                }
            }
        }
    }

    public function testAllYearsJieAndZhongqiBoundaries(): void
    {
        $ref = new SolarTermReference();
        $year = new YearPillarCalculator($ref);
        $month = new MonthPillarCalculator($ref);
        $counts = ['LICHUN' => 0, 'JIE' => 0, 'ZHONGQI' => 0];
        foreach ($this->events() as $event) {
            if ($event['year'] < 1900 || $event['year'] > 2099) { continue; }
            $b = (int) $event['serviceBoundaryUs'];
            $yb = $year->calculate((string) ($b - 1));
            $ya = $year->calculate((string) $b);
            $mb = $month->calculate((string) ($b - 1));
            $ma = $month->calculate((string) $b);
            self::assertSame($ya, $year->calculate((string) ($b + 1)));
            self::assertSame($ma, $month->calculate((string) ($b + 1)));
            self::assertSame($ya['stemIndex'], $ma['yearStemIndex']);
            self::assertSame($ya['sajuYear'], $ma['sajuYear']);
            if ($event['termId'] === 'LICHUN') {
                ++$counts['LICHUN'];
                self::assertSame($event['year'], $ya['sajuYear']);
                self::assertSame($ya['sajuYear'] - 1, $yb['sajuYear']);
                self::assertSame(($yb['cycleIndex'] + 1) % 60, $ya['cycleIndex']);
            } else {
                self::assertSame($yb, $ya);
            }
            if (in_array($event['termId'], SolarTermReference::JIE, true)) {
                ++$counts['JIE'];
                self::assertSame($event['eventId'], $ma['startJieEventId']);
                self::assertSame($mb['endJieEventId'], $ma['startJieEventId']);
                self::assertSame(($mb['monthIndex'] + 1) % 12, $ma['monthIndex']);
                self::assertSame(($mb['stemIndex'] + 1) % 10, $ma['stemIndex']);
                self::assertSame(($mb['branchIndex'] + 1) % 12, $ma['branchIndex']);
            } else {
                ++$counts['ZHONGQI'];
                self::assertSame($mb, $ma);
            }
        }
        self::assertSame(['LICHUN' => 200, 'JIE' => 2400, 'ZHONGQI' => 2400], $counts);
    }

    public function testFiveTigerAllTenYearStemsAndTwelveMonths(): void
    {
        $month = new MonthPillarCalculator();
        $tigers = $this->fixture()['tigerStemByYearStem'];
        $seen = [];
        foreach ($this->events() as $event) {
            // A full ten-year cycle, including Xiaohan of the following year.
            if ($event['year'] < 1984 || $event['year'] > 1994 || !in_array($event['termId'], SolarTermReference::JIE, true)) { continue; }
            $row = $month->calculate($event['serviceBoundaryUs']);
            if ($row['sajuYear'] < 1984 || $row['sajuYear'] > 1993) { continue; }
            $seen[$row['yearStemIndex'] . ':' . $row['monthIndex']] = true;
            self::assertSame(($tigers[$row['yearStemIndex']] + $row['monthIndex']) % 10, $row['stemIndex']);
            self::assertSame(($row['monthIndex'] + 2) % 12, $row['branchIndex']);
        }
        self::assertCount(120, $seen);
    }

    public function testCrossYearAndPublicBuffer(): void
    {
        $year = new YearPillarCalculator();
        $month = new MonthPillarCalculator();
        $events = array_column($this->events(), null, 'eventId');
        foreach ([
            ['SOLAR_1999_DAXUE', 1, 1999, '己卯', '丙子'],
            ['SOLAR_2000_XIAOHAN', -1, 1999, '己卯', '丙子'],
            ['SOLAR_2000_XIAOHAN', 0, 1999, '己卯', '丁丑'],
            ['SOLAR_2000_LICHUN', -1, 1999, '己卯', '丁丑'],
            ['SOLAR_2000_LICHUN', 0, 2000, '庚辰', '戊寅'],
            ['SOLAR_2000_DAXUE', 1, 2000, '庚辰', '戊子'],
            ['SOLAR_2001_XIAOHAN', 1, 2000, '庚辰', '己丑'],
        ] as [$id, $offset, $y, $yg, $mg]) {
            $t = (string) ((int) $events[$id]['serviceBoundaryUs'] + $offset);
            self::assertSame($y, $year->calculate($t)['sajuYear']);
            self::assertSame($yg, $year->calculate($t)['ganzhi']);
            self::assertSame($mg, $month->calculate($t)['ganzhi']);
        }
        foreach (['2000-01-01', '2001-01-01'] as $date) {
            $t = (new DateTimeImmutable($date, new DateTimeZone('UTC')))->getTimestamp() * 1000000;
            self::assertSame($year->calculate((string) ($t - 1)), $year->calculate((string) $t));
            self::assertSame($month->calculate((string) ($t - 1)), $month->calculate((string) $t));
        }
        self::assertSame(1899, $year->calculate('-2208988800000000')['sajuYear']);
        self::assertSame('SOLAR_2100_LICHUN', $year->calculate('4102444799999999')['nextLichunEventId']);
        self::assertSame('SOLAR_2100_XIAOHAN', $month->calculate('4102444799999999')['endJieEventId']);
        foreach (['SOLAR_1899_LICHUN', 'SOLAR_2100_LICHUN'] as $id) {
            $t = (int) $events[$id]['serviceBoundaryUs'];
            $bad = (string) ($id === 'SOLAR_1899_LICHUN' ? $t - 1 : $t);
            expectException(fn () => $year->calculate($bad), OutOfRangeException::class);
            expectException(fn () => $month->calculate($bad), OutOfRangeException::class);
        }
    }

    public function testInvalidCoordinatesAndMissingArtifact(): void
    {
        foreach (['', '1.5', '1e6', '+1', '-0', '01', ' 1', "1\n", '9223372036854775808', '-9223372036854775809'] as $value) {
            expectException(fn () => (new YearPillarCalculator())->calculate($value), InvalidArgumentException::class);
        }
        foreach ([__DIR__ . '/missing.json', 'https://example.invalid/reference', 'php://memory'] as $path) {
            expectException(fn () => (new SolarTermReference($path))->interval('0'), UnexpectedValueException::class);
        }
    }

    public function testIdentityAndCorruptedArtifactFailClosed(): void
    {
        self::assertSame(SolarTermReference::FILE_SHA256, hash_file('sha256', self::ARTIFACT));
        $original = json_decode(file_get_contents(self::ARTIFACT), true, 512, JSON_THROW_ON_ERROR);
        $mutations = [
            static function (&$x) { $x['metadata']['referenceVersion'] = 'OTHER'; },
            static function (&$x) { $x['metadata']['bridgeVersion'] = 'OTHER'; },
            static function (&$x) { $x['metadata']['artifactChecksum'] = str_repeat('0', 64); },
            static function (&$x) { array_splice($x['events'], 2, 1); }, // Missing Lichun.
            static function (&$x) { array_shift($x['events']); }, // Missing Jie.
            static function (&$x) { $x['events'][3] = $x['events'][2]; },
            static function (&$x) { [$x['events'][0], $x['events'][1]] = [$x['events'][1], $x['events'][0]]; },
            static function (&$x) { $x['events'][2]['serviceBoundaryUs'] = '0'; },
        ];
        $file = tempnam(sys_get_temp_dir(), 'lf_reference_');
        try {
            foreach ($mutations as $mutate) {
                $data = $original;
                $mutate($data);
                file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR));
                expectException(fn () => (new SolarTermReference($file))->interval('0'), UnexpectedValueException::class);
            }
            file_put_contents($file, '{');
            expectException(fn () => (new SolarTermReference($file))->interval('0'), UnexpectedValueException::class);
        } finally {
            unlink($file);
        }
    }

    public function testRegisteredCalculatorsAreIndependentAndStateless(): void
    {
        $plugin = PluginFactory::create(dirname(__DIR__, 2));
        $year = $plugin->engines->get('saju.year_pillar');
        $month = $plugin->engines->get('saju.month_pillar');
        $before = $GLOBALS['writes'];
        $a = '949667995815999';
        $b = '949667995816000';
        $first = [$year->calculate($a), $month->calculate($a)];
        self::assertSame('庚辰', $year->calculate($b)['ganzhi']);
        self::assertSame('戊寅', $month->calculate($b)['ganzhi']);
        self::assertSame($first, [$year->calculate($a), $month->calculate($a)]);
        self::assertSame($before, $GLOBALS['writes']);
        self::assertSame(YearPillarCalculator::FORMULA_VERSION, $plugin->versions->get('saju.year_pillar'));
        self::assertSame(MonthPillarCalculator::FORMULA_VERSION, $plugin->versions->get('saju.month_pillar'));
    }
}
