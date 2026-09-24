<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

use LoveFortune\Core\Domain\Score\CategoryResult;
use LoveFortune\Core\Domain\Score\DailyAggregator;
use LoveFortune\Core\Domain\Score\LifetimeBlender;
use LoveFortune\Core\Engine\Zodiac\ZodiacCatalog;
use LoveFortune\Core\Engine\Zodiac\ZodiacDateResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacPairResolver;
use LoveFortune\Core\Engine\Zodiac\ZodiacScorer;
use LoveFortune\Core\Support\Rational as R;

require_once dirname(__DIR__) . '/zodiac-cross.php';

final class ZodiacTest extends \PHPUnit\Framework\TestCase
{
    public function testFrozenExactSimulationGoldensAndEveryPublicDate(): void
    {
        $fixture = json_decode(file_get_contents(dirname(__DIR__) . '/fixtures/zodiac-golden.json'), true, 512, JSON_THROW_ON_ERROR);
        $result = zodiacCross();
        foreach (['cases', 'daily', 'round', 'compare', 'ranks'] as $key) {
            // Canonical JSON object ordering is irrelevant; exact rational strings are not.
            self::assertEquals(array_column($fixture[$key], 'expected'), $result[$key], $key);
        }
        self::assertSame($fixture['dates'], $result['dates']);
        self::assertSame(73049, $result['dates']['count']);
        foreach ($fixture['precision'] as $i => $row) {
            self::assertSame(['signal' => $row['signal'], 'peakIndex' => $row['peakIndex']], $result['precision'][$i]);
        }
        $pairs = [];
        foreach ($result['pairs'] as $row) {
            self::assertSame($row, (new ZodiacPairResolver())->resolve($row['signB'], $row['signA']));
            $pairs[$row['pairId']] = $row['primaryRelation'];
        }
        self::assertCount(78, $pairs);
        foreach ($result['features'] as $features) {
            self::assertCount(4, $features);
            foreach ($features as $feature) {
                self::assertNotSame('COMMUNICATION', $feature['category']);
                self::assertNotSame('LONG_TERM', $feature['category']);
            }
        }
        self::assertEquals(['SAME_SIGN' => 12, 'OPPOSITE_SIGN' => 6, 'SAME_ELEMENT' => 12, 'COMPATIBLE_ELEMENT' => 12, 'CHALLENGING_ELEMENT' => 36], array_count_values($pairs));
    }

    public function testDateRejectionAndBoundary(): void
    {
        $resolver = new ZodiacDateResolver();
        foreach (['1900-02-29', '2001-02-29', '2000-04-31', '2000-2-01', "2000-01-01\n", '1899-12-31', '2100-01-01'] as $date) {
            expectException(fn () => $resolver->resolve($date), $date < '1900' || $date >= '2100' ? OutOfRangeException::class : InvalidArgumentException::class);
        }
        foreach (ZodiacCatalog::configuration()['catalog']['referenceTables']['signs'] as $row) {
            self::assertSame($row['sign'], $resolver->resolve('2000-' . $row['start'])['sign']);
            self::assertSame($row['sign'], $resolver->resolve('2000-' . $row['end'])['sign']);
        }
        self::assertSame('PISCES', $resolver->resolve('2000-02-29')['sign']);
        expectException(fn () => (new ZodiacPairResolver())->resolve('SUN', 'ARIES'), InvalidArgumentException::class);
    }

    public function testScopeAndContextFailClosed(): void
    {
        foreach (['LIFETIME', 'DAILY', 'UNKNOWN'] as $period) { self::assertFalse(ZodiacCatalog::allows('ASTROLOGY', $period)); }
        self::assertFalse(ZodiacCatalog::allows('ZODIAC', 'DAILY'));
        self::assertTrue(ZodiacCatalog::allows('SAJU', 'DAILY'));
        $row = new CategoryResult(R::of(50), R::of(1), R::of('3/4'));
        foreach (['COMMUNICATION', 'LONG_TERM', 'UNKNOWN'] as $category) {
            expectException(fn () => (new LifetimeBlender())->blend([], [$category => $row]), InvalidArgumentException::class);
        }
        $zodiac = (new ZodiacScorer())->score('ARIES', 'ARIES');
        self::assertSame('50/1', (string) $zodiac['ATTRACTION']->score);
        self::assertSame('50/1', (string) $zodiac['PASSION']->score);
        $missing = (new LifetimeBlender())->blend([], $zodiac);
        self::assertNull($missing['categories']['COMMUNICATION']->score);
        self::assertSame('1/5', (string) $missing['categories']['EMOTION']->coverage);
        self::assertSame('105/2', (string) $missing['categories']['EMOTION']->score); // .2 * (62.5 - 50).
        self::assertSame('3/4', (string) $missing['overallPreCoverageConfidence']);
        self::assertSame('153/1720', (string) $missing['overall']->confidence);
        self::assertNull((new LifetimeBlender())->blend([], [])['overall']->score);
    }

    public function testDailyMissingSamplesNullBaselineAndRejectZodiac(): void
    {
        $daily = new DailyAggregator();
        $slot = ['source' => 'SAJU', 'signal' => R::of('1/2'), 'confidence' => R::of(1)];
        $result = $daily->aggregate([null, $slot, null, null]);
        self::assertSame('1/2', (string) $result['signal']);
        self::assertSame('1/4', (string) $result['confidence']);
        self::assertSame(1, $result['peakIndex']);
        self::assertNull($daily->score(null, $result['signal']));
        self::assertSame('100/1', (string) $daily->score(R::of(99), R::of(1)));
        self::assertSame('0/1', (string) $daily->score(R::of(1), R::of(-1)));
        self::assertSame(0, $daily->aggregate([null, null, null, null])['sampleCount']);
        $slot['source'] = 'ZODIAC';
        expectException(fn () => $daily->aggregate([$slot, null, null, null]), InvalidArgumentException::class);
    }

    public function testExactArithmeticLawsAndRejection(): void
    {
        foreach (['not-a-number', '1e-5', 'NaN', '1/-2', '1/2/3'] as $bad) { expectException(fn () => R::of($bad), InvalidArgumentException::class); }
        expectException(fn () => R::of('1/0'), DivisionByZeroError::class);
        expectException(fn () => R::of(1)->divide(R::of(0)), DivisionByZeroError::class);
        expectException(fn () => R::of(0.5), TypeError::class);
        foreach (['0', '-000.000', '-7/352', '123456789012345678901234567890/29', '1/999999999999999999999999999'] as $text) {
            $a = R::of($text);
            self::assertTrue($a->add($a->negate())->isZero());
            self::assertSame((string) $a, (string) $a->add(R::of('7/13'))->subtract(R::of('7/13')));
            self::assertSame((string) $a, (string) $a->multiply(R::of('17/31'))->divide(R::of('17/31')));
        }
        self::assertSame('0.0000', R::of('-0.000049')->halfUp4());
        foreach (['44.999949' => 'CAUTION', '44.99995' => 'BALANCED', '59.99995' => 'GOOD', '74.99995' => 'VERY_GOOD', '84.99995' => 'EXCELLENT'] as $score => $status) {
            self::assertSame($status, (new CategoryResult(R::of($score), R::of(1), R::of(1)))->status());
        }
    }
}
