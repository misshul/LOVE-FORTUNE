<?php
declare(strict_types=1);

namespace LoveFortune\Core\Engine\Saju;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Day-pillar orchestration only, not the complete four-pillar/score engine. */
final class SajuDayPillarService
{
    public const DAILY_REFERENCE = 'DAILY_SAJU_REFERENCE_V1';

    public function __construct(
        private readonly DayPillarCalculator $calculator = new DayPillarCalculator(),
        private readonly CalculationDateResolver $dates = new CalculationDateResolver(),
        private readonly DailySampleResolver $samples = new DailySampleResolver(),
    ) {}

    /** Location lookup and natal gap/fold resolution must have finished upstream. */
    public function natalResolved(DateTimeImmutable $local, float $longitude): array
    {
        (new GregorianDayNumber())->assertPublicDate($local->format('Y-m-d'));
        return $this->resolved($local, $longitude);
    }

    public function dailyResolvedInstant(DateTimeImmutable $instant): array
    {
        return $this->resolved($instant->setTimezone(new DateTimeZone('Asia/Seoul')), 127.5);
    }

    /** Retains four distinct nominal slots even when DST maps them to one instant. */
    public function daily(string $date, DateTimeZone $targetTimezone): array
    {
        $results = [];
        foreach (['06:00', '12:00', '18:00', '23:00'] as $time) {
            $results[$time] = $this->dailyResolvedInstant($this->samples->resolve($date, $time, $targetTimezone));
        }
        return $results;
    }

    /** Preserve upstream candidate order/multiplicity; never fabricate a birth time. */
    public function candidateDates(array $calculationDates): array
    {
        if ($calculationDates === [] || !array_is_list($calculationDates)) {
            throw new InvalidArgumentException('CALCULATION_DATE_CANDIDATES_REQUIRED');
        }
        return array_map(function ($date): array {
            if (!is_string($date)) {
                throw new InvalidArgumentException('INVALID_DATE');
            }
            return ['calculationDate' => $date, 'pillar' => $this->calculator->calculate($date)];
        }, $calculationDates);
    }

    private function resolved(DateTimeImmutable $local, float $longitude): array
    {
        $date = $this->dates->fromResolvedLocal($local, $longitude);
        return ['calculationDate' => $date, 'pillar' => $this->calculator->calculate($date)];
    }
}
