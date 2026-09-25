<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

use LoveFortune\Core\Engine\Saju\HourPillarCalculator;
use LoveFortune\Core\Engine\Saju\DayPillarCalculator;
use LoveFortune\Core\Engine\Saju\CalculationDateResolver;
use LoveFortune\Core\PluginFactory;
use PHPUnit\Framework\TestCase;

final class HourPillarTest extends TestCase
{
    private function day(DateTimeImmutable $time): array
    {
        $date = (new CalculationDateResolver())->fromAdjusted($time);
        return ['calculationDate' => $date, 'pillar' => (new DayPillarCalculator())->calculate($date)];
    }

    private function time(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }

    private function calculate(DateTimeImmutable $time): array
    {
        return (new HourPillarCalculator())->calculate($time, $this->day($time), HourPillarCalculator::conventions());
    }

    private function fixture(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../fixtures/hour-pillar-golden.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testApprovedMidnightGoldenAndMicroseconds(): void
    {
        foreach ($this->fixture()['midnight'] as [$time, $date, $day, $hour]) {
            $t = $this->time($time);
            self::assertSame($date, $this->day($t)['calculationDate']);
            self::assertSame($day, $this->day($t)['pillar']['ganzhi']);
            $result = $this->calculate($t);
            self::assertSame($hour, $result['ganzhi']);
            self::assertSame($date, $result['calculationDateUsed']);
            self::assertTrue($result['known']);
        }
        foreach ([['22:59:59.999999', '乙亥'], ['23:00:00.000000', '甲子'],
            ['23:29:59.999999', '甲子'], ['23:30:00.000000', '丙子'],
            ['23:30:00.000001', '丙子']] as [$time, $expected]) {
            self::assertSame($expected, $this->calculate($this->time('2019-01-27 ' . $time))['ganzhi']);
        }
        self::assertSame('丙子', $this->calculate($this->time('2019-01-28 00:59:59.999999'))['ganzhi']);
        self::assertSame('丁丑', $this->calculate($this->time('2019-01-28 01:00:00.000000'))['ganzhi']);
    }

    public function testAllTwelveMicrosecondBoundaries(): void
    {
        $starts = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
        foreach ($starts as $index => $h) {
            $b = $this->time(sprintf('2019-01-27 %02d:00:00', $h));
            foreach ([-1, 0, 1] as $us) {
                $t = $b->modify(sprintf('%+d microseconds', $us));
                $result = $this->calculate($t);
                self::assertSame($us < 0 ? ($index + 11) % 12 : $index, $result['hourIndex']);
                self::assertSame($result['hourIndex'], $result['branchIndex']);
            }
        }
        self::assertSame($this->calculate($this->time('2019-01-27 23:59:59.999999')),
            $this->calculate($this->time('2019-01-28 00:00:00.000000')));
    }

    public function testAll120FiveRatTableCombinations(): void
    {
        $rows = $this->fixture()['stemRows'];
        $seen = [];
        for ($d = 0; $d < 10; ++$d) {
            $date = $this->time('2019-01-27')->modify("+{$d} days");
            for ($h = 0; $h < 12; ++$h) {
                // 00:00,02:00,...22:00 all use this calculation date.
                $t = $date->setTime($h * 2, 0);
                $r = $this->calculate($t);
                self::assertSame($d, $r['dayStemIndexUsed']);
                self::assertSame($h, $r['hourIndex']);
                self::assertSame($rows[$d % 5][$h], $r['stemIndex']);
                $seen[] = [$d, $h];
            }
        }
        self::assertCount(120, $seen);
    }

    public function testAlreadyAdjustedInputNoSecondCorrectionOrTimezoneConversion(): void
    {
        // Supplied upstream fixture: +09:00,126.5E makes23:02 ->22:58 (-4min).
        $raw = new DateTimeImmutable('2019-01-27 23:02:00', new DateTimeZone('+09:00'));
        $adjusted = $this->time('2019-01-27 22:58:00');
        self::assertSame((new CalculationDateResolver())->fromResolvedLocal($raw, 126.5), $this->day($adjusted)['calculationDate']);
        self::assertSame('亥', $this->calculate($adjusted)['branch']);
        // If a second -4min correction occurs23:01 would incorrectly become亥.
        self::assertSame('子', $this->calculate($this->time('2019-01-27 23:01:00'))['branch']);
        $expected = $this->calculate($adjusted);
        foreach (['UTC', 'Asia/Seoul', 'America/New_York', '+08:27:52'] as $zone) {
            $t = new DateTimeImmutable('2019-01-27 22:58:00', new DateTimeZone($zone));
            self::assertSame($expected, $this->calculate($t));
            self::assertSame('22:58:00.000000', $t->format('H:i:s.u'));
        }
        // Historical offset-shaped synthetic fixture:32400-30472=1928s.
        // Upstream22:27:51.999999+00:32:08 is still22:59:59.999999, not23:00.
        $local = new DateTimeImmutable('1900-01-01 22:27:51.999999', new DateTimeZone('+08:27:52'));
        $t = $this->time('1900-01-01 22:59:59.999999');
        self::assertSame((new CalculationDateResolver())->fromResolvedLocal($local, 127.5), $this->day($t)['calculationDate']);
        self::assertSame('亥', $this->calculate($t)['branch']);
        self::assertSame('子', $this->calculate($t->modify('+1 microsecond'))['branch']);
    }

    public function testUnknownAndResolvedFoldCandidatesWithoutResolver(): void
    {
        $calc = new HourPillarCalculator();
        $r = $calc->unknown();
        self::assertFalse($r['known']);
        foreach (['hourIndex', 'stemIndex', 'branchIndex', 'stem', 'branch', 'ganzhi', 'dayStemIndexUsed', 'calculationDateUsed'] as $field) {
            self::assertNull($r[$field]);
        }
        // Synthetic resolved fold inputs: same local01:30, offsets-04/-05.
        // At127.5E upstream adjustments yield14:30 and15:30; no resolver is built here.
        $a = $this->time('2020-11-01 14:30:00');
        $b = $this->time('2020-11-01 15:30:00');
        self::assertSame('未', $this->calculate($a)['branch']);
        self::assertSame('申', $this->calculate($b)['branch']);
        self::assertSame($this->calculate($a), $this->calculate($a));
        // An unresolved gap has no datetime: the typed resolved-only API rejects it.
        expectException(fn () => $calc->calculate(null, [], HourPillarCalculator::conventions()), TypeError::class);
    }

    public function testInvalidDayAndProvenanceFailClosed(): void
    {
        $calc = new HourPillarCalculator();
        $t = $this->time('2019-01-27 23:30:00');
        $valid = $this->day($t);
        $p = HourPillarCalculator::conventions();
        foreach (array_keys($p) as $key) {
            $wrong = $p;
            unset($wrong[$key]);
            expectException(fn () => $calc->calculate($t, $valid, $wrong), InvalidArgumentException::class);
            $wrong[$key] = 'WRONG';
            expectException(fn () => $calc->calculate($t, $valid, $wrong), InvalidArgumentException::class);
        }
        foreach ([[], ['calculationDate' => '2019-01-28'], $this->day($this->time('2019-01-27 23:29:59'))] as $bad) {
            expectException(fn () => $calc->calculate($t, $bad, $p), InvalidArgumentException::class);
        }
        foreach (['stemIndex', 'branchIndex', 'cycleIndex', 'stem', 'branch', 'ganzhi'] as $key) {
            $bad = $valid;
            $bad['pillar'][$key] = 'INVALID';
            expectException(fn () => $calc->calculate($t, $bad, $p), InvalidArgumentException::class);
        }
        expectException(fn () => $calc->calculate($this->time('2200-01-01'), ['calculationDate' => '2200-01-01', 'pillar' => []], $p), OutOfRangeException::class);
    }

    public function testRegisteredStatelessProductionComponent(): void
    {
        $before = $GLOBALS['writes'];
        $plugin = PluginFactory::create(dirname(__DIR__, 2));
        $calc = $plugin->engines->get('saju.hour_pillar');
        self::assertSame(HourPillarCalculator::VERSION, $plugin->versions->get('saju.hour_pillar'));
        $t = $this->time('2019-01-27 23:30');
        $result = $calc->calculate($t, $this->day($t), HourPillarCalculator::conventions());
        self::assertSame('丙子', $result['ganzhi']);
        self::assertSame('ADJUSTED_CIVIL_TIME', $result['timeBasis']);
        self::assertSame('SAJU_DAY_PILLAR_EPOCH_V1', $result['dayPillarVersion']);
        self::assertSame('SAJU_HOUR_PILLAR_V1', $result['hourPillarVersion']);
        self::assertSame($before, $GLOBALS['writes']);
    }
}
