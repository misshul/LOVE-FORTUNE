<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use LoveFortune\Core\Engine\Saju\CalculationDateResolver;
use LoveFortune\Core\Engine\Saju\DailySampleResolver;
use LoveFortune\Core\Engine\Saju\DayPillarCalculator;
use LoveFortune\Core\Engine\Saju\GregorianDayNumber;
use LoveFortune\Core\Engine\Saju\SajuDayPillarService;
use PHPUnit\Framework\TestCase;

final class DayPillarTest extends TestCase
{
    public function testApprovedGoldenDatasetAndMetadata(): void
    {
        $fixture = json_decode(file_get_contents(dirname(__DIR__) . '/fixtures/day-pillar-golden.json'), true, 512, JSON_THROW_ON_ERROR);
        $days = new GregorianDayNumber();
        $calc = new DayPillarCalculator($days);
        self::assertCount(20, $fixture['dates']);
        foreach ($fixture['dates'] as $row) {
            $result = $calc->calculate($row['date']);
            self::assertSame($row['expectedJDN'], $days->calculate($row['date']));
            foreach (['CycleIndex', 'Stem', 'Branch', 'Ganzhi'] as $field) {
                self::assertSame($row['expected' . $field], $result[lcfirst($field)]);
            }
            self::assertSame($row['expectedCycleIndex'] % 10, $result['stemIndex']);
            self::assertSame($row['expectedCycleIndex'] % 12, $result['branchIndex']);
            self::assertArrayHasKey($row['evidenceId'], $fixture['evidence']);
        }
        self::assertSame([
            'identifier' => 'SAJU_DAY_PILLAR_EPOCH_V1',
            'calendarConvention' => 'PROLEPTIC_GREGORIAN_CALCULATION_DATE',
            'dayNumberConvention' => 'INTEGER_GREGORIAN_JDN',
            'cycleConvention' => 'ZERO_BASED_JIA_ZI_0_GUI_HAI_59',
            'formula' => 'floorMod(JDN + 49, 60)', 'formulaVersion' => 'NAOJ_JDN_GANZHI_V1',
            'goldenAnchorDate' => '2019-01-27', 'goldenAnchorJDN' => 2458511,
            'goldenAnchorIndex' => 0, 'goldenAnchorGanzhi' => '甲子',
            'evidenceVersion' => 'PHASE3_2026-09-15_APPROVED',
        ], $calc->metadata());
    }

    public function testCompleteRangeAgainstIndependentOrdinalAndCycles(): void
    {
        $days = new GregorianDayNumber();
        $calc = new DayPillarCalculator($days);
        $date = new DateTimeImmutable('1899-12-30', new DateTimeZone('UTC'));
        $indices = [];
        $previous = null;
        while ($date->format('Y-m-d') <= '2100-01-02') {
            $key = $date->format('Y-m-d');
            $yearBefore = (int) $date->format('Y') - 1;
            // Independent cumulative Gregorian ordinal, not the production JDN formula.
            $ordinal = 365 * $yearBefore + intdiv($yearBefore, 4)
                - intdiv($yearBefore, 100) + intdiv($yearBefore, 400)
                + (int) $date->format('z') + 1;
            $jdn = $days->calculate($key);
            self::assertSame($ordinal + 1721425, $jdn);
            $result = $calc->calculate($key);
            $index = $result['cycleIndex'];
            self::assertGreaterThanOrEqual(0, $index);
            self::assertLessThanOrEqual(59, $index);
            self::assertContains($result['stem'], DayPillarCalculator::STEMS);
            self::assertContains($result['branch'], DayPillarCalculator::BRANCHES);
            self::assertSame($result['stem'] . $result['branch'], $result['ganzhi']);
            self::assertSame($index % 10, $result['stemIndex']);
            self::assertSame($index % 12, $result['branchIndex']);
            self::assertSame(DayPillarCalculator::floorMod60($jdn - 11), $index);
            self::assertSame(DayPillarCalculator::floorMod60($jdn - 2458511), $index);
            if ($previous !== null) {
                self::assertSame($previous + 1, $jdn);
                self::assertSame((end($indices) + 1) % 60, $index);
            }
            if (count($indices) >= 60) {
                self::assertSame($indices[count($indices) - 60], $index);
            }
            $indices[] = $index;
            $previous = $jdn;
            $date = $date->modify('+1 day');
        }
        self::assertCount(73053, $indices);
    }

    public function testModuloIncludingNegativeInputs(): void
    {
        for ($value = -10000; $value <= 10000; ++$value) {
            $result = DayPillarCalculator::floorMod60($value);
            self::assertSame($value - (int) floor($value / 60) * 60, $result);
            self::assertGreaterThanOrEqual(0, $result);
            self::assertLessThanOrEqual(59, $result);
            self::assertSame(DayPillarCalculator::floorMod60($value + 49), DayPillarCalculator::floorMod60($value - 11));
        }
    }

    public function testInvalidDatesAndPublicInternalRangeSeparation(): void
    {
        $days = new GregorianDayNumber();
        foreach (['', '2019-1-27', '2019-01-27Z', '2019-01-27\n', '2019-01-27 12:00', '1900-02-29', '2100-02-29', '2000-02-30', '2000-13-01', '0000-01-01'] as $invalid) {
            expectException(fn () => $days->calculate($invalid), InvalidArgumentException::class);
        }
        foreach (['1899-12-29', '2100-01-03'] as $invalid) {
            expectException(fn () => $days->calculate($invalid), OutOfRangeException::class);
        }
        foreach (['1899-12-31', '2100-01-01'] as $internal) {
            self::assertIsInt($days->calculate($internal));
            expectException(fn () => $days->assertPublicDate($internal), OutOfRangeException::class);
            expectException(fn () => (new SajuDayPillarService())->daily($internal, new DateTimeZone('UTC')), OutOfRangeException::class);
        }
        foreach (['1900-01-01', '2099-12-31'] as $public) {
            $days->assertPublicDate($public);
            self::assertIsInt($days->calculate($public));
        }
        self::assertSame(1, $days->calculate('1900-03-01') - $days->calculate('1900-02-28'));
        self::assertSame(1, $days->calculate('2000-02-29') - $days->calculate('2000-02-28'));
        self::assertSame(1, $days->calculate('2000-03-01') - $days->calculate('2000-02-29'));
    }

    public function testBoundaryAndHistoricalOffsetIntegrationWithoutMutation(): void
    {
        $resolver = new CalculationDateResolver();
        $calc = new DayPillarCalculator();
        $service = new SajuDayPillarService();
        foreach (['23:29' => '甲子', '23:30' => '乙丑', '23:31' => '乙丑'] as $time => $expected) {
            $local = new DateTimeImmutable('2019-01-27 ' . $time, new DateTimeZone('Asia/Seoul'));
            self::assertSame($expected, $calc->calculate($resolver->fromAdjusted($local))['ganzhi']);
            self::assertSame($expected, $service->natalResolved($local, 127.5)['pillar']['ganzhi']);
            self::assertSame($expected, $service->dailyResolvedInstant($local)['pillar']['ganzhi']);
            self::assertSame('2019-01-27 ' . $time, $local->format('Y-m-d H:i'));
        }
        foreach (['22:59' => '1955-01-01', '23:00' => '1955-01-02'] as $time => $expected) {
            $local = new DateTimeImmutable('1955-01-01 ' . $time, new DateTimeZone('Asia/Seoul'));
            self::assertSame(30600, $local->getOffset());
            self::assertSame($expected, $service->natalResolved($local, 127.5)['calculationDate']);
            self::assertSame($expected, $service->dailyResolvedInstant($local)['calculationDate']);
        }
        // Pre-standard Seoul offset retains seconds: 23:27:52 + 00:32:08 = midnight.
        $old = new DateTimeImmutable('1900-01-01 23:27:52', new DateTimeZone('Asia/Seoul'));
        self::assertSame(30472, $old->getOffset());
        self::assertSame('1900-01-02', $service->dailyResolvedInstant($old)['calculationDate']);
        $tokyo = new DateTimeImmutable('2019-01-27 22:41', new DateTimeZone('Asia/Tokyo'));
        self::assertSame('乙丑', $service->natalResolved($tokyo, 139.75)['pillar']['ganzhi']);
        self::assertSame('甲子', $service->dailyResolvedInstant($tokyo)['pillar']['ganzhi']);
        // Natal does not replace the reference location with Seoul.
        // -74E and UTC-5 yield +34 minutes:22:55 becomes23:29 on the same date.
        $newYork = new DateTimeImmutable('2019-01-27 22:55', new DateTimeZone('America/New_York'));
        self::assertSame('2019-01-27', $service->natalResolved($newYork, -74.0)['calculationDate']);
        self::assertSame('2019-01-28', $service->dailyResolvedInstant($newYork)['calculationDate']);
        foreach ([NAN, INF, -181.0, 181.0] as $bad) {
            expectException(fn () => $service->natalResolved($tokyo, $bad), InvalidArgumentException::class);
        }
    }

    public function testDailyGapFoldAndNominalSlotPreservation(): void
    {
        $resolver = new DailySampleResolver();
        $cases = [
            ['2024-03-10', '02:30', 'America/New_York', '2024-03-10 07:00:00'],
            ['2024-11-03', '01:30', 'America/New_York', '2024-11-03 05:30:00'],
            ['2024-10-06', '02:15', 'Australia/Lord_Howe', '2024-10-05 15:30:00'],
            ['2011-12-30', '12:00', 'Pacific/Apia', '2011-12-30 10:00:00'],
        ];
        foreach ($cases as [$date, $time, $zone, $expected]) {
            $actual = $resolver->resolve($date, $time, new DateTimeZone($zone));
            self::assertSame($expected, $actual->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
        }
        $service = new SajuDayPillarService();
        $samples = $service->daily('2019-01-27', new DateTimeZone('UTC'));
        self::assertSame(['06:00', '12:00', '18:00', '23:00'], array_keys($samples));
        self::assertSame(['甲子', '甲子', '乙丑', '乙丑'], array_column(array_column($samples, 'pillar'), 'ganzhi'));
        self::assertCount(4, $service->daily('2011-12-30', new DateTimeZone('Pacific/Apia')));
        expectException(fn () => $resolver->resolve('2019-01-27', '24:00', new DateTimeZone('UTC')), InvalidArgumentException::class);
        expectException(fn () => $resolver->resolve('2019-01-27', '12:00', new DateTimeZone('+09:00')), InvalidArgumentException::class);
    }

    public function testUnknownTimePreservesCandidatesAndSensitiveErrorsStayGeneric(): void
    {
        $service = new SajuDayPillarService();
        $results = $service->candidateDates(['2019-01-27', '2019-01-28', '2019-01-27']);
        self::assertSame(['甲子', '乙丑', '甲子'], array_column(array_column($results, 'pillar'), 'ganzhi'));
        self::assertCount(3, $results);
        expectException(fn () => $service->candidateDates([]), InvalidArgumentException::class);
        expectException(fn () => $service->candidateDates([null]), InvalidArgumentException::class);
        expectException(fn () => $service->candidateDates(['key' => '2019-01-27']), InvalidArgumentException::class);
        try {
            (new DayPillarCalculator())->calculate('PRIVATE_INPUT_SENTINEL');
            self::fail('Invalid date accepted.');
        } catch (InvalidArgumentException $error) {
            self::assertSame('INVALID_DATE', $error->getMessage());
        }
    }
}
